import { spawn } from 'node:child_process';
import { createRequire } from 'node:module';
import { mkdir, rm, writeFile } from 'node:fs/promises';

const require = createRequire(import.meta.url);
const WebSocket = require('ws');
const outputDir = process.argv[2] || '/tmp/nexor-service-pages-audit';
const label = process.argv[3] || 'production';
const port = Number(process.argv[4] || 9806);
const baseUrl = (process.argv[5] || 'https://nexorpro.ru').replace(/\/$/, '');
const profile = `/tmp/nexor-service-pages-${port}`;
const sleep = ms => new Promise(resolve => setTimeout(resolve, ms));
const slugs = [
  'remont-kvartir-pod-klyuch',
  'capital-remont',
  'design-remont',
  'remont-v-novostroyke',
  'cosmetic-remont',
  'remont-domov-pod-klyuch',
];

await rm(profile, { recursive: true, force: true });
await mkdir(outputDir, { recursive: true });

const chrome = spawn('/usr/bin/chromium', [
  '--headless=new', '--no-sandbox', '--disable-dev-shm-usage', '--disable-http2',
  '--disable-background-networking', '--hide-scrollbars',
  `--remote-debugging-port=${port}`, `--user-data-dir=${profile}`,
  '--window-size=1440,900', 'about:blank',
], { stdio: 'ignore' });

let ws;
let callId = 0;
const pending = new Map();
const browserErrors = [];

const close = () => {
  if (ws?.readyState === WebSocket.OPEN) ws.close();
  chrome.kill('SIGKILL');
  chrome.unref();
};

try {
  let ready = false;
  for (let attempt = 0; attempt < 100; attempt++) {
    try {
      if ((await fetch(`http://127.0.0.1:${port}/json/version`)).ok) { ready = true; break; }
    } catch {}
    await sleep(100);
  }
  if (!ready) throw new Error('Chromium debugging endpoint did not start');
  const targets = await (await fetch(`http://127.0.0.1:${port}/json/list`)).json();
  ws = new WebSocket(targets.find(item => item.type === 'page').webSocketDebuggerUrl);
  await new Promise((resolve, reject) => {
    ws.onopen = resolve;
    ws.onerror = reject;
  });
  ws.onmessage = ({ data }) => {
    const message = JSON.parse(data);
    if (message.id && pending.has(message.id)) {
      const call = pending.get(message.id);
      pending.delete(message.id);
      clearTimeout(call.timer);
      message.error ? call.reject(new Error(message.error.message)) : call.resolve(message.result);
    } else if (message.method === 'Runtime.exceptionThrown') {
      browserErrors.push(message.params.exceptionDetails.text || 'Runtime exception');
    } else if (message.method === 'Log.entryAdded' && message.params.entry.level === 'error') {
      browserErrors.push(message.params.entry.text);
    }
  };
  const send = (method, params = {}, timeoutMs = 20000) => new Promise((resolve, reject) => {
    const id = ++callId;
    const timer = setTimeout(() => { pending.delete(id); reject(new Error(`${method} timed out`)); }, timeoutMs);
    pending.set(id, { resolve, reject, timer });
    ws.send(JSON.stringify({ id, method, params }));
  });
  const evaluate = async expression => {
    const response = await send('Runtime.evaluate', { expression, returnByValue: true, awaitPromise: true });
    if (response.exceptionDetails) {
      const detail = response.exceptionDetails.exception?.description || response.exceptionDetails.text || 'Evaluation failed';
      throw new Error(detail);
    }
    return response.result.value;
  };
  const navigate = async url => {
    let lastError;
    for (let attempt = 0; attempt < 3; attempt++) {
      try {
        await send('Page.navigate', { url }, 25000);
        for (let tick = 0; tick < 180; tick++) {
          if (await evaluate(`document.readyState==='complete' && !!document.querySelector('main')`)) break;
          await sleep(100);
        }
        if (!await evaluate(`document.readyState==='complete' && document.body.innerText.length>900`)) throw new Error('Page did not finish rendering');
        await sleep(900);
        return;
      } catch (error) {
        lastError = error;
        await sleep(700);
      }
    }
    throw lastError;
  };
  const screenshot = async path => {
    const result = await send('Page.captureScreenshot', { format: 'png', fromSurface: true }, 30000);
    await writeFile(path, Buffer.from(result.data, 'base64'));
  };

  await send('Page.enable');
  await send('Runtime.enable');
  await send('Log.enable');
  await send('Page.addScriptToEvaluateOnNewDocument', {
    source: `try{localStorage.setItem('nexor_exit_20260722-auto',String(Date.now()+86400000));}catch{}`,
  });

  const checks = [];
  for (const viewport of [
    { name: 'desktop', width: 1440, height: 900, mobile: false },
    { name: 'mobile', width: 390, height: 844, mobile: true },
  ]) {
    await send('Emulation.setDeviceMetricsOverride', {
      width: viewport.width, height: viewport.height, deviceScaleFactor: 1, mobile: viewport.mobile,
    });
    for (const slug of slugs) {
      const url = baseUrl.includes('127.0.0.1')
        ? `${baseUrl}/${slug}.html?service-audit=${encodeURIComponent(label)}-${viewport.name}`
        : `${baseUrl}/${slug}/?service-audit=${encodeURIComponent(label)}-${viewport.name}`;
      await navigate(url);
      await evaluate(`scrollTo(0,0)`);
      await sleep(150);
      await screenshot(`${outputDir}/${slug}-${viewport.width}x${viewport.height}.png`);
      checks.push(await evaluate(`(()=>{
        const h1=document.querySelector('h1');
        const main=document.querySelector('main');
        const hero=main?.querySelector('section');
        const formButtons=[...document.querySelectorAll('button')].filter(button=>/замер|смет|стоим|консультац/i.test(button.textContent||''));
        const links=[...document.querySelectorAll('a[href]')];
        const badLinks=links.filter(link=>{try{const href=link.href.toLowerCase();return new URL(link.href).origin!==location.origin&&!href.startsWith('tel:')&&!href.startsWith('mailto:')&&!href.startsWith('https://t.me/')&&!href.startsWith('https://vk.com/')}catch{return true}});
        const overflow=[...document.querySelectorAll('body *')].filter(element=>{const box=element.getBoundingClientRect();return box.width>0&&(box.left < -1 || box.right > document.documentElement.clientWidth+1)}).slice(0,8).map(element=>({tag:element.tagName,className:String(element.className||'').slice(0,100)}));
        return {
          slug:${JSON.stringify(slug)}, viewport:${JSON.stringify(viewport.name)}, status:document.readyState,
          title:document.title, canonical:document.querySelector('link[rel=canonical]')?.href||'',
          h1Count:document.querySelectorAll('h1').length, h1:h1?.textContent.trim()||'',
          mainClass:main?.className||'', heroClass:hero?.className||'', sections:main?.querySelectorAll(':scope > section').length||0,
          formButtons:formButtons.length, phoneLinks:links.filter(link=>link.href.startsWith('tel:')).length,
          scrollWidth:document.documentElement.scrollWidth, clientWidth:document.documentElement.clientWidth,
          overflow, badLinks:badLinks.length,
        };
      })()`));
    }
  }

  const report = {
    label, pages: slugs.length, checks: checks.length, checks,
    failures: checks.filter(check => check.h1Count !== 1 || check.scrollWidth > check.clientWidth + 1 || !check.canonical || check.formButtons < 1 || check.phoneLinks < 1),
    browserErrors: [...new Set(browserErrors)],
  };
  await writeFile(`${outputDir}/report.json`, JSON.stringify(report, null, 2));
  console.log(JSON.stringify({ label, pages: report.pages, checks: report.checks, failures: report.failures, browserErrors: report.browserErrors }, null, 2));
  if (report.failures.length || report.browserErrors.length) process.exitCode = 1;
} finally {
  close();
}
