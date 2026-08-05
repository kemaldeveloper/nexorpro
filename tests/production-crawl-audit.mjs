import { createRequire } from 'node:module';

const require = createRequire(import.meta.url);
const { JSDOM } = require('../source/remix-of-nexor-reliable-home-repairs-59-main/node_modules/jsdom');
const origin = 'https://nexorpro.ru';
const failures = [];
const warnings = [];
const pages = new Set([`${origin}/`]);
const sitemapQueue = [`${origin}/wp-sitemap.xml`];
const checkedResources = new Map();

const cleanUrl = value => {
  try {
    const url = new URL(value, origin);
    url.hash = '';
    return url.origin === origin ? url.href : null;
  } catch { return null; }
};
const fetchText = async url => {
  const response = await fetch(url, { redirect: 'follow', headers: { 'user-agent': 'Nexor production acceptance audit' } });
  return { response, text: await response.text() };
};
while (sitemapQueue.length) {
  const sitemap = sitemapQueue.shift();
  const { response, text } = await fetchText(sitemap);
  if (!response.ok) failures.push(`Sitemap ${sitemap}: HTTP ${response.status}`);
  for (const match of text.matchAll(/<loc>([^<]+)<\/loc>/g)) {
    const url = match[1].replaceAll('&amp;', '&');
    if (url.endsWith('.xml')) sitemapQueue.push(url); else pages.add(url);
  }
}

const pageResults = [];
const internalLinks = new Set();
const resources = new Set();
for (const url of pages) {
  const { response, text } = await fetchText(url);
  const result = { url, status: response.status, bytes: Buffer.byteLength(text), title: '', h1: 0, canonical: '', description: false, schemas: 0, images: 0 };
  if (response.status !== 200) failures.push(`${url}: HTTP ${response.status}`);
  if (result.bytes < 500) failures.push(`${url}: suspiciously short body (${result.bytes} bytes)`);
  const dom = new JSDOM(text);
  const document = dom.window.document;
  result.title = document.title.trim();
  result.h1 = document.querySelectorAll('h1').length;
  result.canonical = document.querySelector('link[rel="canonical"]')?.href || '';
  result.description = Boolean(document.querySelector('meta[name="description"]')?.content.trim());
  result.images = document.images.length;
  if (!result.title) failures.push(`${url}: missing title`);
  if (result.h1 !== 1) failures.push(`${url}: expected one H1, found ${result.h1}`);
  if (!result.canonical.startsWith(origin)) failures.push(`${url}: invalid/missing canonical`);
  if (!result.description) warnings.push(`${url}: missing meta description`);
  if (!document.querySelector('meta[property="og:image"]')?.content) warnings.push(`${url}: missing og:image`);
  if (text.includes('{{THEME_URI}}')) failures.push(`${url}: unreplaced THEME_URI placeholder`);
  if (/\b(?:href|src)=["']http:\/\//i.test(text)) failures.push(`${url}: mixed-content URL found`);
  const ids = [...document.querySelectorAll('[id]')].map(node => node.id).filter(Boolean);
  const duplicates = [...new Set(ids.filter((id, index) => ids.indexOf(id) !== index))];
  if (duplicates.length) warnings.push(`${url}: duplicate IDs ${duplicates.join(', ')}`);
  for (const script of document.querySelectorAll('script[type="application/ld+json"]')) {
    try { JSON.parse(script.textContent); result.schemas++; } catch { failures.push(`${url}: invalid JSON-LD`); }
  }
  for (const image of document.images) {
    if (!image.hasAttribute('alt')) warnings.push(`${url}: image without alt (${image.src})`);
    const src = cleanUrl(image.src); if (src) resources.add(src);
  }
  for (const node of document.querySelectorAll('script[src],link[rel="stylesheet"][href]')) {
    const src = cleanUrl(node.src || node.href); if (src) resources.add(src);
  }
  for (const node of document.querySelectorAll('[style]')) {
    for (const match of node.getAttribute('style').matchAll(/url\(\s*['"]?([^)'"\s]+)['"]?\s*\)/gi)) {
      const src = cleanUrl(match[1]); if (src) resources.add(src);
    }
  }
  for (const anchor of document.querySelectorAll('a[href]')) {
    const href = anchor.getAttribute('href');
    if (!href || href.startsWith('#') || /^(?:mailto:|tel:|javascript:)/i.test(href)) continue;
    const link = cleanUrl(href);
    if (link && !/\/wp-(?:admin|login)/.test(link)) internalLinks.add(link);
  }
  pageResults.push(result);
}

const check = async (url, type) => {
  if (checkedResources.has(url)) return checkedResources.get(url);
  try {
    const response = await fetch(url, { redirect: 'follow', headers: { range: 'bytes=0-0', 'user-agent': 'Nexor production acceptance audit' } });
    const value = { status: response.status, type: response.headers.get('content-type') || '' };
    checkedResources.set(url, value);
    if (!response.ok) failures.push(`${type} ${url}: HTTP ${response.status}`);
    return value;
  } catch (error) {
    failures.push(`${type} ${url}: ${error.message}`);
    return null;
  }
};
for (const link of internalLinks) await check(link, 'Internal link');
for (const resource of resources) await check(resource, 'Resource');

const search = await fetchText(`${origin}/?s=ремонт`);
const searchDoc = new JSDOM(search.text).window.document;
if (search.response.status !== 200) failures.push(`Search: HTTP ${search.response.status}`);
if (![...searchDoc.querySelectorAll('meta[name="robots"]')].some(meta => /noindex/i.test(meta.content))) failures.push('Search: missing noindex');
if (search.text.includes('nexor_lead')) failures.push('Search: private lead marker exposed');
const emptySearch = await fetchText(`${origin}/?s=`);
if (emptySearch.text.includes('Все результаты') || emptySearch.text.includes('Все страницы')) warnings.push('Empty search may expose all content');
const notFound = await fetchText(`${origin}/acceptance-audit-definitely-missing/`);
const notFoundDoc = new JSDOM(notFound.text).window.document;
if (notFound.response.status !== 404) failures.push(`404 route: HTTP ${notFound.response.status}`);
if (![...notFoundDoc.querySelectorAll('meta[name="robots"]')].some(meta => /noindex/i.test(meta.content))) failures.push('404 route: missing noindex');

const home = await fetchText(`${origin}/`);
const homeDoc = new JSDOM(home.text).window.document;
for (const marker of ['c99ae8eeb3386f97', 'mc.yandex.ru/metrika/tag.js', 'NexorSettings']) {
  if (!home.text.includes(marker)) failures.push(`Home: missing marker ${marker}`);
}
const headers = Object.fromEntries(['strict-transport-security','x-content-type-options','x-frame-options','referrer-policy'].map(name => [name, home.response.headers.get(name)]));
for (const [name, value] of Object.entries(headers)) if (!value) failures.push(`Home: missing security header ${name}`);

const duplicateTitles = pageResults.reduce((map, page) => map.set(page.title, [...(map.get(page.title) || []), page.url]), new Map());
for (const [title, urls] of duplicateTitles) if (urls.length > 1) failures.push(`Duplicate title "${title}": ${urls.join(', ')}`);

console.log(JSON.stringify({ summary: { sitemapPages: pages.size, internalLinks: internalLinks.size, resources: resources.size, failures: failures.length, warnings: warnings.length }, headers, pages: pageResults, failures, warnings }, null, 2));
if (failures.length) process.exitCode = 1;
