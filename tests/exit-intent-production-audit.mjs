import { spawn } from 'node:child_process';
import { createRequire } from 'node:module';
import { rm, writeFile } from 'node:fs/promises';

const require = createRequire(import.meta.url);
const WebSocket = require('ws');
const port = 9790;
const profile = '/tmp/nexor-exit-audit';
const sleep = ms => new Promise(resolve => setTimeout(resolve, ms));
await rm(profile, { recursive: true, force: true });

const chrome = spawn('/usr/bin/chromium', [
  '--headless=new', '--no-sandbox', '--disable-dev-shm-usage', '--disable-http2', '--disable-background-networking',
  `--remote-debugging-port=${port}`, `--user-data-dir=${profile}`,
  '--window-size=1440,1000', 'about:blank',
], { stdio: 'ignore' });

for (let i = 0; i < 80; i++) {
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
  } else if (message.method === 'Runtime.exceptionThrown') errors.push(message.params.exceptionDetails.text);
};
const send = (method, params = {}) => new Promise((resolve, reject) => {
  const callId = ++id;
  pending.set(callId, { resolve, reject });
  ws.send(JSON.stringify({ id: callId, method, params }));
});
const evaluate = async expression => (await send('Runtime.evaluate', { expression, awaitPromise: true, returnByValue: true })).result.value;

await send('Page.enable');
await send('Runtime.enable');
const navigate = async url => {
  for (let attempt = 0; attempt < 3; attempt++) {
    await send('Page.navigate', { url: `${url}-${attempt}` });
    for (let i = 0; i < 120 && await evaluate('document.readyState') !== 'complete'; i++) await sleep(100);
    await sleep(700);
    if (await evaluate(`!!document.querySelector('.nexor-exit')`)) return;
  }
  throw new Error('The popup markup did not load after three attempts');
};
await navigate('https://nexorpro.ru/?exit-audit=20260731-151');

const immediate = await evaluate(`(()=>{const dialog=document.querySelector('.nexor-exit');return{exists:!!dialog,visible:!!dialog&&!dialog.hidden};})()`);
await sleep(20500);
const afterDelay = await evaluate(`(()=>{const dialog=document.querySelector('.nexor-exit');return{visible:!!dialog&&!dialog.hidden,title:dialog?.querySelector('h2')?.textContent.trim()||'',focusClose:document.activeElement?.classList.contains('nexor-exit__close')||false};})()`);
const escape = await evaluate(`(()=>{document.activeElement?.dispatchEvent(new KeyboardEvent('keydown',{bubbles:true,key:'Escape'}));const dialog=document.querySelector('.nexor-exit');const value=localStorage.getItem('nexor_exit_20260722-auto')||'';return{hidden:!!dialog&&dialog.hidden,suppressionStored:Number(value)>Date.now()};})()`);
await evaluate(`localStorage.removeItem('nexor_exit_20260722-auto')`);
await send('Emulation.setDeviceMetricsOverride', { width: 390, height: 844, deviceScaleFactor: 1, mobile: true });
await send('Emulation.setTouchEmulationEnabled', { enabled: true, maxTouchPoints: 5 });
await navigate('https://nexorpro.ru/?popup-mobile-audit=20260731-151');
const mobileImmediate = await evaluate(`(()=>{const dialog=document.querySelector('.nexor-exit');return{exists:!!dialog,visible:!!dialog&&!dialog.hidden};})()`);
await sleep(20500);
const mobileAfterDelay = await evaluate(`(()=>{const dialog=document.querySelector('.nexor-exit');return{visible:!!dialog&&!dialog.hidden,title:dialog?.querySelector('h2')?.textContent.trim()||''};})()`);
const mobileScreenshotPath = './artifacts/nexor-evidence/06-mobile-popup.png';
const mobileScreenshot = await send('Page.captureScreenshot', { format: 'png', captureBeyondViewport: false, fromSurface: true });
await writeFile(mobileScreenshotPath, Buffer.from(mobileScreenshot.data, 'base64'));

console.log(JSON.stringify({ immediate, afterDelay, escape, mobileImmediate, mobileAfterDelay, mobileScreenshotPath, errors }, null, 2));
ws.close();
chrome.kill('SIGKILL');
chrome.unref();

if (immediate.visible || !afterDelay.visible || !afterDelay.focusClose || !escape.hidden || !escape.suppressionStored || mobileImmediate.visible || !mobileAfterDelay.visible || errors.length) process.exitCode = 1;
