import { spawn } from 'node:child_process';
import { createRequire } from 'node:module';
import { mkdir, rm, writeFile } from 'node:fs/promises';

const require = createRequire(import.meta.url);
const WebSocket = require('ws');
const port = 9796;
const profile = '/tmp/nexor-production-release-evidence';
const outputDir = './artifacts/production-1.6.0';
const sleep = ms => new Promise(resolve => setTimeout(resolve, ms));

await rm(profile, { recursive: true, force: true });
await mkdir(outputDir, { recursive: true });

const chrome = spawn('/usr/bin/chromium', [
  '--headless=new', '--no-sandbox', '--disable-dev-shm-usage', '--disable-http2',
  '--disable-background-networking', '--hide-scrollbars',
  `--remote-debugging-port=${port}`, `--user-data-dir=${profile}`,
  '--window-size=1440,900', 'about:blank',
], { stdio: 'ignore' });

let ws;
const pending = new Map();
const browserErrors = [];
let callId = 0;

const close = () => {
  if (ws?.readyState === WebSocket.OPEN) ws.close();
  chrome.kill('SIGKILL');
  chrome.unref();
};

try {
  let ready = false;
  for (let attempt = 0; attempt < 100; attempt++) {
    try {
      if ((await fetch(`http://127.0.0.1:${port}/json/version`)).ok) {
        ready = true;
        break;
      }
    } catch {}
    await sleep(100);
  }
  if (!ready) throw new Error('Chromium debugging endpoint did not start');

  const targets = await (await fetch(`http://127.0.0.1:${port}/json/list`)).json();
  const target = targets.find(item => item.type === 'page');
  if (!target) throw new Error('Chromium page target is missing');
  ws = new WebSocket(target.webSocketDebuggerUrl);
  await new Promise((resolve, reject) => {
    const timer = setTimeout(() => reject(new Error('WebSocket connection timed out')), 10000);
    ws.onopen = () => { clearTimeout(timer); resolve(); };
    ws.onerror = error => { clearTimeout(timer); reject(error); };
  });

  ws.onmessage = ({ data }) => {
    const message = JSON.parse(data);
    if (message.id && pending.has(message.id)) {
      const call = pending.get(message.id);
      pending.delete(message.id);
      clearTimeout(call.timer);
      if (message.error) call.reject(new Error(message.error.message));
      else call.resolve(message.result);
      return;
    }
    if (message.method === 'Runtime.exceptionThrown') {
      browserErrors.push(message.params.exceptionDetails.text || 'Runtime exception');
    }
    if (message.method === 'Log.entryAdded' && message.params.entry.level === 'error') {
      browserErrors.push(message.params.entry.text);
    }
  };

  const send = (method, params = {}, timeoutMs = 15000) => new Promise((resolve, reject) => {
    const id = ++callId;
    const timer = setTimeout(() => {
      pending.delete(id);
      reject(new Error(`${method} timed out`));
    }, timeoutMs);
    pending.set(id, { resolve, reject, timer });
    ws.send(JSON.stringify({ id, method, params }));
  });
  const evaluate = async expression => {
    const result = await send('Runtime.evaluate', {
      expression,
      returnByValue: true,
      awaitPromise: true,
    });
    if (result.exceptionDetails) throw new Error(result.exceptionDetails.text || 'Evaluation failed');
    return result.result.value;
  };

  await send('Page.enable');
  await send('Runtime.enable');
  await send('Log.enable');
  await send('Page.addScriptToEvaluateOnNewDocument', {
    source: `try{localStorage.setItem('nexor_exit_20260722-auto',String(Date.now()+86400000));}catch{}`,
  });

  const navigate = async url => {
    let lastError;
    for (let attempt = 0; attempt < 3; attempt++) {
      try {
        await send('Page.navigate', { url }, 20000);
        for (let tick = 0; tick < 160; tick++) {
          if (await evaluate(`document.readyState==='complete' && !!document.querySelector('main')`)) break;
          await sleep(100);
        }
        const loaded = await evaluate(`document.readyState==='complete' && !!document.querySelector('main') && document.body.innerText.length>1000`);
        if (!loaded) throw new Error('Homepage did not finish rendering');
        await sleep(1800);
        return;
      } catch (error) {
        lastError = error;
        await sleep(800);
      }
    }
    throw lastError;
  };

  const shotPositions = {};
  const screenshot = async (filename, selector = null) => {
    if (selector) {
      const position = await evaluate(`(()=>{const el=document.querySelector(${JSON.stringify(selector)});if(!el)return null;document.documentElement.style.scrollBehavior='auto';el.scrollIntoView({block:'start'});scrollBy(0,-12);return{scrollY,top:el.getBoundingClientRect().top,height:el.getBoundingClientRect().height};})()`);
      if (!position) throw new Error(`Missing screenshot target: ${selector}`);
      shotPositions[filename] = position;
      await sleep(500);
    } else {
      await evaluate(`document.documentElement.style.scrollBehavior='auto'`);
      await evaluate('scrollTo(0,0)');
      await sleep(300);
    }
    const result = await send('Page.captureScreenshot', {
      format: 'png',
      fromSurface: true,
    }, 30000);
    const path = `${outputDir}/${filename}`;
    await writeFile(path, Buffer.from(result.data, 'base64'));
    return path;
  };

  const desktopShots = {};
  await send('Emulation.setDeviceMetricsOverride', {
    width: 1440, height: 900, deviceScaleFactor: 1, mobile: false,
  });
  await navigate('https://nexorpro.ru/?production-evidence=1.6.0-desktop');
  desktopShots.home = await screenshot('homepage-1440x900.png');
  desktopShots.services = await screenshot('services-projects-1440x900.png', '#main-services');
  desktopShots.budget = await screenshot('budget-1440x900.png', '#budget-control');
  desktopShots.timeline = await screenshot('timeline-1440x900.png', '#repair-timeline');
  desktopShots.stages = await screenshot('work-stages-1440x900.png', '#stages');
  desktopShots.beforeAfter = await screenshot('before-after-1440x900.png', '#before-after');
  desktopShots.additional = await screenshot('additional-services-1440x900.png', '#additional-services');
  desktopShots.bonuses = await screenshot('bonuses-1440x900.png', '#promotions');

  const desktop = await evaluate(`(()=>{
    const rect=element=>{if(!element)return null;const box=element.getBoundingClientRect();return{x:box.x,y:box.y,width:box.width,height:box.height,right:box.right,bottom:box.bottom,text:element.textContent.replace(/\\s+/g,' ').trim().slice(0,80)}};
    const ids=[...document.querySelectorAll('main>section[id]')].map(section=>section.id);
    const featured=document.querySelector('.nexor-bonus-banner');
    const searchButton=document.querySelector('.nexor-search__submit');
    const nav=document.querySelector('.nexor-desktop-nav');
    const contact=document.querySelector('.nexor-desktop-contact');
    return {
      width:innerWidth,
      order:ids,
      overflow:document.documentElement.scrollWidth>document.documentElement.clientWidth+1,
      searchIcon:!!searchButton?.querySelector('svg'),
      searchLabel:searchButton?.getAttribute('aria-label')||'',
      stages:document.querySelectorAll('.nexor-stage-card__nav [role="tab"]').length,
      bonusCards:document.querySelectorAll('.nexor-bonus-card').length,
      featuredText:featured?.innerText.replace(/\\s+/g,' ').trim()||'',
      timerCells:featured?.querySelectorAll('[data-days],[data-hours],[data-minutes],[data-seconds]').length||0,
      popupSuppressed:document.querySelector('.nexor-exit')?.hidden===true,
      headerOverlapPixels:Math.max(0,(rect(nav?.querySelector('.nexor-search'))?.right||0)-(rect(contact)?.x||innerWidth)),
      navOverflowPixels:Math.max(0,(rect(nav?.querySelector('.nexor-search'))?.right||0)-(rect(nav)?.right||innerWidth)),
      headerBoxes:{
        nav:rect(nav),
        contact:rect(contact),
        search:rect(nav?.querySelector('.nexor-search')),
        searchInput:rect(nav?.querySelector('input[type="search"]')),
        searchButton:rect(searchButton),
        navItems:[...nav?.children||[]].map(rect),
        contactItems:[...contact?.querySelectorAll('a,button')||[]].map(rect),
      },
    };
  })()`);

  const mobileShots = {};
  await send('Emulation.setDeviceMetricsOverride', {
    width: 390, height: 844, deviceScaleFactor: 1, mobile: true,
  });
  await send('Emulation.setTouchEmulationEnabled', { enabled: true, maxTouchPoints: 5 });
  await navigate('https://nexorpro.ru/?production-evidence=1.6.0-mobile');
  mobileShots.home = await screenshot('homepage-390x844.png');
  mobileShots.budget = await screenshot('budget-390x844.png', '#budget-control');
  mobileShots.timeline = await screenshot('timeline-390x844.png', '#repair-timeline');
  mobileShots.stages = await screenshot('work-stages-390x844.png', '#stages');
  mobileShots.beforeAfter = await screenshot('before-after-390x844.png', '#before-after');
  mobileShots.additional = await screenshot('additional-services-390x844.png', '#additional-services');
  mobileShots.bonuses = await screenshot('bonuses-390x844.png', '#promotions');

  const mobile = await evaluate(`(()=>{
    const stage=document.querySelector('.nexor-stage-card');
    const stageRect=stage?.getBoundingClientRect();
    const timeline=document.querySelector('#repair-timeline');
    const activeCells=[...timeline?.querySelectorAll('tbody td')||[]].filter(cell=>getComputedStyle(cell).display!=='none');
    const trigger=document.querySelector('.nexor-mobile-trigger');
    trigger?.click();
    const menu=document.querySelector('.nexor-mobile-menu');
    return {
      width:innerWidth,
      overflow:document.documentElement.scrollWidth>document.documentElement.clientWidth+1,
      stageExists:!!stage,
      stageDisplay:stage?getComputedStyle(stage).display:'missing',
      stageHeight:stageRect?.height||0,
      stageTabs:stage?.querySelectorAll('[role="tab"]').length||0,
      stageSelected:stage?.querySelector('[role="tab"][aria-selected="true"]')?.textContent.trim()||'',
      timelineRows:timeline?.querySelectorAll('tbody tr').length||0,
      timelineVisibleCells:activeCells.length,
      budgetToggles:document.querySelectorAll('.nexor-budget__toggle').length,
      menuTrigger:!!trigger,
      menuOpen:!!menu&&!menu.hidden,
      popupSuppressed:document.querySelector('.nexor-exit')?.hidden===true,
    };
  })()`);

  const result = {
    productionUrl: 'https://nexorpro.ru/',
    release: '1.6.0',
    desktop,
    mobile,
    browserErrors: [...new Set(browserErrors)],
    shotPositions,
    screenshots: { desktop: desktopShots, mobile: mobileShots },
  };
  await writeFile(`${outputDir}/evidence.json`, `${JSON.stringify(result, null, 2)}\n`);
  console.log(JSON.stringify(result, null, 2));

  const expectedOrder = ['main-services', 'cases', 'calculator', 'budget-control', 'repair-timeline', 'nexor-system', 'stages', 'before-after', 'additional-services', 'promotions'];
  const ordered = expectedOrder.every((section, index) => desktop.order[index] === section);
  if (!ordered || desktop.overflow || desktop.headerOverlapPixels > 0 || desktop.navOverflowPixels > 0 || !desktop.searchIcon || desktop.searchLabel !== 'Найти' || desktop.stages !== 5 || desktop.bonusCards !== 4 || desktop.timerCells !== 4 || /5\s*000\s*000/.test(desktop.featuredText) || mobile.overflow || !mobile.stageExists || mobile.stageDisplay === 'none' || mobile.stageHeight < 300 || mobile.stageTabs !== 5 || mobile.timelineRows !== 4 || mobile.timelineVisibleCells !== 4 || mobile.budgetToggles !== 3 || !mobile.menuOpen || result.browserErrors.length) {
    process.exitCode = 1;
  }
} finally {
  close();
}
