import { readFile } from 'node:fs/promises';

const origin = 'https://nexorpro.ru';
const vectors = JSON.parse(await readFile(new URL('../docs/regression/calculator-vectors.json', import.meta.url)));
const home = await (await fetch(`${origin}/`)).text();
const nonce = home.match(/"nonce":"([^"]+)"/)?.[1];
if (!nonce) throw new Error('NexorSettings nonce not found');
const call = body => fetch(`${origin}/wp-json/nexor/v1/calculate`, {
  method: 'POST',
  headers: { 'content-type': 'application/json', 'x-wp-nonce': nonce, origin },
  body: JSON.stringify(body),
});
const results = [];
for (const [index, vector] of vectors.entries()) {
  const response = await call(vector);
  const data = await response.json();
  const pass = response.status === vector.status && data.min === vector.min && data.max === vector.max && typeof data.formatted === 'string';
  results.push({ index: index + 1, pass, expected: [vector.status, vector.min, vector.max], actual: [response.status, data.min, data.max] });
}
for (const [name, payload, expectedStatus] of [
  ['missing fields', {}, 422],
  ['invalid enum', { ...vectors[0], propertyType: 'invalid' }, 422],
  ['wrong priority count', { ...vectors[0], priorities: ['deadlines'] }, 422],
]) {
  const response = await call(payload);
  results.push({ name, pass: response.status === expectedStatus, expected: expectedStatus, actual: response.status });
}
const noNonce = await fetch(`${origin}/wp-json/nexor/v1/calculate`, { method: 'POST', headers: { 'content-type': 'application/json', origin }, body: JSON.stringify(vectors[0]) });
results.push({ name: 'missing nonce', pass: noNonce.status === 403, expected: 403, actual: noNonce.status });
console.log(JSON.stringify({ passed: results.filter(result => result.pass).length, total: results.length, results }, null, 2));
if (results.some(result => !result.pass)) process.exitCode = 1;
