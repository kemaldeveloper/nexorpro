import { spawn } from 'node:child_process';
import { createRequire } from 'node:module';
import { rm } from 'node:fs/promises';

const require = createRequire(import.meta.url);
const WebSocket = require('ws');
const port = 9666;
const profile = '/tmp/nexor-layout-audit';
const sleep = ms => new Promise(resolve => setTimeout(resolve, ms));
await rm(profile, { recursive: true, force: true });

const sitemap = await (await fetch('https://nexorpro.ru/wp-sitemap-posts-page-1.xml')).text();
const projectSitemap = await (await fetch('https://nexorpro.ru/wp-sitemap-posts-nexor_project-1.xml')).text();
const urls = [...new Set([...sitemap.matchAll(/<loc>([^<]+)<\/loc>/g), ...projectSitemap.matchAll(/<loc>([^<]+)<\/loc>/g)].map(match => match[1]))];

const chrome = spawn('/usr/bin/chromium', ['--headless=new', '--no-sandbox', '--disable-dev-shm-usage', `--remote-debugging-port=${port}`, `--user-data-dir=${profile}`, 'about:blank'], { stdio: 'ignore' });
for (let attempt = 0; attempt < 50; attempt++) {
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
    const call = pending.get(message.id); pending.delete(message.id);
    message.error ? call.reject(new Error(message.error.message)) : call.resolve(message.result);
  } else if (message.method === 'Runtime.exceptionThrown') errors.push(message.params.exceptionDetails.text);
};
const send = (method, params = {}) => new Promise((resolve, reject) => {
  const callId = ++id; pending.set(callId, { resolve, reject });
  ws.send(JSON.stringify({ id: callId, method, params }));
});
const evaluate = async expression => (await send('Runtime.evaluate', { expression, returnByValue: true })).result.value;
await send('Page.enable'); await send('Runtime.enable');

const results = [];
for (const width of [390, 1024]) {
  await send('Emulation.setDeviceMetricsOverride', { width, height: 900, deviceScaleFactor: 1, mobile: width < 768 });
  for (const url of urls) {
    await send('Page.navigate', { url });
    for (let attempt = 0; attempt < 50 && await evaluate('document.readyState') !== 'complete'; attempt++) await sleep(100);
    await sleep(100);
    results.push(await evaluate(`(()=>{const h1=document.querySelector('h1'),r=h1?.getBoundingClientRect(),clientWidth=document.documentElement.clientWidth;const overflow=[...document.querySelectorAll('body *')].map(element=>{const box=element.getBoundingClientRect();return{tag:element.tagName,className:String(element.className||'').slice(0,120),text:String(element.textContent||'').trim().replace(/\\s+/g,' ').slice(0,80),left:Math.round(box.left),right:Math.round(box.right),width:Math.round(box.width)}}).filter(item=>item.left < -1 || item.right > clientWidth + 1).sort((a,b)=>(b.right-clientWidth)-(a.right-clientWidth)).slice(0,30);return{url:location.href,width:innerWidth,clientWidth,scrollWidth:document.documentElement.scrollWidth,h1:!!h1,h1Visible:!!h1&&getComputedStyle(h1).visibility!=='hidden'&&getComputedStyle(h1).display!=='none'&&r.width>0&&r.height>0,overflow};})()`));
  }
}
const failures = results.filter(result => result.scrollWidth > result.clientWidth + 1 || !result.h1Visible);
console.log(JSON.stringify({ pages: urls.length, checks: results.length, failures, browserErrors: [...new Set(errors)] }, null, 2));
ws.close(); chrome.kill('SIGTERM');
if (failures.length || errors.length) process.exitCode = 1;
