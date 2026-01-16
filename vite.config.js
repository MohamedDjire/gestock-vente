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
    proxy: {
      '/index.php': {
        target: 'https://aliadjame.com/api-stock',
        changeOrigin: true,
        secure: false,
      },
      '/api-stock': {
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
      '/api_compta_ecritures.php': {
        target: 'https://aliadjame.com/api-stock',
        changeOrigin: true,
        secure: false,
      },
      '/api_compta_factures_clients.php': {
        target: 'https://aliadjame.com/api-stock',
        changeOrigin: true,
        secure: false,
      },
      '/api_compta_factures_fournisseurs.php': {
        target: 'https://aliadjame.com/api-stock',
        changeOrigin: true,
        secure: false,
      },
      '/api_compta_tresorerie.php': {
        target: 'https://aliadjame.com/api-stock',
        changeOrigin: true,
        secure: false,
      },
      '/api_compta_rapports.php': {
        target: 'https://aliadjame.com/api-stock',
        changeOrigin: true,
        secure: false,
      },
      '/api_compta_audit.php': {
        target: 'https://aliadjame.com/api-stock',
        changeOrigin: true,
        secure: false,
      },
      '/api_fournisseur.php': {
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
      }
    }
  }
});
