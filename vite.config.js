import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

const API_PROXY_TARGET = 'https://aliadjame.com'

// https://vite.dev/config/
export default defineConfig({
  plugins: [
    vue(),
    {
      name: 'api-stock-proxy',
      configureServer(server) {
        const handler = async (req, res, next) => {
          if (!req.url || !req.url.startsWith('/api-stock')) return next()
          const [pathPart, qsPart] = req.url.split('?')
          const path = pathPart.replace(/^\/api-stock\/?/, '') || ''
          const qs = qsPart ? '?' + qsPart : ''
          const targetUrl = `${API_PROXY_TARGET}/api-stock/${path}${qs}`
          const origin = req.headers.origin || '*'
          try {
            const headers = {
              Accept: req.headers.accept || 'application/json',
              Host: new URL(API_PROXY_TARGET).host
            }
            if (req.headers.authorization) headers['Authorization'] = req.headers.authorization
            if (req.headers['content-type']) headers['Content-Type'] = req.headers['content-type']
            const opts = { method: req.method || 'GET', headers, redirect: 'follow' }
            if (['POST', 'PUT', 'PATCH'].includes(req.method) && parseInt(req.headers['content-length'], 10) > 0) {
              const body = await new Promise((resolve, reject) => {
                const chunks = []
                req.on('data', (c) => chunks.push(c))
                req.on('end', () => resolve(Buffer.concat(chunks)))
                req.on('error', reject)
              })
              if (body && body.length) opts.body = body
            }
            const response = await fetch(targetUrl, opts)
            const contentType = response.headers.get('content-type') || 'application/json'
            res.writeHead(response.status, {
              'Content-Type': contentType,
              'Access-Control-Allow-Origin': origin,
              'Access-Control-Allow-Credentials': 'true',
              'Access-Control-Allow-Methods': 'GET, POST, PUT, DELETE, OPTIONS',
              'Access-Control-Allow-Headers': 'Content-Type, Authorization, X-Auth-Token, X-Requested-With'
            })
            const buffer = await response.arrayBuffer()
            res.end(Buffer.from(buffer))
          } catch (err) {
            console.error('[API Proxy]', err.message)
            if (!res.headersSent) {
              res.writeHead(502, {
                'Content-Type': 'application/json',
                'Access-Control-Allow-Origin': origin
              })
              res.end(JSON.stringify({ success: false, message: 'Proxy error: ' + err.message }))
            }
          }
        }
        server.middlewares.stack.unshift({ route: '', handle: handler })
      }
    }
  ],
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
      // /api-stock est géré par le plugin api-stock-proxy (middleware avec fetch + redirect: 'follow')
      // pour éviter que le navigateur reçoive une 302 vers aliadjame.com (CORS).
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
