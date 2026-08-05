import { spawn } from 'node:child_process';
import { createRequire } from 'node:module';
import { mkdir, rm, writeFile } from 'node:fs/promises';

const require = createRequire(import.meta.url);
const WebSocket = require('ws');
const port = 9788;
const profile = '/tmp/nexor-customer-evidence';
const outputDir = './artifacts/nexor-evidence';
const sleep = ms => new Promise(resolve => setTimeout(resolve, ms));

await rm(profile, { recursive: true, force: true });
await mkdir(outputDir, { recursive: true });

const chrome = spawn('/usr/bin/chromium', [
  '--headless=new', '--no-sandbox', '--disable-dev-shm-usage',
  `--remote-debugging-port=${port}`, `--user-data-dir=${profile}`,
  '--window-size=1440,1000', 'about:blank',
], { stdio: 'ignore' });

for (let attempt = 0; attempt < 80; attempt++) {
  try { if ((await fetch(`http://127.0.0.1:${port}/json/version`)).ok) break; } catch {}
  await sleep(100);
}

const targets = await (await fetch(`http://127.0.0.1:${port}/json/list`)).json();
const ws = new WebSocket(targets.find(target => target.type === 'page').webSocketDebuggerUrl);
await new Promise((resolve, reject) => { ws.onopen = resolve; ws.onerror = reject; });

let id = 0;
const pending = new Map();
const errors = [];
ws.onmessage = ({ data }) => {
  const message = JSON.parse(data);
  if (message.id && pending.has(message.id)) {
    const call = pending.get(message.id);
    pending.delete(message.id);
    message.error ? call.reject(new Error(message.error.message)) : call.resolve(message.result);
  } else if (message.method === 'Runtime.exceptionThrown') {
    errors.push(message.params.exceptionDetails.text);
  } else if (message.method === 'Log.entryAdded' && message.params.entry.level === 'error') {
    errors.push(message.params.entry.text);
  } else if (message.method === 'Network.loadingFailed' && !message.params.canceled) {
    errors.push(message.params.errorText);
  }
};

const send = (method, params = {}) => new Promise((resolve, reject) => {
  const callId = ++id;
  pending.set(callId, { resolve, reject });
  ws.send(JSON.stringify({ id: callId, method, params }));
});
const evaluate = async expression => {
  const result = await send('Runtime.evaluate', { expression, returnByValue: true, awaitPromise: true });
  if (result.exceptionDetails) throw new Error(result.exceptionDetails.text);
  return result.result.value;
};
const navigate = async url => {
  await send('Page.navigate', { url });
  for (let attempt = 0; attempt < 80; attempt++) {
    if (await evaluate('document.readyState') === 'complete') break;
    await sleep(100);
  }
  await sleep(1200);
};
const screenshot = async (name, full = false) => {
  const result = await send('Page.captureScreenshot', {
    format: 'png',
    captureBeyondViewport: full,
    fromSurface: true,
  });
  const path = `${outputDir}/${name}`;
  await writeFile(path, Buffer.from(result.data, 'base64'));
  return path;
};

await send('Page.enable');
await send('Runtime.enable');
await send('Network.enable');
await send('Log.enable');
const finePointerScript = await send('Page.addScriptToEvaluateOnNewDocument', {
  source: `(()=>{const nativeMatchMedia=window.matchMedia.bind(window);window.matchMedia=query=>{if(query.includes('(hover: hover)')&&query.includes('(pointer: fine)'))return{matches:true,media:query,onchange:null,addListener(){},removeListener(){},addEventListener(){},removeEventListener(){},dispatchEvent(){return true;}};return nativeMatchMedia(query);};})();`,
});
await send('Emulation.setDeviceMetricsOverride', { width: 1440, height: 1000, deviceScaleFactor: 1, mobile: false });

await navigate('https://nexorpro.ru/');
const desktop = await evaluate(`(()=>({
  url: location.href,
  title: document.title,
  h1: document.querySelector('h1')?.textContent.trim() || '',
  pointerFine: matchMedia('(hover: hover) and (pointer: fine)').matches,
  exitIntent: window.NexorSettings?.enhancements?.exitIntent || null,
  sections: [...document.querySelectorAll('main section')].map((section, index) => ({
    index: index + 1,
    id: section.id || '',
    heading: section.querySelector('h1,h2')?.textContent.trim().replace(/\\s+/g, ' ') || '',
  })),
  required: {
    services: !![...document.querySelectorAll('main h2')].find(h => h.textContent.includes('Основные услуги')),
    calculator: !!document.querySelector('#calculator'),
    prices: !!document.querySelector('#prices'),
    video: !!document.querySelector('.nexor-video'),
    additional: !!document.querySelector('#additional-services'),
    promotions: !!document.querySelector('#promotions'),
    about: !!document.querySelector('#about-company-nexor'),
    faq: !!document.querySelector('#faq'),
  },
  brokenImages: [...document.images].filter(image => image.complete && image.src && !image.naturalWidth).map(image => image.src),
  horizontalOverflow: document.documentElement.scrollWidth > document.documentElement.clientWidth + 1,
}))()`);

const fullHomepage = await screenshot('01-homepage-production-full.png', true);
const menu = await evaluate(`(()=>{
  const trigger = [...document.querySelectorAll('.nexor-desktop-nav button')].find(button => button.textContent.trim() === 'Услуги');
  trigger?.click();
  const panel = document.querySelector('.nexor-mega');
  return { trigger: !!trigger, open: !!panel && !panel.hidden, links: panel ? [...panel.querySelectorAll('a')].map(a => a.textContent.trim()) : [] };
})()`);
await sleep(300);
const navigation = await screenshot('02-desktop-navigation.png');
await evaluate(`document.querySelector('.nexor-desktop-nav button[aria-expanded="true"]')?.click()`);

const popupImmediate = await evaluate(`(()=>{
  document.dispatchEvent(new MouseEvent('mouseout', { bubbles: true, clientY: 0, relatedTarget: null }));
  const dialog = document.querySelector('.nexor-exit');
  return { exists: !!dialog, visible: !!dialog && !dialog.hidden };
})()`);
await sleep(21000);
const popupTriggered = await evaluate(`(()=>{
  const dialog = document.querySelector('.nexor-exit');
  return { exists: !!dialog, visible: !!dialog && !dialog.hidden, title: dialog?.querySelector('h2')?.textContent.trim() || '', focusedControl: document.activeElement?.className || '' };
})()`);
await sleep(300);
const exitPopup = popupTriggered.visible ? await screenshot('03-exit-intent-popup.png') : null;
const popupEscape = await evaluate(`(()=>{
  document.activeElement?.dispatchEvent(new KeyboardEvent('keydown', { bubbles: true, key: 'Escape' }));
  const dialog = document.querySelector('.nexor-exit');
  const value = localStorage.getItem('nexor_exit_20260722-auto') || '';
  return { hidden: !!dialog && dialog.hidden, suppressionStored: Number(value) > Date.now() };
})()`);

await navigate('https://nexorpro.ru/?s=ремонт');
const search = await evaluate(`(()=>({
  noindex: document.querySelector('meta[name="robots"]')?.content || '',
  heading: document.querySelector('h1')?.textContent.trim() || '',
  results: document.querySelectorAll('.nexor-search-results article').length,
  leakedLeads: [...document.querySelectorAll('main a')].some(a => /заявк/i.test(a.textContent)),
}))()`);
const searchResults = await screenshot('04-search-results.png', true);
await evaluate(`localStorage.removeItem('nexor_exit_20260722-auto')`);

await send('Page.removeScriptToEvaluateOnNewDocument', { identifier: finePointerScript.identifier });
await send('Emulation.setDeviceMetricsOverride', { width: 390, height: 844, deviceScaleFactor: 1, mobile: true });
await send('Emulation.setTouchEmulationEnabled', { enabled: true, maxTouchPoints: 5 });
await navigate('https://nexorpro.ru/');
const mobile = await evaluate(`(()=>{
  const trigger = document.querySelector('.nexor-mobile-trigger');
  trigger?.click();
  const panel = document.querySelector('.nexor-mobile-menu');
  return {
    trigger: !!trigger,
    open: !!panel && !panel.hidden,
    pointerFine: matchMedia('(hover: hover) and (pointer: fine)').matches,
    links: panel?.querySelectorAll('a').length || 0,
    horizontalOverflow: document.documentElement.scrollWidth > document.documentElement.clientWidth + 1,
  };
})()`);
await sleep(300);
const mobileNavigation = await screenshot('05-mobile-navigation.png');
await evaluate(`document.querySelector('.nexor-mobile-trigger')?.click()`);
await sleep(21000);
const mobilePopup = await evaluate(`(()=>{
  const dialog = document.querySelector('.nexor-exit');
  return { exists: !!dialog, visible: !!dialog && !dialog.hidden };
})()`);
const mobilePopupScreenshot = mobilePopup.visible ? await screenshot('06-mobile-popup.png') : null;

console.log(JSON.stringify({
  desktop,
  menu,
  popupImmediate,
  popupTriggered,
  popupEscape,
  search,
  mobile,
  mobilePopup,
  errors: [...new Set(errors)],
  screenshots: { fullHomepage, navigation, exitPopup, searchResults, mobileNavigation, mobilePopup: mobilePopupScreenshot },
}, null, 2));

ws.close();
chrome.kill('SIGKILL');
chrome.unref();
