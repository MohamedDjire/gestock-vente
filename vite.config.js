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
        // Liste des routes à intercepter (toutes les routes PHP/API concernées)
        const apiRoutes = [
          '/api-stock',
          '/api-stock/',
          '/api-stock/login.php',
          '/register.php',
          '/api_point_vente.php',
          '/api_forfait.php',
          '/api_entrepot.php',
          '/api_entreprise.php',
          '/api_fournisseur.php',
          '/api_vente.php',
          '/api_utilisateur.php',
          '/api_compta_ecritures.php',
          '/check_forfait.php',
          '/check_forfait_limits.php',
          '/api_stock_notification_settings.php',
          '/clients.php',
          '/api_produit.php'
        ];
        const handler = async (req, res, next) => {
          if (!req.url) return next();
          // Trouver la route correspondante
          const matchedRoute = apiRoutes.find(route => req.url.startsWith(route));
          if (!matchedRoute) return next();

          // Construction de l'URL cible
          let targetUrl;
          if (matchedRoute.startsWith('/api-stock')) {
            // Pour /api-stock et sous-routes
            const [pathPart, qsPart] = req.url.split('?');
            const path = pathPart.replace(/^\/api-stock\/?/, '') || '';
            const qs = qsPart ? '?' + qsPart : '';
            targetUrl = `${API_PROXY_TARGET}/api-stock/${path}${qs}`;
          } else {
            // Pour les autres routes PHP
            const [pathPart, qsPart] = req.url.split('?');
            const file = matchedRoute.replace(/^\//, '');
            const qs = qsPart ? '?' + qsPart : '';
            targetUrl = `${API_PROXY_TARGET}/api-stock/${file}${qs}`;
          }

          const origin = req.headers.origin || '*';
          try {
            const headers = {
              Accept: req.headers.accept || 'application/json',
              Host: new URL(API_PROXY_TARGET).host
            };
            if (req.headers.authorization) headers['Authorization'] = req.headers.authorization;
            if (req.headers['content-type']) headers['Content-Type'] = req.headers['content-type'];
            const opts = { method: req.method || 'GET', headers, redirect: 'follow' };
            if (['POST', 'PUT', 'PATCH'].includes(req.method) && parseInt(req.headers['content-length'] || '0', 10) > 0) {
              const body = await new Promise((resolve, reject) => {
                const chunks = [];
                req.on('data', (c) => chunks.push(c));
                req.on('end', () => resolve(Buffer.concat(chunks)));
                req.on('error', reject);
              });
              if (body && body.length) opts.body = body;
            }
            const response = await fetch(targetUrl, opts);
            const contentType = response.headers.get('content-type') || 'application/json';
            res.writeHead(response.status, {
              'Content-Type': contentType,
              'Access-Control-Allow-Origin': origin,
              'Access-Control-Allow-Credentials': 'true',
              'Access-Control-Allow-Methods': 'GET, POST, PUT, DELETE, OPTIONS',
              'Access-Control-Allow-Headers': 'Content-Type, Authorization, X-Auth-Token, X-Requested-With'
            });
            const buffer = await response.arrayBuffer();
            res.end(Buffer.from(buffer));
          } catch (err) {
            console.error('[API Proxy]', err.message);
            if (!res.headersSent) {
              res.writeHead(502, {
                'Content-Type': 'application/json',
                'Access-Control-Allow-Origin': origin
              });
              res.end(JSON.stringify({ success: false, message: 'Proxy error: ' + err.message }));
            }
          }
        };
        server.middlewares.stack.unshift({ route: '', handle: handler });
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
      ...[ '/api-stock/login.php', '/register.php', '/api-stock', '/api_compta_ecritures.php', '/api_point_vente.php', '/api_produit.php', '/api_fournisseur.php', '/api_vente.php', '/api_forfait.php', '/api_entrepot.php', '/api_entreprise.php', '/api_utilisateur.php', '/check_forfait.php', '/check_forfait_limits.php', '/api_stock_notification_settings.php', '/clients.php' ].reduce((acc, route) => {
        acc[route] = {
          target: 'https://aliadjame.com/api-stock',
          changeOrigin: true,
          secure: route === '/api_stock_notification_settings.php' ? true : false,
          ws: route === '/api_stock_notification_settings.php' ? false : undefined,
          timeout: route === '/api_stock_notification_settings.php' ? 30000 : undefined,
          rewrite: route === '/api-stock' ? (path => path.replace(/^\/api-stock/, '')) : undefined,
           // Ne PAS réécrire le chemin - garder login.php/login.php tel quel
          configure: (proxy, options) => {
            proxy.on('proxyReq', (proxyReq, req, res) => {
              console.log(`[Vite Proxy] ${req.method} ${req.url} -> ${proxyReq.path}`)
              proxyReq.setHeader('Host', 'aliadjame.com')
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
              proxyRes.headers['access-control-allow-origin'] = req.headers.origin || '*'
              proxyRes.headers['access-control-allow-methods'] = 'GET, POST, PUT, DELETE, OPTIONS'
              proxyRes.headers['access-control-allow-headers'] = 'Content-Type, Authorization, X-Auth-Token, X-Requested-With'
              proxyRes.headers['access-control-allow-credentials'] = 'true'
              console.log(`[Vite Proxy Response] ${req.url} -> Status: ${proxyRes.statusCode}`)
            })
          }
        }
        return acc
      }, {})
    }
  }
});
