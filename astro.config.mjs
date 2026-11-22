// @ts-check
import { defineConfig } from 'astro/config';
import sitemap from '@astrojs/sitemap';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
  site: 'https://upravl.ru',
  trailingSlash: 'always',
  devToolbar: { enabled: false },
  // без сжатия HTML: иначе теряется пробел на переносе строки перед ссылкой («проверьте<a>»)
  compressHTML: false,
  build: { format: 'directory', inlineStylesheets: 'auto' },
  // МФЦ — отдельная услуга (22.11.2026): старый адрес внутри раздела физлиц ведёт на новый
  redirects: { '/bankrotstvo-fizicheskih-lic/cherez-mfc/': '/bankrotstvo-cherez-mfc/' },
  integrations: [
    sitemap({
      filter: (page) => !page.includes('/spasibo/'),
      lastmod: new Date('2026-11-13'),
    }),
  ],
  vite: { plugins: [tailwindcss()] },
});
