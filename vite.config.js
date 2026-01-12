import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

// https://vite.dev/config/
export default defineConfig({
  plugins: [vue()],
  optimizeDeps: {
    include: ['chart.js/auto', 'jspdf', 'jspdf-autotable', 'xlsx']
  },
  resolve: {
    alias: {
      'chart.js': 'chart.js'
    }
  },
  // Proxy pour contourner CORS en développement (solution alternative)
  server: {
    proxy: {
      '/api_stock': {
        target: 'https://aliadjame.com',
        changeOrigin: true,
        secure: false,
        rewrite: (path) => path.replace(/^\/api_stock/, '/api_stock')
      }
    }
  }
});
