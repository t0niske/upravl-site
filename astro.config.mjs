// @ts-check
import { defineConfig } from 'astro/config';
import sitemap from '@astrojs/sitemap';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
  site: 'https://upravl.ru',
  trailingSlash: 'always',
  devToolbar: { enabled: false },
  build: { format: 'directory', inlineStylesheets: 'auto' },
  integrations: [
    sitemap({
      filter: (page) => !page.includes('/spasibo/'),
      lastmod: new Date('2026-11-13'),
    }),
  ],
  vite: { plugins: [tailwindcss()] },
});
