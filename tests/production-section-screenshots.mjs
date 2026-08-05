import { spawn } from 'node:child_process';
import { createRequire } from 'node:module';
import { rm, writeFile } from 'node:fs/promises';

const require = createRequire(import.meta.url);
const WebSocket = require('ws');
const port = 9792;
const profile = '/tmp/nexor-section-shots';
const outputDir = './artifacts/nexor-evidence';
const sleep = ms => new Promise(resolve => setTimeout(resolve, ms));
await rm(profile, { recursive: true, force: true });

const chrome = spawn('/usr/bin/chromium', [
  '--headless=new', '--no-sandbox', '--disable-dev-shm-usage',
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
ws.onmessage = ({ data }) => {
  const message = JSON.parse(data);
  if (message.id && pending.has(message.id)) {
    const call = pending.get(message.id);
    pending.delete(message.id);
    message.error ? call.reject(new Error(message.error.message)) : call.resolve(message.result);
  }
};
const send = (method, params = {}) => new Promise((resolve, reject) => {
  const callId = ++id;
  pending.set(callId, { resolve, reject });
  ws.send(JSON.stringify({ id: callId, method, params }));
});
const evaluate = async expression => (await send('Runtime.evaluate', { expression, returnByValue: true, awaitPromise: true })).result.value;

await send('Page.enable');
await send('Runtime.enable');
await send('Page.navigate', { url: 'https://nexorpro.ru/?section-shots=20260722' });
for (let i = 0; i < 80 && await evaluate('document.readyState') !== 'complete'; i++) await sleep(100);
await sleep(1800);

const captureElement = async (selector, filename) => {
  const clip = await evaluate(`(()=>{const element=document.querySelector(${JSON.stringify(selector)});if(!element)return null;const rect=element.getBoundingClientRect();return{x:Math.max(0,rect.left+scrollX),y:Math.max(0,rect.top+scrollY),width:Math.min(document.documentElement.scrollWidth,rect.width),height:rect.height,scale:1};})()`);
  if (!clip) throw new Error(`Missing element: ${selector}`);
  const result = await send('Page.captureScreenshot', { format: 'png', captureBeyondViewport: true, fromSurface: true, clip });
  const path = `${outputDir}/${filename}`;
  await writeFile(path, Buffer.from(result.data, 'base64'));
  return path;
};

const screenshots = {
  services: await captureElement('#main-services', '07-main-services.png'),
  calculator: await captureElement('#calculator', '08-calculator.png'),
};
console.log(JSON.stringify(screenshots, null, 2));
ws.close();
chrome.kill('SIGKILL');
chrome.unref();
