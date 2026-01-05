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
      '/api-stock': {
        target: 'https://aliadjame.com',
        changeOrigin: true,
        secure: true,
        rewrite: (path) => path.replace(/^\/api-stock/, '/api-stock')
      }
    }
  }
})
