import { readFile, writeFile } from 'node:fs/promises';
import { createRequire } from 'node:module';

const require = createRequire(import.meta.url);
const esbuild = require('./source/remix-of-nexor-reliable-home-repairs-59-main/node_modules/esbuild');
const sourcePath = new URL('./source/remix-of-nexor-reliable-home-repairs-59-main/src/data/projects.ts', import.meta.url);
const outputPath = new URL('./package/wp-content/plugins/nexor-core/project-data.json', import.meta.url);
let source = await readFile(sourcePath, 'utf8');
source = source.replace(
  /import\s+(\w+)\s+from\s+['"]@\/assets\/([^'"]+)['"];?/g,
  (_match, name, asset) => `const ${name} = ${JSON.stringify(asset)};`,
);
const transformed = await esbuild.transform(source, { loader: 'ts', format: 'cjs', target: 'es2022' });
const module = { exports: {} };
new Function('module', 'exports', transformed.code)(module, module.exports);
const projects = module.exports.projects.map(project => ({
  slug: project.slug,
  title: project.title,
  location: project.location,
  area: project.area,
  area_display: project.areaDisplay,
  repair_type: project.repairType,
  repair_type_display: project.repairTypeDisplay,
  property_type: project.propertyType,
  duration: project.duration,
  hero_image: project.heroImage,
  focal_point: project.imageFocalPoint || 'center',
  gallery: project.images,
  task: project.task,
  works_done: project.worksDone,
  result: project.result,
  key_solutions: project.keySolutions,
  features: project.features,
  floor_plan: Boolean(project.hasFloorPlan),
  featured: Boolean(project.featured),
  seo_alt: project.seoAlt || project.title,
}));
await writeFile(outputPath, `${JSON.stringify(projects, null, 2)}\n`);
console.log(`Generated ${projects.length} projects: ${outputPath.pathname}`);
