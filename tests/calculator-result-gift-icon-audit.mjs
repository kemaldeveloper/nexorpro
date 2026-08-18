import { chromium } from 'playwright';
import { mkdir, readFile, writeFile } from 'node:fs/promises';
import path from 'node:path';

const root = process.cwd();
const outputDir = `${root}/artifacts/calculator-result-gift-icon`;
const referencePath = `${root}/assets/reference-gift-icon.png`;
const targetUrl = process.env.NEXOR_AUDIT_URL || 'http://localhost:8080/';

await mkdir(outputDir, { recursive: true });

const browser = await chromium.launch({ headless: true });
const page = await browser.newPage({ viewport: { width: 1440, height: 900 } });

await page.goto(targetUrl, { waitUntil: 'networkidle' });
await page.evaluate(() => document.querySelector('#calculator')?.scrollIntoView({ block: 'center' }));

const rootLocator = page.locator('#calculator .nexor-calculator');
await rootLocator.locator('[data-next]').click();

for (let step = 0; step < 7; step++) {
  await page.waitForTimeout(80);
  const options = rootLocator.locator('.nexor-calculator__option');
  await options.first().click();
  if (step === 6) {
    await options.nth(1).click();
    await rootLocator.locator('[data-result]').click();
  }
}

await page.waitForSelector('#calculator .nexor-calculator.is-result .nexor-calculator__gift-icon', { timeout: 15000 });
await page.waitForTimeout(400);

const giftIcon = page.locator('#calculator .nexor-calculator__gift-icon').first();

const styles = await giftIcon.evaluate(node => {
  const svg = node.querySelector('svg');
  const computed = getComputedStyle(node);
  return {
    width: computed.width,
    height: computed.height,
    background: computed.backgroundColor,
    border: computed.border,
    borderRadius: computed.borderRadius,
    color: computed.color,
    boxShadow: computed.boxShadow,
    classes: node.className,
    svgStroke: svg?.querySelector('[stroke-width]')?.getAttribute('stroke-width') || svg?.getAttribute('stroke-width') || null,
    pathCount: svg?.querySelectorAll('path,rect').length || 0,
  };
});

let heroStyles = null;
try {
  await page.evaluate(() => window.scrollTo(0, 0));
  const heroIcon = page.locator('.nexor-hero-promo__icon').first();
  if (await heroIcon.count()) {
    await heroIcon.screenshot({ path: `${outputDir}/gift-icon-hero-promo.png`, scale: 'css' });
    heroStyles = await heroIcon.evaluate(node => {
      const computed = getComputedStyle(node);
      return {
        width: computed.width,
        height: computed.height,
        background: computed.backgroundColor,
        border: computed.border,
        borderRadius: computed.borderRadius,
        color: computed.color,
        boxShadow: computed.boxShadow,
      };
    });
  }
} catch {}

await giftIcon.screenshot({ path: `${outputDir}/gift-icon-actual.png`, scale: 'css' });
await page.locator('#calculator .nexor-calculator.is-result').screenshot({ path: `${outputDir}/calculator-result-1440.png`, scale: 'css' });

let reference = null;
try {
  reference = await readFile(referencePath);
  await writeFile(`${outputDir}/gift-icon-reference.png`, reference);
} catch {
  reference = await readFile(
    'C:/Users/DEV/.cursor/projects/d-projects-commercial-nexorpro/assets/c__Users_DEV_AppData_Roaming_Cursor_User_workspaceStorage_4a15b81e0203fcbb5e35b449cce6b8b9_images_image-eb03f5f8-22b7-469a-8f17-627a993bd8f3.png',
  );
  await writeFile(`${outputDir}/gift-icon-reference.png`, reference);
}

const report = {
  targetUrl,
  outputDir: path.relative(root, outputDir),
  styles,
  heroStyles,
  screenshots: {
    actual: 'artifacts/calculator-result-gift-icon/gift-icon-actual.png',
    reference: 'artifacts/calculator-result-gift-icon/gift-icon-reference.png',
    heroPromo: 'artifacts/calculator-result-gift-icon/gift-icon-hero-promo.png',
    result: 'artifacts/calculator-result-gift-icon/calculator-result-1440.png',
  },
};

await writeFile(`${outputDir}/report.json`, JSON.stringify(report, null, 2));
console.log(JSON.stringify(report, null, 2));

await browser.close();
