import { spawn, spawnSync } from 'node:child_process';
import { createRequire } from 'node:module';
import { mkdir, rm, writeFile } from 'node:fs/promises';

const root = process.cwd();
const outputDir = `${root}/artifacts/homepage-local`;
const fixturePath = '/tmp/nexor-homepage-redesign-fixture.html';
const profile = '/tmp/nexor-homepage-redesign-chrome';
const port = 9837;
const require = createRequire(import.meta.url);
const WebSocket = require('ws');
const sleep = ms => new Promise(resolve => setTimeout(resolve, ms));

const rendered = spawnSync('php', [`${root}/tests/render-homepage-redesign-fixture.php`], { encoding: 'utf8' });
if (rendered.status !== 0) throw new Error(rendered.stderr || 'Fixture renderer failed');
await mkdir(outputDir, { recursive: true });
await writeFile(fixturePath, rendered.stdout);
await rm(profile, { recursive: true, force: true });

const chrome = spawn('/usr/bin/chromium', [
  '--headless=new', '--no-sandbox', '--disable-dev-shm-usage', '--allow-file-access-from-files',
  `--remote-debugging-port=${port}`, `--user-data-dir=${profile}`, 'about:blank',
], { stdio: 'ignore' });
for (let index = 0; index < 100; index++) {
  try { if ((await fetch(`http://127.0.0.1:${port}/json/version`)).ok) break; } catch {}
  await sleep(100);
}
const targets = await (await fetch(`http://127.0.0.1:${port}/json/list`)).json();
const ws = new WebSocket(targets.find(target => target.type === 'page').webSocketDebuggerUrl);
await new Promise((resolve, reject) => { ws.onopen = resolve; ws.onerror = reject; });
let callId = 0;
const pending = new Map();
const runtimeErrors = [];
ws.onmessage = ({ data }) => {
  const message = JSON.parse(data);
  if (message.method === 'Runtime.exceptionThrown') runtimeErrors.push(message.params.exceptionDetails.text);
  if (message.id && pending.has(message.id)) {
    const call = pending.get(message.id); pending.delete(message.id);
    message.error ? call.reject(new Error(message.error.message)) : call.resolve(message.result);
  }
};
const send = (method, params = {}) => new Promise((resolve, reject) => {
  const id = ++callId; pending.set(id, { resolve, reject }); ws.send(JSON.stringify({ id, method, params }));
});
const evaluate = async expression => (await send('Runtime.evaluate', { expression, returnByValue: true, awaitPromise: true })).result.value;
await send('Page.enable'); await send('Runtime.enable');
await send('Page.navigate', { url: `file://${fixturePath}` });
for (let index = 0; index < 120 && await evaluate('document.readyState') !== 'complete'; index++) await sleep(100);
await evaluate(`Promise.race([Promise.all([...document.images].map(image=>image.complete?true:new Promise(resolve=>{image.addEventListener('load',resolve,{once:true});image.addEventListener('error',resolve,{once:true})}))),new Promise(resolve=>setTimeout(resolve,4000))])`);
await sleep(800);

const capture = async (name, width, height, fullPage = false) => {
  await send('Emulation.setDeviceMetricsOverride', { width, height, deviceScaleFactor: 1, mobile: width < 600 });
  await evaluate('scrollTo(0,0)'); await sleep(220);
  const params = { format: 'png', fromSurface: true, captureBeyondViewport: fullPage };
  if (fullPage) {
    await evaluate(`(async()=>{for(let y=0;y<document.documentElement.scrollHeight;y+=650){scrollTo(0,y);await new Promise(r=>setTimeout(r,25))}scrollTo(0,0)})()`);
    await sleep(250);
    const metrics = await send('Page.getLayoutMetrics');
    params.clip = { x: 0, y: 0, width, height: Math.ceil(metrics.cssContentSize.height), scale: 1 };
  }
  const shot = await send('Page.captureScreenshot', params);
  const path = `${outputDir}/${name}`;
  await writeFile(path, Buffer.from(shot.data, 'base64'));
  return path;
};
const captureRange = async (name, width, viewportHeight, startSelector, endSelector) => {
  await send('Emulation.setDeviceMetricsOverride', { width, height: viewportHeight, deviceScaleFactor: 1, mobile: width < 600 });
  await sleep(120);
  await evaluate(`(()=>{document.querySelectorAll('.nexor-reveal').forEach(node=>node.classList.add('is-visible'));const header=document.querySelector('header');if(header)header.style.visibility='hidden'})()`);
  const range = await evaluate(`(()=>{const start=document.querySelector(${JSON.stringify(startSelector)}),end=document.querySelector(${JSON.stringify(endSelector)});return{top:start.getBoundingClientRect().top+scrollY,bottom:end.getBoundingClientRect().bottom+scrollY}})()`);
  await evaluate(`Promise.race([Promise.all([...document.images].map(image=>{image.loading='eager';return image.decode?.().catch(()=>{})||true})),new Promise(resolve=>setTimeout(resolve,3500))])`);
  await evaluate(`(async()=>{for(let y=${Math.max(0, Math.floor(range.top))};y<${Math.ceil(range.bottom)};y+=500){scrollTo(0,y);await new Promise(r=>setTimeout(r,22))}})()`);
  await sleep(180);
  const shot = await send('Page.captureScreenshot', { format: 'png', fromSurface: true, captureBeyondViewport: true, clip: { x: 0, y: range.top, width, height: Math.ceil(range.bottom - range.top), scale: 1 } });
  const path = `${outputDir}/${name}`;
  await writeFile(path, Buffer.from(shot.data, 'base64'));
  await evaluate(`(()=>{const header=document.querySelector('header');if(header)header.style.visibility=''})()`);
  return path;
};

const viewports = [
  ['1440x900', 1440, 900], ['1024x900', 1024, 900], ['768x900', 768, 900], ['390x844', 390, 844],
];
const screenshots = [];
for (const [label, width, height] of viewports) {
  screenshots.push(await capture(`homepage-${label}.png`, width, height, false));
}
for (const [label, width, height] of [['1440',1440,900],['1024',1024,900],['768',768,900],['390',390,844]]) {
  screenshots.push(await captureRange(`sections-services-projects-${label}.png`,width,height,'#main-services','#cases'));
  screenshots.push(await captureRange(`sections-calculator-timeline-${label}.png`,width,height,'#calculator','#repair-timeline'));
  screenshots.push(await captureRange(`sections-system-process-comparison-${label}.png`,width,height,'#nexor-system','#before-after'));
  screenshots.push(await captureRange(`sections-additional-bonuses-${label}.png`,width,height,'#additional-services','#promotions'));
}

await send('Emulation.setDeviceMetricsOverride', { width: 1440, height: 900, deviceScaleFactor: 1, mobile: false });
await evaluate('scrollTo(0,0)');
const calculator = await evaluate(`(async()=>{const root=document.querySelector('#calculator');root.querySelector('[data-next]')?.click();for(let step=0;step<7;step++){await new Promise(r=>setTimeout(r,20));const options=[...root.querySelectorAll('.nexor-calculator__option')];options[0]?.click();if(step===6)options[1]?.click();if(step===6)root.querySelector('[data-result]')?.click();}await new Promise(r=>setTimeout(r,180));return{result:root.querySelector('.nexor-calculator__result')?.textContent.trim()||'',progress:root.querySelector('.nexor-calculator__progress span')?.style.width||''};})()`);
const interactions = await evaluate(`(()=>{
  const timeline=document.querySelector('[data-timeline-mode="capital"]');timeline?.click();
  const stagesRoot=document.querySelector('[data-nexor-stages]');const stageTabs=[...stagesRoot?.querySelectorAll('.nexor-stage-card__nav [role="tab"]')||[]];const processBefore=stageTabs.findIndex(tab=>tab.getAttribute('aria-selected')==='true');stageTabs[2]?.click();
  const slider=document.querySelector('.nexor-before-after');slider?.focus();slider?.dispatchEvent(new KeyboardEvent('keydown',{key:'ArrowRight',bubbles:true}));const sliderAfterKeyboard=slider?.getAttribute('aria-valuenow')||'';
  const secondThumb=[...document.querySelectorAll('.nexor-before-after-thumb')][1];secondThumb?.click();
  const service=[...document.querySelectorAll('.nexor-service-hotspot')][2];service?.click();
  const bonus=document.querySelector('[data-nexor-bonus-details]');bonus?.click();
  const heroCta=[...document.querySelectorAll('.nexor-home-hero button')].find(button=>button.textContent.includes('Рассчитать'));heroCta?.click();const leadModal=!document.querySelector('.nexor-modal')?.hidden;document.querySelector('.nexor-modal__close')?.click();
  const result={timeline:document.querySelector('#repair-timeline')?.dataset.timelineActive||'',processBefore,processAfter:stageTabs.findIndex(tab=>tab.getAttribute('aria-selected')==='true'),sliderAfterKeyboard,sliderValue:slider?.getAttribute('aria-valuenow')||'',thumbPressed:secondThumb?.getAttribute('aria-pressed')||'',servicePanel:document.querySelector('.nexor-service-panel.is-active')?.id||'',bonusModal:!document.querySelector('.nexor-bonus-modal')?.hidden,leadModal};
  document.querySelector('.nexor-bonus-modal__close')?.click();return result;
})()`);

const layout = [];
for (const [, width, height] of viewports) {
  await send('Emulation.setDeviceMetricsOverride', { width, height, deviceScaleFactor: 1, mobile: width < 600 });
  await sleep(100);
  layout.push(await evaluate(`(()=>{const overflow=[...document.querySelectorAll('body *')].filter(node=>{const style=getComputedStyle(node),rect=node.getBoundingClientRect();return style.position!=='fixed'&&(rect.left<-2||rect.right>innerWidth+2)&&rect.width>0}).slice(0,10).map(node=>({tag:node.tagName,className:node.className,left:Math.round(node.getBoundingClientRect().left),right:Math.round(node.getBoundingClientRect().right)}));const comparisonImages=[...document.querySelectorAll('.nexor-before-after img')],serviceCards=[...document.querySelectorAll('.nexor-service-card')].map(card=>{const cardRect=card.getBoundingClientRect(),linkRect=card.querySelector('a').getBoundingClientRect(),titleRect=card.querySelector('h3').getBoundingClientRect();return{title:card.querySelector('h3').textContent.trim(),cardHeight:Math.round(cardRect.height),linkHeight:Math.round(linkRect.height),titleTop:Math.round(titleRect.top-cardRect.top),titleHeight:Math.round(titleRect.height)}});return{width:innerWidth,scrollWidth:document.documentElement.scrollWidth,overflow,services:document.querySelectorAll('.nexor-service-card').length,serviceCards,projects:document.querySelectorAll('#cases article').length,timelineRows:document.querySelectorAll('#repair-timeline tbody tr').length,processTabs:document.querySelectorAll('[data-stage-index]').length,beforeAfterThumbs:document.querySelectorAll('.nexor-before-after-thumb').length,beforeAfterImagesLoaded:comparisonImages.length===2&&comparisonImages.every(image=>image.naturalWidth>0),serviceHotspots:document.querySelectorAll('.nexor-service-hotspot').length,bonusCards:document.querySelectorAll('.nexor-bonus-card').length,font:getComputedStyle(document.body).fontFamily,additionalBackground:getComputedStyle(document.querySelector('#additional-services')).backgroundColor,firstServiceTitleVisible:document.querySelector('.nexor-service-card h3')?.getBoundingClientRect().height>0}})()`));
}

await send('Emulation.setDeviceMetricsOverride', { width: 390, height: 844, deviceScaleFactor: 1, mobile: true });
const mobileMenuOpen = await evaluate(`(()=>{const trigger=document.querySelector('.nexor-mobile-trigger');trigger?.click();return!document.querySelector('.nexor-mobile-menu')?.hidden})()`);
await evaluate(`document.querySelector('.nexor-mobile-menu__close')?.click()`);
await sleep(600);
const mobileMenu = { open: mobileMenuOpen, closed: await evaluate(`document.querySelector('.nexor-mobile-menu')?.hidden===true`) };

const assertions = {
  noRuntimeErrors: runtimeErrors.length === 0,
  calculatorResult: Boolean(calculator.result),
  timelineSwitch: interactions.timeline === 'capital',
  processSwitch: interactions.processBefore === '01' && interactions.processAfter === '02',
  sliderKeyboard: interactions.sliderAfterKeyboard === '55',
  thumbnailSwitch: interactions.thumbPressed === 'true',
  additionalDrawer: interactions.servicePanel.includes('interior-design-project'),
  bonusDialog: interactions.bonusModal === true,
  leadDialog: interactions.leadModal === true,
  mobileMenu: mobileMenu.open && mobileMenu.closed,
  allViewportsNoHorizontalScroll: layout.every(item => item.scrollWidth <= item.width),
  requiredContent: layout.every(item => item.services === 5 && item.projects === 3 && item.timelineRows === 4 && item.processTabs === 5 && item.beforeAfterThumbs === 5 && item.serviceHotspots === 6 && item.bonusCards === 4),
  comparisonImagesLoaded: layout.every(item => item.beforeAfterImagesLoaded),
  additionalDarkTheme: layout.every(item => item.additionalBackground === 'rgb(24, 24, 22)'),
  serviceTitlesVisible: layout.every(item => item.firstServiceTitleVisible),
  localMontserrat: layout.every(item => item.font.startsWith('Montserrat')),
};
const report = { screenshots, assertions, calculator, interactions, mobileMenu, layout, runtimeErrors, pass: Object.values(assertions).every(Boolean) };
console.log(JSON.stringify(report, null, 2));
ws.close(); chrome.kill('SIGKILL'); chrome.unref();
if (!report.pass) process.exitCode = 1;
