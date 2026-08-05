import { spawn } from 'node:child_process';
import { createRequire } from 'node:module';
import { rm } from 'node:fs/promises';

const require = createRequire(import.meta.url);
const WebSocket = require('ws');
const port = 9444;
await rm('/tmp/nexor-header-audit', { recursive: true, force: true });
const chrome = spawn('/usr/bin/chromium', [
  '--headless=new', '--no-sandbox', '--disable-dev-shm-usage',
  `--remote-debugging-port=${port}`, '--user-data-dir=/tmp/nexor-header-audit',
  'about:blank',
], { stdio: 'ignore' });
const sleep = ms => new Promise(resolve => setTimeout(resolve, ms));
for (let i = 0; i < 50; i++) {
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
  if (!message.id || !pending.has(message.id)) return;
  const call = pending.get(message.id); pending.delete(message.id);
  message.error ? call.reject(new Error(message.error.message)) : call.resolve(message.result);
};
const send = (method, params = {}) => new Promise((resolve, reject) => {
  const callId = ++id; pending.set(callId, { resolve, reject });
  ws.send(JSON.stringify({ id: callId, method, params }));
});
const evaluate = async expression => (await send('Runtime.evaluate', { expression, returnByValue: true })).result.value;
await send('Page.enable'); await send('Runtime.enable');
await send('Page.navigate', { url: 'https://nexorpro.ru/' });
await sleep(3000);
const results = [];
for (const width of [390, 768, 1024, 1280, 1366, 1440, 1600, 1920, 2048]) {
  await send('Emulation.setDeviceMetricsOverride', { width, height: 900, deviceScaleFactor: 1, mobile: width < 768 });
  await sleep(250);
  results.push(await evaluate(`(()=>{
    const rect = element => element ? {left:Math.round(element.getBoundingClientRect().left),right:Math.round(element.getBoundingClientRect().right),width:Math.round(element.getBoundingClientRect().width)} : null;
    const search=document.querySelector('header nav[aria-label="Main"]~.nexor-search');
    const phone=document.querySelector('header a[href^="tel:"]:not([aria-label])');
    const desktopNav=document.querySelector('header nav[aria-label="Main"]')?.parentElement;
    const services=document.querySelector('header nav[aria-label="Main"] button');
    const logo=document.querySelector('header .container-nexor a[href="/"]');
    const headerRow=document.querySelector('header .container-nexor>div');
    const aboutGrid=document.querySelector('#about-company-nexor>.container-nexor>.grid');
    const aboutText=aboutGrid?.lastElementChild;
    const overflow=[...document.querySelectorAll('body *')].map(element=>{const r=element.getBoundingClientRect();return{tag:element.tagName,className:String(element.className||'').slice(0,100),left:Math.round(r.left),right:Math.round(r.right),width:Math.round(r.width)}}).filter(item=>item.left < -1 || item.right > innerWidth + 1).sort((a,b)=>(b.right-innerWidth)-(a.right-innerWidth)).slice(0,8);
    return {viewport:innerWidth,scrollWidth:document.documentElement.scrollWidth,logo:rect(logo),services:rect(services),search:rect(search),phone:rect(phone),desktopNav:rect(desktopNav),row:rect(headerRow),logoGap:logo&&services?Math.round(services.getBoundingClientRect().left-logo.getBoundingClientRect().right):null,searchGap:search&&phone?Math.round(phone.getBoundingClientRect().left-search.getBoundingClientRect().right):null,about:aboutText?{grid:rect(aboutGrid),text:rect(aboutText),minWidth:getComputedStyle(aboutText).minWidth,transform:getComputedStyle(aboutText).transform,columns:getComputedStyle(aboutGrid).gridTemplateColumns,gap:getComputedStyle(aboutGrid).columnGap}:null,overflow};
  })()`));
}
console.log(JSON.stringify(results, null, 2));
ws.close(); chrome.kill('SIGTERM');
