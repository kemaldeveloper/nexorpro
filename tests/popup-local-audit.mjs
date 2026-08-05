import { spawn } from 'node:child_process';
import { createRequire } from 'node:module';
import { rm } from 'node:fs/promises';

const root = process.cwd();
const profile = '/tmp/nexor-popup-local-audit';
const port = 9854;
const fixturePort = 9855;
const sleep = milliseconds => new Promise(resolve => setTimeout(resolve, milliseconds));
const require = createRequire(import.meta.url);
const WebSocket = require('ws');

await rm(profile, { recursive: true, force: true });
const fixtureServer = spawn('php', ['-S', `127.0.0.1:${fixturePort}`, '-t', root], { stdio: 'ignore' });
for (let attempt = 0; attempt < 100; attempt++) {
  try { if ((await fetch(`http://127.0.0.1:${fixturePort}/tests/popup-fixture.html`)).ok) break; } catch {}
  await sleep(50);
}
const chrome = spawn('/usr/bin/chromium', [
  '--headless=new', '--no-sandbox', '--disable-dev-shm-usage', '--allow-file-access-from-files',
  `--remote-debugging-port=${port}`, `--user-data-dir=${profile}`, 'about:blank',
], { stdio: 'ignore' });

for (let attempt = 0; attempt < 100; attempt++) {
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
  if (message.method === 'Runtime.exceptionThrown') errors.push(message.params.exceptionDetails.text);
  if (message.id && pending.has(message.id)) {
    const call = pending.get(message.id); pending.delete(message.id);
    message.error ? call.reject(new Error(message.error.message)) : call.resolve(message.result);
  }
};
const send = (method, params = {}) => new Promise((resolve, reject) => {
  const callId = ++id; pending.set(callId, { resolve, reject }); ws.send(JSON.stringify({ id: callId, method, params }));
});
const evaluate = async expression => (await send('Runtime.evaluate', { expression, returnByValue: true, awaitPromise: true })).result.value;
const fixture = async (name, timeout) => {
  await send('Page.navigate', { url: `http://127.0.0.1:${fixturePort}/tests/${name}` });
  for (let elapsed = 0; elapsed < timeout; elapsed += 100) {
    const result = await evaluate(`document.querySelector('#result')?.textContent || ''`);
    if (result) return JSON.parse(result);
    await sleep(100);
  }
  throw new Error(`${name} did not finish in ${timeout}ms`);
};

await send('Page.enable'); await send('Runtime.enable');
const desktop = await fixture('popup-fixture.html', 9000);
const mobile = await fixture('popup-mobile-fixture.html', 8000);
await send('Emulation.setDeviceMetricsOverride', { width: 1440, height: 900, deviceScaleFactor: 1, mobile: false });
await send('Page.navigate', { url: `http://127.0.0.1:${fixturePort}/tests/homepage-popup-fixture.php` });
for (let elapsed = 0; elapsed < 8000 && await evaluate('document.readyState') !== 'complete'; elapsed += 100) await sleep(100);
await sleep(5500);
const fullPageDesktop = await evaluate(`(()=>{const dialog=document.querySelector('.nexor-exit');return{visible:!!dialog&&!dialog.hidden,title:dialog?.querySelector('h2')?.textContent.trim()||'',focusClose:document.activeElement?.classList.contains('nexor-exit__close')||false};})()`);
const assertions = {
  desktopDelay: desktop.immediateNegative && desktop.conflictNegative && desktop.opened,
  desktopKeyboardAndFallback: desktop.escapeClosed && desktop.cookieFallback,
  mobileDelay: mobile.mobilePositive,
  fullPageDesktopDelay: fullPageDesktop.visible && fullPageDesktop.focusClose && fullPageDesktop.title === 'Хотите получить скидку сегодня?',
  noRuntimeErrors: errors.length === 0,
};
const report = { desktop, mobile, fullPageDesktop, assertions, errors, pass: Object.values(assertions).every(Boolean) };
console.log(JSON.stringify(report, null, 2));
ws.close(); chrome.kill('SIGKILL'); chrome.unref(); fixtureServer.kill('SIGTERM'); fixtureServer.unref();
if (!report.pass) process.exitCode = 1;
