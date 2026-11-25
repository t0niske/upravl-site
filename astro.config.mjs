// @ts-check
import { defineConfig } from 'astro/config';
import sitemap from '@astrojs/sitemap';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
  site: 'https://bankrotstvo70.ru',
  trailingSlash: 'always',
  devToolbar: { enabled: false },
  // без сжатия HTML: иначе теряется пробел на переносе строки перед ссылкой («проверьте<a>»)
  compressHTML: false,
  build: { format: 'directory', inlineStylesheets: 'auto' },
  // Страница МФЦ убрана по решению заказчика (24.11.2026): старые адреса ведут на банкротство физлиц
  redirects: { '/bankrotstvo-fizicheskih-lic/cherez-mfc/': '/bankrotstvo-fizicheskih-lic/', '/bankrotstvo-cherez-mfc/': '/bankrotstvo-fizicheskih-lic/' },
  integrations: [
    sitemap({
      filter: (page) => !page.includes('/spasibo/'),
      lastmod: new Date('2026-11-13'),
    }),
  ],
  vite: { plugins: [tailwindcss()] },
});
