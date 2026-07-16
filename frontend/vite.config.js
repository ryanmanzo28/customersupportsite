import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import fs from 'node:fs';
import path from 'node:path';

const servePublicPagesHtml = {
  name: 'serve-public-pages-html',
  configureServer(server) {
    server.middlewares.use((req, res, next) => {
      const url = req.url || '';
      if (!url.startsWith('/pages/') || !url.endsWith('.html')) {
        next();
        return;
      }

      const cleanUrl = url.split('?')[0];
      const filePath = path.join(server.config.root, 'public', cleanUrl.slice(1));
      if (!fs.existsSync(filePath)) {
        next();
        return;
      }

      res.setHeader('Content-Type', 'text/html; charset=utf-8');
      res.end(fs.readFileSync(filePath, 'utf8'));
    });
  },
};

export default defineConfig({
  plugins: [vue(), servePublicPagesHtml],
  server: {
    host: '0.0.0.0',
    port: 5173,
    proxy: {
      '/api': {
        target: process.env.VITE_PROXY_TARGET || 'http://backend',
        changeOrigin: true,
      },
    },
  },
});
