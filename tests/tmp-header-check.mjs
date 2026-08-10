import { spawn } from 'node:child_process';
import { writeFile, mkdir, rm } from 'node:fs/promises';

const chromePath = 'C:/Program Files/Google/Chrome/Application/chrome.exe';
const port = 9455;
const profile = 'C:/Users/DEV/AppData/Local/Temp/nexor-header-tmp';
await rm(profile, { recursive: true, force: true });
const chrome = spawn(chromePath, [
  '--headless=new',
  '--disable-gpu',
  '--no-first-run',
  `--remote-debugging-port=${port}`,
  `--user-data-dir=${profile}`,
  'about:blank',
], { stdio: 'ignore' });
const sleep = ms => new Promise(resolve => setTimeout(resolve, ms));
let version = null;
for (let i = 0; i < 80; i++) {
  try {
    const response = await fetch(`http://127.0.0.1:${port}/json/version`);
    if (response.ok) { version = await response.json(); break; }
  } catch {}
  await sleep(200);
}
if (!version) throw new Error('chrome not ready');
const targets = await (await fetch(`http://127.0.0.1:${port}/json/list`)).json();
const ws = new WebSocket(targets.find(target => target.type === 'page').webSocketDebuggerUrl);
await new Promise((resolve, reject) => { ws.onopen = resolve; ws.onerror = reject; });
let id = 0;
const pending = new Map();
ws.onmessage = ({ data }) => {
  const message = JSON.parse(data);
  if (!message.id || !pending.has(message.id)) return;
  const call = pending.get(message.id);
  pending.delete(message.id);
  message.error ? call.reject(new Error(message.error.message)) : call.resolve(message.result);
};
const send = (method, params = {}) => new Promise((resolve, reject) => {
  const callId = ++id;
  pending.set(callId, { resolve, reject });
  ws.send(JSON.stringify({ id: callId, method, params }));
});
const evaluate = async expression => (await send('Runtime.evaluate', { expression, returnByValue: true })).result.value;
await mkdir('artifacts', { recursive: true });
const shot = async name => {
  const { data } = await send('Page.captureScreenshot', { format: 'png' });
  await writeFile(`artifacts/${name}`, Buffer.from(data, 'base64'));
};
await send('Page.enable');
await send('Runtime.enable');
await send('Emulation.setDeviceMetricsOverride', { width: 1440, height: 900, deviceScaleFactor: 1, mobile: false });
await send('Page.navigate', { url: 'http://localhost:8080/' });
await sleep(3500);
const state = await evaluate(`(()=>{
  const trigger=document.querySelector('.nexor-mobile-trigger');
  const nav=document.querySelector('.nexor-desktop-nav');
  const rect=el=>el?{left:Math.round(el.getBoundingClientRect().left),right:Math.round(el.getBoundingClientRect().right)}:null;
  return {trigger:!!trigger,triggerDisplay:trigger?getComputedStyle(trigger).display:null,triggerRect:rect(trigger),navDisplay:nav?getComputedStyle(nav).display:null};
})()`);
await shot('tmp-header-1440.png');
await evaluate(`document.querySelector('.nexor-mobile-trigger').click()`);
await sleep(120);
const mid = await evaluate(`(()=>{const p=document.querySelector('.nexor-mobile-menu');return{hidden:p.hidden,open:p.classList.contains('is-open'),transform:getComputedStyle(p).transform,opacity:getComputedStyle(p).opacity}})()`);
await shot('tmp-menu-mid.png');
await sleep(700);
const opened = await evaluate(`(()=>{const p=document.querySelector('.nexor-mobile-menu');return{transform:getComputedStyle(p).transform,opacity:getComputedStyle(p).opacity,firstLinkOpacity:getComputedStyle(p.querySelector('nav > *')).opacity}})()`);
await shot('tmp-menu-open.png');
await evaluate(`document.querySelector('.nexor-mobile-menu__close').click()`);
await sleep(150);
const closing = await evaluate(`(()=>{const p=document.querySelector('.nexor-mobile-menu');return{hidden:p.hidden,transform:getComputedStyle(p).transform}})()`);
await sleep(600);
const closed = await evaluate(`(()=>{const p=document.querySelector('.nexor-mobile-menu');return{hidden:p.hidden,locked:document.documentElement.classList.contains('nexor-lock')}})()`);
console.log(JSON.stringify({ state, mid, opened, closing, closed }, null, 2));
ws.close();
chrome.kill('SIGTERM');
