import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

// https://vite.dev/config/
export default defineConfig({
  plugins: [vue()],
  optimizeDeps: {
    include: ['chart.js/auto', 'jspdf', 'jspdf-autotable', 'xlsx', '@vuepic/vue-datepicker']
  },
  resolve: {
    alias: {
      'chart.js': 'chart.js'
    }
  },
  // Proxy pour contourner CORS en développement (solution alternative)
  server: {
    host: 'localhost',
    port: 5173,
    // Corriger le WebSocket HMR (400) : forcer host/port/protocole pour la poignée de main.
    // Si l'erreur persiste (p.ex. avec rolldown-vite), essayer: hmr: false
    // ou repasser à "vite" au lieu de "rolldown-vite" dans package.json.
    hmr: {
      protocol: 'ws',
      host: 'localhost',
      port: 5173,
      clientPort: 5173,
    },
    proxy: {
      // Un seul proxy: tout passe par /api-stock/*
      // Exemple: /api-stock/login.php -> https://aliadjame.com/api-stock/login.php
      '/api-stock': {
        target: 'https://aliadjame.com',
        changeOrigin: true,
        secure: false,
      }
    }
  }
});
