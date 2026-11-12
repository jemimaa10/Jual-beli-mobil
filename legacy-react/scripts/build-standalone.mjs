// Membuat satu file HTML mandiri (JS, CSS, dan foto mobil ter-inline)
// yang bisa langsung dibuka dengan double-click tanpa server.
// Jalankan: npm run build:standalone
import { build } from 'vite';
import { readFile, writeFile, rm } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const outDir = path.join(root, 'dist-standalone');
const target = path.join(root, 'aplikasi-kasir-langsung-buka.html');

await build({
  root,
  logLevel: 'warn',
  build: {
    outDir,
    emptyOutDir: true,
    assetsInlineLimit: Number.MAX_SAFE_INTEGER, // foto mobil jadi data URI
    cssCodeSplit: false,
    modulePreload: false,
    rollupOptions: { output: { inlineDynamicImports: true } },
  },
});

let html = await readFile(path.join(outDir, 'index.html'), 'utf8');

const readAsset = (href) => readFile(path.join(outDir, href.replace(/^\.?\//, '')), 'utf8');

const scriptTag = html.match(/<script type="module" crossorigin src="([^"]+)"><\/script>/);
const cssTag = html.match(/<link rel="stylesheet" crossorigin href="([^"]+)">/);
if (!scriptTag || !cssTag) {
  throw new Error('Tag script/stylesheet hasil build Vite tidak ditemukan.');
}

const js = (await readAsset(scriptTag[1])).replace(/<\/script/gi, '<\\/script');
const css = await readAsset(cssTag[1]);

// Pakai fungsi pengganti agar pola "$&" / "$1" di dalam kode JS tidak ikut ditafsirkan
html = html
  .replace(cssTag[0], () => `<style>\n${css}\n</style>`)
  .replace(scriptTag[0], () => `<script type="module">\n${js}\n</script>`);

await writeFile(target, html, 'utf8');
await rm(outDir, { recursive: true, force: true });

const kb = Math.round(Buffer.byteLength(html) / 1024);
console.log(`✓ ${path.basename(target)} dibuat (${kb} KB)`);
