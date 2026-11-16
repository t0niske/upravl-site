// Превращает dist/ в предпросмотр для GitHub Pages: добавляет префикс к корневым ссылкам
// и закрывает от индексации. Запуск: node tools/preview.mjs /upravl-site _preview
import { cpSync, readdirSync, readFileSync, writeFileSync, statSync, rmSync } from 'node:fs';
import { join, extname } from 'node:path';

const base = (process.argv[2] || '').replace(/\/$/, '');
const out = process.argv[3] || '_preview';
rmSync(out, { recursive: true, force: true });
cpSync('dist', out, { recursive: true });

const walk = (d) => readdirSync(d).flatMap((f) => (statSync(join(d, f)).isDirectory() ? walk(join(d, f)) : [join(d, f)]));
for (const file of walk(out)) {
  const ext = extname(file);
  if (!['.html', '.css', '.xml', '.txt', '.webmanifest'].includes(ext)) continue;
  let s = readFileSync(file, 'utf8');
  // href="/x", src="/x", srcset="/x 1x, /y 2x", url(/x), action="/x" — кроме протокол-относительных //
  s = s.replace(/((?:href|src|action|content)=["'])\/(?!\/)/g, `$1${base}/`);
  s = s.replace(/(srcset=["'])([^"']+)/g, (_, a, v) => a + v.replace(/(^|,\s*)\/(?!\/)/g, `$1${base}/`));
  s = s.replace(/url\(\s*(["']?)\/(?!\/)/g, `url($1${base}/`);
  if (ext === '.html') s = s.replace('<head>', '<head><meta name="robots" content="noindex, nofollow">');
  writeFileSync(file, s);
}
writeFileSync(join(out, 'robots.txt'), 'User-agent: *\nDisallow: /\n');
writeFileSync(join(out, '.nojekyll'), '');
console.log(`preview ready in ${out} with base ${base}`);
