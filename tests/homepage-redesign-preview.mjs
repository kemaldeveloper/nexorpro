import { spawn } from 'node:child_process';
import { createRequire } from 'node:module';
import { mkdir, readFile, rm, writeFile } from 'node:fs/promises';

const require = createRequire(import.meta.url);
const WebSocket = require('ws');
const outputDir = './artifacts/nexor-evidence';
const production = process.argv.includes('--production');
const css = await readFile(new URL('../package/wp-content/themes/nexor/assets/nexor.css', import.meta.url), 'utf8');
const port = 9797;
const profile = '/tmp/nexor-homepage-redesign-preview';
const sleep = ms => new Promise(resolve => setTimeout(resolve, ms));
await mkdir(outputDir, { recursive: true });
await rm(profile, { recursive: true, force: true });

const chrome = spawn('/usr/bin/chromium', ['--headless=new', '--no-sandbox', '--disable-dev-shm-usage', `--remote-debugging-port=${port}`, `--user-data-dir=${profile}`, 'about:blank'], { stdio: 'ignore' });
for (let i = 0; i < 80; i++) { try { if ((await fetch(`http://127.0.0.1:${port}/json/version`)).ok) break; } catch {} await sleep(100); }
const targets = await (await fetch(`http://127.0.0.1:${port}/json/list`)).json();
const ws = new WebSocket(targets.find(target => target.type === 'page').webSocketDebuggerUrl);
await new Promise((resolve, reject) => { ws.onopen = resolve; ws.onerror = reject; });
let id = 0;
const pending = new Map();
ws.onmessage = ({ data }) => { const message = JSON.parse(data); if (message.id && pending.has(message.id)) { const call = pending.get(message.id); pending.delete(message.id); message.error ? call.reject(new Error(message.error.message)) : call.resolve(message.result); } };
const send = (method, params = {}) => new Promise((resolve, reject) => { const callId = ++id; pending.set(callId, { resolve, reject }); ws.send(JSON.stringify({ id: callId, method, params })); });
const evaluate = async expression => (await send('Runtime.evaluate', { expression, returnByValue: true, awaitPromise: true })).result.value;
await send('Page.enable'); await send('Runtime.enable');
await send('Page.navigate', { url: `https://nexorpro.ru/?homepage-preview=${Date.now()}` });
for (let i = 0; i < 100 && await evaluate('document.readyState') !== 'complete'; i++) await sleep(100);

const budget = `<section id="budget-control" class="nexor-budget-section"><div class="container-nexor"><h2 class="heading-section">Как мы держим смету</h2><div class="nexor-budget__grid"><div class="nexor-budget__metric"><strong>0%</strong><p>отклонение итоговой сметы от первоначальной</p></div><ol class="nexor-budget__list"><li><span class="nexor-budget__icon" aria-hidden="true">1</span><div><h3>Считаем детально на замере</h3><p>Закладываем работы, которые другие забывают и потом выставляют дополнительно</p></div></li><li><span class="nexor-budget__icon" aria-hidden="true">2</span><div><h3>Фиксируем стоимость и объём</h3><p>В договоре до старта работ</p></div></li><li><span class="nexor-budget__icon" aria-hidden="true">3</span><div><h3>Любые изменения</h3><p>Только по вашему письменному согласию</p></div></li></ol></div></div></section>`;
const promotions = `<section id="promotions" class="nexor-enhancement-section"><div class="container-nexor"><h2 class="heading-section">Акции</h2><p class="nexor-promotions__disclaimer">Акции не суммируются и не комбинируются.</p><div class="nexor-card-grid">${[
  ['Визуализация в подарок', 'При заключении договора на ремонт под ключ'],
  ['Скидка 5% на работы', 'При заключении договора на ремонт под ключ в течение пяти дней после получения сметы'],
  ['Кондиционер в подарок', 'При заключении договора на ремонт под ключ от 2 000 000 ₽'],
  ['Телевизор в подарок', 'При заключении договора на ремонт под ключ от 3 000 000 ₽'],
].map(([title, text]) => `<article class="nexor-card"><h3>${title}</h3><p class="nexor-card__details">${text} Акция действует постоянно.</p><button type="button">Узнать условия</button></article>`).join('')}</div></div></section>`;

if (!production) await evaluate(`(()=>{document.querySelector('.nexor-exit')?.remove();const hero=document.querySelector('main>section');hero.classList.add('nexor-home-hero');const image=hero.querySelector('img');image.src='https://nexorpro.ru/wp-content/themes/nexor/assets/design-fullwidth-interior-t1Ou1Olm.webp';document.querySelector('#cases').insertAdjacentHTML('beforebegin',${JSON.stringify(budget)});document.querySelector('#about-company-nexor').insertAdjacentHTML('beforebegin',${JSON.stringify(promotions)});const style=document.createElement('style');style.textContent=${JSON.stringify(css)};document.head.append(style);return true;})()`);
await sleep(1200);

const capture = async (name, width, height, selector = null) => {
  await send('Emulation.setDeviceMetricsOverride', { width, height, deviceScaleFactor: 1, mobile: width < 600 });
  await sleep(300);
  let params = { format: 'png', fromSurface: true, captureBeyondViewport: true };
  if (selector) {
    const clip = await evaluate(`(()=>{const r=document.querySelector(${JSON.stringify(selector)}).getBoundingClientRect();return{x:0,y:r.top+scrollY,width:document.documentElement.scrollWidth,height:r.height,scale:1}})()`);
    params.clip = clip;
  } else params.clip = { x: 0, y: 0, width, height, scale: 1 };
  const shot = await send('Page.captureScreenshot', params);
  const path = `${outputDir}/${name}`;
  await writeFile(path, Buffer.from(shot.data, 'base64'));
  return path;
};
const captureComparisonGap = async name => {
  await send('Emulation.setDeviceMetricsOverride', { width: 1440, height: 900, deviceScaleFactor: 1, mobile: false });
  await sleep(300);
  const clip = await evaluate(`(()=>{const section=document.querySelector('.nexor-before-after')?.closest('section'),buttons=[...section.querySelectorAll('button')],cta=buttons.at(-1),heading=document.querySelector('#promotions h2'),start=cta.getBoundingClientRect().bottom+scrollY-24,end=heading.getBoundingClientRect().bottom+scrollY+36;return{x:0,y:start,width:document.documentElement.scrollWidth,height:end-start,scale:1}})()`);
  const shot = await send('Page.captureScreenshot', { format: 'png', fromSurface: true, captureBeyondViewport: true, clip });
  const path = `${outputDir}/${name}`;
  await writeFile(path, Buffer.from(shot.data, 'base64'));
  return path;
};
const captureServicesCases = async name => {
  await send('Emulation.setDeviceMetricsOverride', { width: 1440, height: 900, deviceScaleFactor: 1, mobile: false });
  await sleep(300);
  const clip = await evaluate(`(()=>{const services=document.querySelector('#main-services').getBoundingClientRect(),cases=document.querySelector('#cases').getBoundingClientRect(),start=services.top+scrollY,end=cases.bottom+scrollY;return{x:0,y:start,width:document.documentElement.scrollWidth,height:end-start,scale:1}})()`);
  const shot = await send('Page.captureScreenshot', { format: 'png', fromSurface: true, captureBeyondViewport: true, clip });
  const path = `${outputDir}/${name}`;
  await writeFile(path, Buffer.from(shot.data, 'base64'));
  return path;
};

const files = [];
const prefix = production ? 'production' : 'preview';
files.push(await capture(`20-home-${prefix}-desktop-1440x900.png`, 1440, 900));
const desktopAudit = await evaluate(`(()=>({heroImage:document.querySelector('.nexor-home-hero img')?.src||'',budget:!!document.querySelector('#budget-control'),promotions:document.querySelectorAll('#promotions .nexor-card').length,draftVisible:document.querySelector('#promotions')?.textContent.includes('Полный дизайн-проект')||false,overflow:document.documentElement.scrollWidth>document.documentElement.clientWidth}))()`);
if (production) files.push(await capture('27-timeline-production-desktop.png', 1440, 900, '#repair-timeline'));
if (production) files.push(await captureServicesCases('31-services-cases-production-desktop.png'));
if (production) files.push(await captureComparisonGap('29-comparison-bonuses-gap-production-desktop.png'));
if (production) files.push(await capture('30-stats-production-desktop.png', 1440, 900, 'main>section.bg-foreground:has(+ #faq)'));
files.push(await capture(`21-budget-${prefix}-desktop.png`, 1440, 900, '#budget-control'));
if (production) files.push(await capture('32-calculator-background-production-desktop.png', 1440, 900, '#calculator'));
if (production) files.push(await capture('33-additional-services-production-desktop.png', 1440, 900, '#additional-services'));
files.push(await capture(`22-promotions-${prefix}-desktop.png`, 1440, 900, '#promotions'));
let processDesktopInteractive = null;
if (production) { await evaluate(`document.querySelectorAll('.nexor-stage-card__nav [role="tab"]')[1]?.click()`); await sleep(350); processDesktopInteractive = await evaluate(`(()=>{const tabs=[...document.querySelectorAll('.nexor-stage-card__nav [role="tab"]')],slides=[...document.querySelectorAll('.nexor-stage-card__slide')];return{count:tabs.length,selected:tabs.map(tab=>tab.getAttribute('aria-selected')),secondVisible:!!slides[1]&&getComputedStyle(slides[1]).opacity==='1'}})()`); files.push(await capture('36-process-production-desktop.png', 1440, 900, '#stages')); }
await evaluate('scrollTo(0,0)');
files.push(await capture(`23-home-${prefix}-mobile-390x844.png`, 390, 844));
files.push(await capture(`24-budget-${prefix}-mobile.png`, 390, 844, '#budget-control'));
if (production) files.push(await capture('34-calculator-background-production-mobile.png', 390, 844, '#calculator'));
if (production) files.push(await capture('35-additional-services-production-mobile.png', 390, 844, '#additional-services'));
files.push(await capture(`25-promotions-${prefix}-mobile.png`, 390, 844, '#promotions'));
if (production) files.push(await capture('28-timeline-production-mobile.png', 390, 844, '#repair-timeline'));
if (production) files.push(await capture('37-process-production-mobile.png', 390, 844, '#stages'));
const mobileAudit = await evaluate(`(()=>{const tabs=[...document.querySelectorAll('.nexor-stage-card__nav [role="tab"]')];return{overflow:document.documentElement.scrollWidth>document.documentElement.clientWidth,viewport:document.documentElement.clientWidth,processSteps:tabs.length,activeStage:tabs.findIndex(tab=>tab.getAttribute('aria-selected')==='true')}})()`);
const contentAudit = production ? await evaluate(`(()=>{const timeline=document.querySelector('#repair-timeline'),cases=document.querySelector('#cases'),calculator=document.querySelector('#calculator'),additional=document.querySelector('#additional-services'),bonuses=document.querySelector('#promotions'),services=document.querySelector('#main-services'),comparison=document.querySelector('.nexor-before-after')?.closest('section'),search=document.querySelector('.nexor-search__submit'),stats=[...document.querySelectorAll('main>section.bg-foreground:has(+ #faq) .text-5xl.font-bold')],heroPromo=document.querySelector('.nexor-hero-promo'),hero=document.querySelector('.nexor-home-hero');return{sectionOrder:{servicesBeforeCases:!!(services?.compareDocumentPosition(cases)&Node.DOCUMENT_POSITION_FOLLOWING),casesBeforeCalculator:!!(cases?.compareDocumentPosition(calculator)&Node.DOCUMENT_POSITION_FOLLOWING),casesImmediatelyAfterServices:services?.nextElementSibling===cases},calculatorBackground:getComputedStyle(calculator).backgroundImage,additionalServices:{heading:additional?.querySelector('h2')?.textContent.trim()||'',intro:additional?.querySelector('.nexor-additional__intro')?.textContent.trim()||'',cards:additional?.querySelectorAll('.nexor-additional-card').length||0,titles:[...additional?.querySelectorAll('h3')||[]].map(x=>x.textContent.trim()),allHaveIncluded:[...additional?.querySelectorAll('.nexor-additional-card')||[]].every(card=>card.querySelectorAll('li').length>=4),allHaveBenefit:[...additional?.querySelectorAll('.nexor-additional-card')||[]].every(card=>!!card.querySelector('.nexor-additional-card__benefit')?.textContent.trim())},timelineHeading:timeline?.querySelector('h2')?.textContent.trim(),timelineRows:timeline?.querySelectorAll('tbody tr').length||0,timelineColumns:[...timeline?.querySelectorAll('thead th')||[]].map(x=>x.textContent.trim()),timelineNote:timeline?.querySelector('#repair-timeline-note')?.textContent.trim(),timelineAfterCases:!!(cases?.compareDocumentPosition(timeline)&Node.DOCUMENT_POSITION_FOLLOWING),timelineHeaderWeight:getComputedStyle(timeline?.querySelector('thead th')).fontWeight,timelineBodyWeight:getComputedStyle(timeline?.querySelector('tbody td')).fontWeight,comparisonPaddingBottom:getComputedStyle(comparison).paddingBottom,promotionsPaddingTop:getComputedStyle(bonuses).paddingTop,searchButton:{visibleText:search?.textContent.trim()||'',accessibleName:search?.getAttribute('aria-label')||'',hasIcon:!!search?.querySelector('svg')},heroPromotion:{visible:!!heroPromo,title:heroPromo?.querySelector('.nexor-hero-promo__copy strong')?.textContent.trim()||'',countdown:[...heroPromo?.querySelectorAll('[data-days],[data-hours],[data-minutes],[data-seconds]')||[]].map(x=>x.textContent),thresholdVisible:hero?.textContent.includes('5 000 000')||bonuses?.textContent.includes('5 000 000')||false},stats:stats.map(node=>({text:node.textContent.trim(),color:getComputedStyle(node).color})),bonusesHeading:bonuses?.querySelector('h2')?.textContent.trim(),bonusCards:bonuses?.querySelectorAll('.nexor-card').length||0,banner:!!bonuses?.querySelector('.nexor-bonus-banner'),countdown:bonuses?.querySelector('[data-days]')?.textContent||'',cosmeticHomepage:services?.textContent.includes('Косметический ремонт')||false}})()`) : null;
let calculatorInteractive = null;
if (production) { await evaluate(`document.querySelector('#calculator [data-next]')?.click()`); await sleep(150); calculatorInteractive = await evaluate(`!!document.querySelector('#calculator .nexor-calculator__progress')`); }
console.log(JSON.stringify({ files, desktopAudit, mobileAudit, contentAudit, processDesktopInteractive, calculatorInteractive }, null, 2));
ws.close(); chrome.kill('SIGKILL'); chrome.unref();
