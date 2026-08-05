import { mkdir, readFile, writeFile } from 'node:fs/promises';

const root = process.cwd();
const theme = `${root}/package/wp-content/themes/nexor`;
const output = `${root}/tasks/91437222-homepage-redesign/work/fixtures/service-pages-1.6.0`;
const slugs = [
  'remont-kvartir-pod-klyuch', 'capital-remont', 'design-remont',
  'remont-v-novostroyke', 'cosmetic-remont', 'remont-domov-pod-klyuch',
];

const shell = input => {
  let content = input.replace(/<main(?:\s+class="([^"]*)")?>/, (_, classes = '') => `<main class="nexor-service-page ${classes.trim()}">`);
  const mainPosition = content.indexOf('<main');
  const heroPosition = content.indexOf('<section', mainPosition);
  if (heroPosition < 0) return content;
  content = `${content.slice(0, heroPosition)}<section class="nexor-service-hero ${content.slice(heroPosition + '<section class="'.length)}`;
  let heroClose = content.indexOf('</section>', heroPosition);
  const eyebrow = '<p class="nexor-service-hero__eyebrow">Nexor · системный ремонт</p>';
  const h1Position = content.indexOf('<h1', heroPosition);
  if (h1Position >= 0 && h1Position < heroClose) {
    content = `${content.slice(0, h1Position)}${eyebrow}${content.slice(h1Position)}`;
    heroClose += eyebrow.length;
  }
  const heroCard = '<aside class="nexor-service-hero__card" aria-label="Условия работы Nexor"><p>Ответственность по договору</p><strong>Смета и сроки фиксируются до старта</strong><span>Инженер контролирует ключевые этапы, а вы принимаете и оплачиваете работы поэтапно.</span><a href="/#calculator">Рассчитать бюджет <span aria-hidden="true">↗</span></a></aside>';
  content = `${content.slice(0, heroClose)}${heroCard}${content.slice(heroClose)}`;
  heroClose += heroCard.length + '</section>'.length;
  const standards = '<section class="nexor-service-standards" aria-label="Стандарты работы Nexor"><div class="container-nexor"><article><span>01</span><strong>Инженерный замер</strong><p>Фиксируем исходные данные объекта.</p></article><article><span>02</span><strong>Подробная смета</strong><p>Согласовываем стоимость до старта.</p></article><article><span>03</span><strong>Контроль этапов</strong><p>Проверяем технологии и качество.</p></article><article><span>04</span><strong>Гарантия 3 года</strong><p>Закрепляем обязательства в договоре.</p></article></div></section>';
  return `${content.slice(0, heroClose)}${standards}${content.slice(heroClose)}`;
};

await mkdir(output, { recursive: true });
for (const slug of slugs) {
  const stored = await readFile(`${theme}/content/${slug}.html`, 'utf8');
  const content = shell(stored).replaceAll('{{THEME_URI}}', '/package/wp-content/themes/nexor');
  const html = `<!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>${slug} — Nexor 1.6.0</title><link rel="canonical" href="https://nexorpro.ru/${slug}/"><link rel="stylesheet" href="/package/wp-content/themes/nexor/assets/index-DfWs8OlI.css"><link rel="stylesheet" href="/package/wp-content/themes/nexor/assets/nexor.css"></head><body>${content}</body></html>`;
  await writeFile(`${output}/${slug}.html`, html);
}
console.log(output);
