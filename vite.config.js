import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

// https://vite.dev/config/
export default defineConfig({
  plugins: [vue()],
  optimizeDeps: {
    include: [
      'chart.js/auto',
      'jspdf',
      'jspdf-autotable',
      'xlsx',
      '@vuepic/vue-datepicker'
    ]
  },
  resolve: {
    alias: {
      // 'chart.js': 'chart.js' // Alias inutile, à activer seulement si besoin spécifique
    }
  },
  server: {
    host: 'localhost',
    port: 5173,
    hmr: {
      protocol: 'ws',
      host: 'localhost',
      port: 5173,
      clientPort: 5173,
    },
    proxy: {
      '/api-stock': {
        target: 'https://aliadjame.com/api-stock',
        changeOrigin: true,
        secure: false,
      },
      '/api_compta_ecritures.php': {
        target: 'https://aliadjame.com/api-stock',
        changeOrigin: true,
        secure: false,
      },
      '/api_point_vente.php': {
        target: 'https://aliadjame.com/api-stock',
        changeOrigin: true,
        secure: false,
      },
      '/api_produit.php': {
        target: 'https://aliadjame.com/api-stock',
        changeOrigin: true,
        secure: false,
      },
      '/api_fournisseur.php': {
        target: 'https://aliadjame.com/api-stock',
        changeOrigin: true,
        secure: false,
      },
      '/api_vente.php': {
        target: 'https://aliadjame.com/api-stock',
        changeOrigin: true,
        secure: false,
      },
      '/api_forfait.php': {
        target: 'https://aliadjame.com/api-stock',
        changeOrigin: true,
        secure: false,
      },
      '/api_entrepot.php': {
        target: 'https://aliadjame.com/api-stock',
        changeOrigin: true,
        secure: false,
      },
      '/api_entreprise.php': {
        target: 'https://aliadjame.com/api-stock',
        changeOrigin: true,
        secure: false,
      },
      '/api_utilisateur.php': {
        target: 'https://aliadjame.com/api-stock',
        changeOrigin: true,
        secure: false,
      },
      '/check_forfait.php': {
        target: 'https://aliadjame.com/api-stock',
        changeOrigin: true,
        secure: false,
      },
      '/check_forfait_limits.php': {
        target: 'https://aliadjame.com/api-stock',
        changeOrigin: true,
        secure: false,
      },
      '/api_stock_notification_settings.php': {
        target: 'https://aliadjame.com/api-stock',
        changeOrigin: true,
        secure: false,
      }
    }
  }
});
