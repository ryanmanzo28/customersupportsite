import { defineConfig, loadEnv } from 'vite';
import vue from '@vitejs/plugin-vue';
import fs from 'node:fs';
import path from 'node:path';

const rootDirectory = path.resolve(__dirname);

// Vite's SPA fallback serves index.html for unknown .html routes. The static
// Pug pages live in public/pages, so serve them before that fallback can turn
// a request for /pages/home.html back into the redirecting root page.
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

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, path.resolve(rootDirectory, '..'), '');

  return {
    envDir: '..',
    plugins: [vue(), servePublicPagesHtml],
    // Avoid a stale/failed optimized dependency cache leaving the app shell
    // permanently loading with a 504 for /node_modules/.vite/deps/vue.js.
    optimizeDeps: {
      exclude: ['vue'],
    },
    build: {
      rollupOptions: {
        input: {
          home: path.resolve(rootDirectory, 'index.html'),
          app: path.resolve(rootDirectory, 'app.html'),
        },
      },
    },
    server: {
      host: '0.0.0.0',
      port: 5173,
      proxy: {
        '/api': {
          target: env.VITE_PROXY_TARGET || 'http://backend',
          changeOrigin: true,
        },
      },
    },
  };
});
