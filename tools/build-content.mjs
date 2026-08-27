import fs from 'node:fs';
import path from 'node:path';

const projectRoot = process.cwd();
const dist = process.env.NEXOR_DIST_DIR || path.join(projectRoot, 'nexor-current-dist');
const out = path.join(projectRoot, 'package/wp-content/themes/nexor/content');
fs.mkdirSync(out, { recursive: true });

const routes = [
  ['', 'home'], ['projects', 'projects'], ['capital-remont', 'capital-remont'],
  ['design-remont', 'design-remont'], ['remont-v-novostroyke', 'remont-v-novostroyke'],
  ['cosmetic-remont', 'cosmetic-remont'], ['remont-domov-pod-klyuch', 'remont-domov-pod-klyuch'],
  ['remont-kvartir-pod-klyuch', 'remont-kvartir-pod-klyuch'], ['privacy', 'privacy'],
  ['consent', 'consent'],
];

const projectDir = path.join(dist, 'projects');
for (const name of fs.readdirSync(projectDir)) {
  if (name !== 'index.html' && fs.statSync(path.join(projectDir, name)).isDirectory()) {
    routes.push([`projects/${name}`, `project-${name}`]);
  }
}

const metadata = {};
for (const [route, name] of routes) {
  const filename = route ? path.join(dist, route, 'index.html') : path.join(dist, 'index.html');
  const html = fs.readFileSync(filename, 'utf8');
  const match = html.match(/<div id="root">([\s\S]*)<\/div>\s*<\/body>/);
  if (!match) throw new Error(`root content not found in ${filename}`);
  let body = match[1]
    .replaceAll('https://nexor-remont.ru', 'https://nexorpro.ru')
    .replaceAll('src="/assets/', 'src="{{THEME_URI}}/assets/')
    .replaceAll('href="/assets/', 'href="{{THEME_URI}}/assets/')
    .replaceAll('src="/favicon', 'src="{{THEME_URI}}/favicon')
    .replace(/<header\b[^>]*\bfixed\b[^>]*>[\s\S]*?<\/header>/i, '')
    .replace(/<footer\b[^>]*>[\s\S]*?<\/footer>/i, '');
  fs.writeFileSync(path.join(out, `${name}.html`), body);
  metadata[name] = {
    route: `/${route}`.replace(/\/$/, '') || '/',
    title: html.match(/<title(?:\s[^>]*)?>(.*?)<\/title>/)?.[1] || name,
    description: html.match(/<meta name="description" content="([^"]*)"/)?.[1] || '',
  };
}
fs.writeFileSync(path.join(out, 'metadata.json'), JSON.stringify(metadata, null, 2));
