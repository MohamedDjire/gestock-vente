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
      // Proxy pour contourner CORS en développement
      // Toutes les requêtes vers /api-stock/* seront proxifiées vers https://aliadjame.com/api-stock/*
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
        secure: true,
        ws: false,
        timeout: 30000,
        // Ne PAS réécrire le chemin - garder /api-stock/login.php tel quel
        configure: (proxy, options) => {
          proxy.on('proxyReq', (proxyReq, req, res) => {
            // Log dans le terminal Vite (pas dans la console navigateur)
            console.log(`[Vite Proxy] ${req.method} ${req.url} -> ${proxyReq.path}`)
            // S'assurer que le Host est correct
            proxyReq.setHeader('Host', 'aliadjame.com')
            // Ajouter l'origine pour que le serveur puisse répondre correctement
            if (req.headers.origin) {
              proxyReq.setHeader('Origin', req.headers.origin)
            }
          })
          proxy.on('error', (err, req, res) => {
            console.error('[Vite Proxy Error]', err.message)
            if (res && !res.headersSent) {
              res.writeHead(500, {
                'Content-Type': 'application/json',
                'Access-Control-Allow-Origin': req.headers.origin || '*'
              })
              res.end(JSON.stringify({ error: 'Proxy error: ' + err.message }))
            }
          })
          proxy.on('proxyRes', (proxyRes, req, res) => {
            // Forcer les headers CORS même si le serveur ne les envoie pas
            proxyRes.headers['access-control-allow-origin'] = req.headers.origin || '*'
            proxyRes.headers['access-control-allow-methods'] = 'GET, POST, PUT, DELETE, OPTIONS'
            proxyRes.headers['access-control-allow-headers'] = 'Content-Type, Authorization, X-Auth-Token, X-Requested-With'
            proxyRes.headers['access-control-allow-credentials'] = 'true'
            
            console.log(`[Vite Proxy Response] ${req.url} -> Status: ${proxyRes.statusCode}`)
          })
        }
      }
    }
  }
});
