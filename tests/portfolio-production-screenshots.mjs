import { spawn } from 'node:child_process';
import { createRequire } from 'node:module';
import { mkdtemp, mkdir, rm, writeFile } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { join } from 'node:path';

const require = createRequire(import.meta.url);
const WebSocket = require('ws');

const outputDir = './artifacts/portfolio';
const profile = await mkdtemp(join(tmpdir(), 'nexor-portfolio-'));
const port = 9811;
const sleep = milliseconds => new Promise(resolve => setTimeout(resolve, milliseconds));

await mkdir(outputDir, { recursive: true });

const chrome = spawn('/usr/bin/chromium', [
  '--headless=new',
  '--no-sandbox',
  '--disable-dev-shm-usage',
  '--disable-http2',
  '--disable-background-networking',
  '--hide-scrollbars',
  '--force-color-profile=srgb',
  '--font-render-hinting=medium',
  `--remote-debugging-port=${port}`,
  `--user-data-dir=${profile}`,
  '--window-size=1600,1000',
  'about:blank',
], { stdio: 'ignore' });

let ws;
let callId = 0;
const pending = new Map();
const browserErrors = [];
const manifest = [];

const close = async () => {
  if (ws?.readyState === WebSocket.OPEN) ws.close();
  const exited = new Promise(resolve => chrome.once('exit', resolve));
  chrome.kill('SIGKILL');
  chrome.unref();
  await Promise.race([exited, sleep(1500)]);
  await rm(profile, { recursive: true, force: true }).catch(() => {});
};

try {
  let ready = false;
  for (let attempt = 0; attempt < 120; attempt += 1) {
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
    ws.onopen = () => {
      clearTimeout(timer);
      resolve();
    };
    ws.onerror = error => {
      clearTimeout(timer);
      reject(error);
    };
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

  const send = (method, params = {}, timeoutMs = 20000) => new Promise((resolve, reject) => {
    const id = ++callId;
    const timer = setTimeout(() => {
      pending.delete(id);
      reject(new Error(`${method} timed out`));
    }, timeoutMs);
    pending.set(id, { resolve, reject, timer });
    ws.send(JSON.stringify({ id, method, params }));
  });

  const evaluate = async expression => {
    const response = await send('Runtime.evaluate', {
      expression,
      returnByValue: true,
      awaitPromise: true,
    });
    if (response.exceptionDetails) {
      throw new Error(response.exceptionDetails.text || 'Evaluation failed');
    }
    return response.result.value;
  };

  await send('Page.enable');
  await send('Runtime.enable');
  await send('Log.enable');
  await send('Emulation.setEmulatedMedia', {
    media: 'screen',
    features: [{ name: 'prefers-reduced-motion', value: 'reduce' }],
  });
  await send('Page.addScriptToEvaluateOnNewDocument', {
    source: `
      try {
        localStorage.setItem('nexor_exit_20260722-auto', String(Date.now() + 86400000));
        localStorage.setItem('nexor_exit_popup_closed', '1');
      } catch {}
    `,
  });

  const setViewport = async ({ width, height, dpr, mobile }) => {
    await send('Emulation.setDeviceMetricsOverride', {
      width,
      height,
      deviceScaleFactor: dpr,
      mobile,
      screenWidth: width,
      screenHeight: height,
    });
    await send('Emulation.setTouchEmulationEnabled', {
      enabled: mobile,
      maxTouchPoints: mobile ? 5 : 1,
    });
  };

  const installCaptureStyles = async () => {
    await evaluate(`(() => {
      let style = document.getElementById('nexor-portfolio-capture-style');
      if (!style) {
        style = document.createElement('style');
        style.id = 'nexor-portfolio-capture-style';
        document.head.append(style);
      }
      style.textContent = \`
        html { scroll-behavior: auto !important; }
        html, body { scrollbar-width: none !important; }
        body { cursor: none !important; }
        body::-webkit-scrollbar, html::-webkit-scrollbar { display: none !important; }
        *, *::before, *::after {
          animation: none !important;
          transition: none !important;
          caret-color: transparent !important;
        }
        body.nexor-portfolio-midpage header { display: none !important; }
        .nexor-exit, .nexor-lightbox, .nexor-bonus-modal { display: none !important; }
        .nexor-reveal { opacity: 1 !important; transform: none !important; }
      \`;
      return true;
    })()`);
  };

  const settle = async (selector = 'body', loadImages = true) => {
    await evaluate(`(async () => {
      if (document.fonts?.ready) await document.fonts.ready;
      const root = document.querySelector(${JSON.stringify(selector)}) || document.body;
      const allImages = [...root.querySelectorAll('img')];
      const images = ${String(true)} && ${String(loadImages)}
        ? allImages.filter(image => {
            if (root !== document.body && root !== document.documentElement) return true;
            const rect = image.getBoundingClientRect();
            return rect.bottom > -300 && rect.top < innerHeight + 300;
          })
        : [];
      images.forEach(image => { image.loading = 'eager'; });
      await Promise.race([
        Promise.all(images.map(image => {
          if (image.complete) return image.decode?.().catch(() => {}) || Promise.resolve();
          return new Promise(resolve => {
            image.addEventListener('load', resolve, { once: true });
            image.addEventListener('error', resolve, { once: true });
          });
        })),
        new Promise(resolve => setTimeout(resolve, 8000)),
      ]);
      await new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve)));
      return { images: images.length, width: innerWidth, height: innerHeight };
    })()`);
    await sleep(450);
  };

  const navigate = async url => {
    let lastError;
    for (let attempt = 0; attempt < 3; attempt += 1) {
      try {
        await send('Page.navigate', { url }, 25000);
        for (let tick = 0; tick < 220; tick += 1) {
          if (await evaluate(`document.readyState === 'complete' && !!document.querySelector('main')`)) break;
          await sleep(100);
        }
        const loaded = await evaluate(`document.readyState === 'complete' && !!document.querySelector('main') && document.body.innerText.length > 1000`);
        if (!loaded) throw new Error(`Page did not finish rendering: ${url}`);
        await installCaptureStyles();
        await settle('main', false);
        return;
      } catch (error) {
        lastError = error;
        await sleep(900);
      }
    }
    throw lastError;
  };

  const position = async ({ selector, top = 0, midpage = true }) => {
    const result = await evaluate(`(() => {
      document.body.classList.toggle('nexor-portfolio-midpage', ${String(midpage)});
      const element = document.querySelector(${JSON.stringify(selector)});
      if (!element) return null;
      const absoluteTop = element.getBoundingClientRect().top + scrollY;
      scrollTo(0, Math.max(0, absoluteTop - ${Number(top)}));
      const rect = element.getBoundingClientRect();
      return {
        selector: ${JSON.stringify(selector)},
        top: rect.top,
        bottom: rect.bottom,
        width: rect.width,
        height: rect.height,
        scrollY,
      };
    })()`);
    if (!result) throw new Error(`Missing screenshot target: ${selector}`);
    await settle(selector);
    return result;
  };

  const click = async selector => {
    const clicked = await evaluate(`(() => {
      const element = document.querySelector(${JSON.stringify(selector)});
      if (!element) return false;
      element.click();
      return true;
    })()`);
    if (!clicked) throw new Error(`Missing click target: ${selector}`);
    await sleep(350);
  };

  const capture = async ({ filename, title, url, viewport, target, prepare }) => {
    await setViewport(viewport);
    await navigate(url);
    if (prepare) await prepare();
    const positionData = target
      ? await position(target)
      : await position({ selector: 'body', top: 0, midpage: false });
    const pageState = await evaluate(`({
      url: location.href,
      title: document.title,
      overflow: document.documentElement.scrollWidth > document.documentElement.clientWidth + 1,
      popupVisible: (() => {
        const popup = document.querySelector('.nexor-exit');
        return !!popup && !popup.hidden && getComputedStyle(popup).display !== 'none';
      })(),
      fonts: document.fonts?.status || 'unknown'
    })`);
    const screenshot = await send('Page.captureScreenshot', {
      format: 'png',
      fromSurface: true,
      captureBeyondViewport: false,
    }, 40000);
    await writeFile(`${outputDir}/${filename}`, Buffer.from(screenshot.data, 'base64'));
    manifest.push({ filename, title, viewport, target: positionData, pageState });
    process.stdout.write(`Captured ${filename}\n`);
  };

  const home = 'https://nexorpro.ru/?portfolio-capture=2026-08-01';
  const designService = 'https://nexorpro.ru/design-remont/?portfolio-capture=2026-08-01';
  const desktop = { width: 1600, height: 1000, dpr: 2, mobile: false };
  const desktopEditorial = { width: 1600, height: 900, dpr: 2, mobile: false };
  const desktopCalculator = { width: 1600, height: 780, dpr: 2, mobile: false };
  const desktopBudget = { width: 1600, height: 840, dpr: 2, mobile: false };
  const desktopTall = { width: 1600, height: 1100, dpr: 2, mobile: false };
  const desktopAdditional = { width: 1600, height: 1320, dpr: 2, mobile: false };
  const desktopBonuses = { width: 1600, height: 940, dpr: 2, mobile: false };
  const mobileCompact = { width: 430, height: 820, dpr: 2, mobile: true };
  const mobile = { width: 430, height: 932, dpr: 2, mobile: true };

  await capture({
    filename: '01-homepage-hero-desktop.png',
    title: 'Главная — премиальный первый экран',
    url: home,
    viewport: desktop,
  });
  await capture({
    filename: '02-homepage-hero-mobile.png',
    title: 'Главная — мобильный первый экран',
    url: home,
    viewport: mobileCompact,
  });
  await capture({
    filename: '03-main-services-editorial.png',
    title: 'Основные услуги — editorial-композиция',
    url: home,
    viewport: desktopEditorial,
    target: { selector: '#main-services .nexor-section-heading', top: 38, midpage: true },
  });
  await capture({
    filename: '04-calculator-premium-glass.png',
    title: 'Калькулятор — premium glass',
    url: home,
    viewport: desktopCalculator,
    target: { selector: '#calculator', top: 0, midpage: true },
  });
  await capture({
    filename: '05-budget-control.png',
    title: 'Как мы держим смету',
    url: home,
    viewport: desktopBudget,
    target: { selector: '#budget-control .nexor-section-heading', top: 38, midpage: true },
  });
  await capture({
    filename: '06-work-stages-interactive.png',
    title: 'Этапы ремонта — активное состояние',
    url: home,
    viewport: desktopTall,
    prepare: async () => click('.nexor-process-experience__nav [role="tab"]:nth-child(3)'),
    target: { selector: '#work-stages .text-center', top: 34, midpage: true },
  });
  await capture({
    filename: '07-before-after-mobile.png',
    title: 'До и после — мобильный слайдер',
    url: home,
    viewport: mobileCompact,
    prepare: async () => click('#before-after .nexor-before-after-thumb:nth-child(3)'),
    target: { selector: '#before-after .nexor-before-after', top: 34, midpage: true },
  });
  await capture({
    filename: '08-additional-services-interactive.png',
    title: 'Дополнительные услуги — интерактивный стол',
    url: home,
    viewport: desktopAdditional,
    prepare: async () => click('#additional-services .nexor-service-hotspot:nth-of-type(3)'),
    target: { selector: '#additional-services .nexor-section-heading', top: 34, midpage: true },
  });
  await capture({
    filename: '09-client-bonuses.png',
    title: 'Бонусы для клиентов и таймер предложения',
    url: home,
    viewport: desktopBonuses,
    target: { selector: '#promotions .nexor-section-heading', top: 38, midpage: true },
  });
  await capture({
    filename: '10-design-remont-service-mobile.png',
    title: 'Страница дизайнерского ремонта — mobile',
    url: designService,
    viewport: mobile,
  });

  const uniqueErrors = [...new Set(browserErrors)].filter(error => !/favicon/i.test(error));
  const report = {
    production: 'https://nexorpro.ru/',
    capturedAt: new Date().toISOString(),
    screenshotCount: manifest.length,
    screenshots: manifest,
    browserErrors: uniqueErrors,
  };
  await writeFile(`${outputDir}/manifest.json`, `${JSON.stringify(report, null, 2)}\n`);

  if (manifest.length !== 10) throw new Error(`Expected 10 screenshots, captured ${manifest.length}`);
  if (manifest.some(item => item.pageState.overflow || item.pageState.popupVisible || item.pageState.fonts !== 'loaded')) {
    throw new Error('One or more screenshots failed layout/font/popup checks');
  }
  if (uniqueErrors.length) throw new Error(`Browser errors: ${uniqueErrors.join('; ')}`);
} finally {
  await close();
}
