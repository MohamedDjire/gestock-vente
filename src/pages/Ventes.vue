<template>
  <div class="ventes-page">
    <div class="ventes-container">
      <!-- Colonne gauche : Panier -->
      <div class="cart-column">
        <div class="cart-header">
          <h2>Panier de Vente</h2>
          <button @click="clearCart" class="btn-clear" v-if="cart.length > 0">
            🗑️ Vider
          </button>
        </div>
        
        <div class="cart-content">
          <div v-if="cart.length === 0" class="empty-cart">
            <div class="empty-cart-icon">🛒</div>
            <p>Aucun produit dans le panier</p>
            <p class="empty-cart-hint">Sélectionnez des produits à droite pour commencer</p>
          </div>
          
          <div v-else class="cart-items">
            <div 
              v-for="(item, index) in cart" 
              :key="`${item.id_produit}-${index}`"
              class="cart-item"
            >
              <div class="cart-item-header">
                <div class="cart-item-info">
                  <h4 class="cart-item-name">{{ item.nom }}</h4>
                  <span class="cart-item-code">{{ item.code_produit }}</span>
                </div>
                <button @click="removeFromCart(index)" class="btn-remove">×</button>
              </div>
              
              <div class="cart-item-details">
                <div class="cart-item-row">
                  <span class="label">Prix unitaire:</span>
                  <span class="value">{{ formatPrice(item.prix_unitaire) }}</span>
                </div>
                <div class="cart-item-row">
                  <span class="label">Quantité:</span>
                  <div class="quantity-controls">
                    <button @click="decreaseQuantity(index)" class="qty-btn">−</button>
                    <input 
                      type="number" 
                      v-model.number="item.quantite" 
                      @input="updateCartItem(index)"
                      min="1"
                      :max="item.quantite_stock"
                      class="qty-input"
                    />
                    <button @click="increaseQuantity(index)" class="qty-btn">+</button>
                  </div>
                </div>
                <div class="cart-item-row">
                  <span class="label">Stock disponible:</span>
                  <span class="value" :class="{ 'low-stock': item.quantite_stock < 10 }">
                    {{ item.quantite_stock }}
                  </span>
                </div>
                <div class="cart-item-row total-row">
                  <span class="label">Sous-total:</span>
                  <span class="value total">{{ formatPrice(item.sous_total) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <div class="cart-footer" v-if="cart.length > 0">
          <div class="cart-summary">
            <div class="summary-row">
              <span class="summary-label">Nombre d'articles:</span>
              <span class="summary-value">{{ totalItems }}</span>
            </div>
            <div class="summary-row">
              <span class="summary-label">Sous-total:</span>
              <span class="summary-value">{{ formatPrice(subtotal) }}</span>
            </div>
            <div class="summary-row discount-row" v-if="discount > 0">
              <span class="summary-label">Remise:</span>
              <span class="summary-value discount">-{{ formatPrice(discount) }}</span>
            </div>
            <div class="summary-row total-row">
              <span class="summary-label">Total:</span>
              <span class="summary-value total">{{ formatPrice(total) }}</span>
            </div>
          </div>
          
          <div class="cart-actions">
            <button @click="showDiscountModal = true" class="btn-discount">
              💰 Remise
            </button>
            <button @click="processSale" class="btn-checkout" :disabled="processingSale || !selectedPointVente">
              {{ processingSale ? '⏳ Traitement...' : selectedPointVente ? '💳 Valider la vente' : '📍 Sélectionner un point de vente' }}
            </button>
          </div>
        </div>
      </div>
      
      <!-- Colonne droite : Liste des produits -->
      <div class="products-column">
        <div class="products-header">
          <div class="header-top">
            <div class="point-vente-info" v-if="selectedPointVente">
              <span class="pv-badge">🏪 {{ selectedPointVente.nom_point_vente }}</span>
            </div>
            <button 
              v-else 
              @click="showPointVenteModal = true" 
              class="btn-select-pv"
            >
              📍 Sélectionner un point de vente
            </button>
          </div>
          <div class="search-section">
            <input 
              v-model="searchQuery"
              type="text"
              placeholder="🔍 Rechercher un produit..."
              class="search-input"
            />
            <select v-model="selectedCategory" class="filter-select">
              <option value="">Toutes les catégories</option>
              <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
            </select>
          </div>
          <div class="products-stats">
            <span>{{ filteredProducts.length }} produit(s) disponible(s)</span>
          </div>
        </div>
        
        <div v-if="loadingProducts" class="loading-products">
          <div class="loading-spinner">⏳</div>
          <p>Chargement des produits...</p>
        </div>
        <div v-else-if="filteredProducts.length === 0" class="no-products">
          <div class="no-products-icon">📦</div>
          <p>Aucun produit disponible</p>
          <p class="no-products-hint" v-if="products.length === 0">Veuillez d'abord créer des produits</p>
          <p class="no-products-hint" v-else>Aucun produit ne correspond à votre recherche</p>
        </div>
        <div v-else class="products-grid">
          <div 
            v-for="product in filteredProducts" 
            :key="product.id_produit"
            class="product-card"
            :class="{ 'out-of-stock': product.quantite_stock === 0, 'low-stock': product.quantite_stock > 0 && product.quantite_stock <= product.seuil_minimum }"
            @click="addToCart(product)"
          >
            <div class="product-badge" v-if="product.quantite_stock === 0">Rupture</div>
            <div class="product-badge warning" v-else-if="product.quantite_stock <= product.seuil_minimum">Stock faible</div>
            
            <div class="product-image">
              <img 
                v-if="product.image" 
                :src="product.image" 
                :alt="product.nom"
                class="product-image-img"
              />
              <span v-else class="product-icon">📦</span>
            </div>
            
            <div class="product-info">
              <h3 class="product-name">{{ product.nom }}</h3>
              <div class="product-code">{{ product.code_produit }}</div>
              <div class="product-stock">
                Stock: <strong>{{ product.quantite_stock }}</strong>
              </div>
            </div>
            
            <div class="product-price">
              <div class="price-label">Prix de vente</div>
              <div class="price-value">{{ formatPrice(product.prix_vente) }}</div>
            </div>
            
            <button class="product-add-btn" :disabled="product.quantite_stock === 0">
              {{ product.quantite_stock === 0 ? 'Rupture' : '+ Ajouter' }}
            </button>
          </div>
        </div>
        
        <div v-if="filteredProducts.length === 0" class="no-products">
          <p>Aucun produit trouvé</p>
        </div>
      </div>
    </div>
    
    <!-- Modal Confirmation Vider Panier -->
    <div v-if="showClearCartModal" class="modal-overlay" @click.self="cancelClearCart">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>Vider le panier</h3>
          <button class="modal-close" @click="cancelClearCart">×</button>
        </div>
        <div class="modal-body">
          <p>Êtes-vous sûr de vouloir vider le panier ?</p>
          <p class="modal-warning">Cette action est irréversible et supprimera tous les produits du panier.</p>
        </div>
        <div class="modal-actions">
          <button class="btn-secondary" @click="cancelClearCart">Annuler</button>
          <button class="btn-primary" style="background: #dc2626;" @click="confirmClearCart">Vider le panier</button>
        </div>
      </div>
    </div>
    
    <!-- Modal Remise -->
    <div v-if="showDiscountModal" class="modal-overlay" @click.self="showDiscountModal = false">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>Appliquer une remise</h3>
          <button class="modal-close" @click="showDiscountModal = false">×</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Type de remise</label>
            <select v-model="discountType" class="form-input">
              <option value="percent">Pourcentage (%)</option>
              <option value="amount">Montant fixe (FCFA)</option>
            </select>
          </div>
          <div class="form-group">
            <label>Valeur</label>
            <input 
              v-model.number="discountValue" 
              type="number" 
              :min="0"
              :max="discountType === 'percent' ? 100 : subtotal"
              class="form-input"
              placeholder="0"
            />
          </div>
          <div class="discount-preview" v-if="discountValue > 0">
            <div class="preview-row">
              <span>Sous-total:</span>
              <span>{{ formatPrice(subtotal) }}</span>
            </div>
            <div class="preview-row">
              <span>Remise:</span>
              <span class="discount">-{{ formatPrice(calculateDiscount()) }}</span>
            </div>
            <div class="preview-row total">
              <span>Total après remise:</span>
              <span>{{ formatPrice(subtotal - calculateDiscount()) }}</span>
            </div>
          </div>
        </div>
        <div class="modal-actions">
          <button class="btn-secondary" @click="showDiscountModal = false">Annuler</button>
          <button class="btn-primary" @click="applyDiscount">Appliquer</button>
        </div>
      </div>
    </div>
    
    <!-- Modal Sélection Point de Vente -->
    <div v-if="showPointVenteModal" class="modal-overlay" @click.self="showPointVenteModal = false">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>Sélectionner le point de vente</h3>
          <button class="modal-close" @click="showPointVenteModal = false">×</button>
        </div>
        <div class="modal-body">
          <div v-if="loadingPointsVente" class="loading">Chargement...</div>
          <div v-else-if="pointsVente.length === 0" class="empty-state">
            <p>Aucun point de vente disponible</p>
          </div>
          <div v-else class="points-vente-list">
            <div 
              v-for="pv in pointsVente" 
              :key="pv.id_point_vente"
              class="point-vente-item"
              @click="selectPointVente(pv)"
            >
              <div class="pv-icon">🏪</div>
              <div class="pv-info">
                <div class="pv-name">{{ pv.nom_point_vente }}</div>
                <div class="pv-address" v-if="pv.adresse">{{ pv.adresse }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Modal Reçu de Vente -->
    <div v-if="showReceiptModal && lastSaleReceipt" class="modal-overlay receipt-overlay" @click.self="closeReceipt">
      <div class="receipt-modal" @click.stop>
        <div class="receipt-header">
          <h2>Reçu de Vente</h2>
          <div class="receipt-actions">
            <button @click="printReceipt" class="btn-print">🖨️ Imprimer</button>
            <button @click="closeReceipt" class="btn-close-receipt">×</button>
          </div>
        </div>
        <div class="receipt-body">
          <div class="receipt-info">
            <div class="receipt-line">
              <span class="label">Date:</span>
              <span class="value">{{ lastSaleReceipt.date }}</span>
            </div>
            <div class="receipt-line">
              <span class="label">Point de vente:</span>
              <span class="value">{{ lastSaleReceipt.point_vente }}</span>
            </div>
            <div class="receipt-line">
              <span class="label">N° Vente:</span>
              <span class="value">#{{ lastSaleReceipt.id_vente }}</span>
            </div>
          </div>
          
          <div class="receipt-products">
            <div class="receipt-table-header">
              <div class="col-name">Produit</div>
              <div class="col-qty">Qté</div>
              <div class="col-price">Prix</div>
              <div class="col-total">Total</div>
            </div>
            <div 
              v-for="(produit, index) in lastSaleReceipt.produits" 
              :key="index"
              class="receipt-table-row"
            >
              <div class="col-name">
                <div class="product-name">{{ produit.nom }}</div>
                <div class="product-code">{{ produit.code }}</div>
              </div>
              <div class="col-qty">{{ produit.quantite }}</div>
              <div class="col-price">{{ formatPrice(produit.prix_unitaire) }}</div>
              <div class="col-total">{{ formatPrice(produit.sous_total) }}</div>
            </div>
          </div>
          
          <div class="receipt-totals">
            <div class="receipt-total-row">
              <span class="label">Sous-total:</span>
              <span class="value">{{ formatPrice(lastSaleReceipt.sous_total) }}</span>
            </div>
            <div class="receipt-total-row" v-if="lastSaleReceipt.remise > 0">
              <span class="label">Remise:</span>
              <span class="value discount">-{{ formatPrice(lastSaleReceipt.remise) }}</span>
            </div>
            <div class="receipt-total-row final">
              <span class="label">Total:</span>
              <span class="value">{{ formatPrice(lastSaleReceipt.total) }}</span>
            </div>
          </div>
          
          <div class="receipt-footer">
            <p>Merci de votre achat !</p>
            <p class="receipt-note">Ce reçu a été enregistré dans l'historique des ventes</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { apiService } from '../composables/Api/apiService.js'
import { createEcriture } from '../composables/api/apiCompta'
import { useAuthStore } from '../stores/auth.js'
import { useCurrency } from '../composables/useCurrency.js'

const authStore = useAuthStore()
const { formatPrice } = useCurrency()

// Vérifier si l'utilisateur est admin
const isAdmin = computed(() => {
  const user = authStore.user
  if (!user) return false
  // Vérifier le rôle (peut être 'Admin', 'admin', 'superadmin', etc.)
  const role = String(user.role || user.user_role || '').toLowerCase()
  return role === 'admin' || role === 'superadmin'
})

// Données
const products = ref([])
const cart = ref([])
const pointsVente = ref([])
const selectedPointVente = ref(null)
const searchQuery = ref('')
const selectedCategory = ref('')
const categories = ref([])
const discount = ref(0)
const discountType = ref('percent')
const discountValue = ref(0)
const showDiscountModal = ref(false)
const showPointVenteModal = ref(false)
const loadingPointsVente = ref(false)
const processingSale = ref(false)

// Computed
const filteredProducts = computed(() => {
  // Ne pas filtrer par actif ici car c'est déjà fait dans loadProducts
  let filtered = products.value
  
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(p => 
      p.nom?.toLowerCase().includes(query) ||
      p.code_produit?.toLowerCase().includes(query)
    )
  }
  
  if (selectedCategory.value) {
    filtered = filtered.filter(p => p.categorie === selectedCategory.value)
  }
  
  return filtered.sort((a, b) => a.nom.localeCompare(b.nom))
})

const totalItems = computed(() => {
  return cart.value.reduce((sum, item) => sum + item.quantite, 0)
})

const subtotal = computed(() => {
  // Calculer explicitement : prix_unitaire * quantite pour chaque produit
  return cart.value.reduce((sum, item) => {
    const itemTotal = (item.prix_unitaire || 0) * (item.quantite || 0)
    return sum + itemTotal
  }, 0)
})

const total = computed(() => {
  const calculatedSubtotal = subtotal.value
  const calculatedDiscount = discount.value || 0
  return Math.max(0, calculatedSubtotal - calculatedDiscount)
})

// Méthodes
// formatPrice est maintenant fourni par useCurrency()

const loadingProducts = ref(false)

const loadProducts = async () => {
  loadingProducts.value = true
  try {
    const user = authStore.user
    
    // Si les permissions sont vides, essayer de les recharger depuis l'API
    if (user && !isAdmin.value && (!user.permissions_entrepots || user.permissions_entrepots.length === 0)) {
      console.warn('⚠️ [Ventes] Permissions vides, rechargement depuis l\'API...')
      try {
        const userResponse = await apiService.get(`/index.php?action=single&id=${user.id_utilisateur || user.id}`)
        console.log('📥 [Ventes] Réponse API utilisateur:', userResponse)
        if (userResponse && userResponse.success && userResponse.data) {
          console.log('✅ [Ventes] Utilisateur rechargé:', userResponse.data)
          // Mettre à jour les permissions dans le store avec setAuthData pour sauvegarder dans localStorage
          if (userResponse.data.permissions_entrepots && Array.isArray(userResponse.data.permissions_entrepots)) {
            authStore.setAuthData(authStore.token, {
              ...authStore.user,
              permissions_entrepots: userResponse.data.permissions_entrepots,
              permissions_points_vente: userResponse.data.permissions_points_vente || authStore.user.permissions_points_vente || []
            })
            console.log('✅ [Ventes] Permissions mises à jour dans le store:', authStore.user.permissions_entrepots)
          } else {
            console.warn('⚠️ [Ventes] Permissions toujours vides après rechargement')
          }
        }
      } catch (error) {
        console.error('❌ [Ventes] Erreur lors du rechargement des permissions:', error)
      }
    }
    
    let url = '/api_produit.php?action=all'
    
    console.log('👤 [Ventes] User complet:', JSON.stringify(authStore.user, null, 2))
    console.log('👤 [Ventes] User:', user?.username, 'isAdmin:', isAdmin.value)
    console.log('👤 [Ventes] permissions_entrepots:', user?.permissions_entrepots)
    console.log('👤 [Ventes] permissions_entrepots type:', typeof user?.permissions_entrepots)
    console.log('👤 [Ventes] permissions_entrepots isArray:', Array.isArray(user?.permissions_entrepots))
    console.log('👤 [Ventes] permissions_entrepots length:', user?.permissions_entrepots?.length)
    console.log('👤 [Ventes] role:', user?.role)
    
    // Si l'utilisateur n'est pas admin, passer les IDs d'entrepôts à l'API
    if (user && !isAdmin.value) {
      // Pour les agents, ils DOIVENT avoir des permissions d'entrepôts
      if (user.permissions_entrepots && Array.isArray(user.permissions_entrepots) && user.permissions_entrepots.length > 0) {
        const entrepotIds = user.permissions_entrepots.map(id => parseInt(id)).filter(id => !isNaN(id) && id > 0)
        if (entrepotIds.length > 0) {
          url += '&id_entrepots=' + entrepotIds.join(',')
          console.log('🏭 [Ventes] Agent - URL avec filtrage:', url)
          console.log('🏭 [Ventes] Agent - IDs entrepôts:', entrepotIds)
        } else {
          console.warn('⚠️ [Ventes] Agent - Aucun ID d\'entrepôt valide')
          // Si l'agent n'a pas d'IDs valides, ne pas charger de produits
          products.value = []
          categories.value = []
          loadingProducts.value = false
          return
        }
      } else {
        console.warn('⚠️ [Ventes] Agent - Aucune permission d\'entrepôt, aucun produit chargé')
        // Si l'agent n'a pas de permissions, ne pas charger de produits
        products.value = []
        categories.value = []
        loadingProducts.value = false
        return
      }
    } else {
      console.log('✅ [Ventes] Admin - Pas de filtre, tous les produits')
    }
    
    console.log('🌐 [Ventes] Appel API:', url)
    const response = await apiService.get(url)
    console.log('📦 [Ventes] Réponse API complète:', JSON.stringify(response, null, 2))
    
    if (response && response.success && response.data) {
      let allProducts = Array.isArray(response.data) ? response.data.map(p => ({
        ...p,
        categorie: p.id_categorie || 'Non catégorisé',
        actif: p.actif === 1 || p.actif === true || p.actif === '1'
      })) : []
      
      console.log('📦 [Ventes] Total produits reçus de l\'API:', allProducts.length)
      
      // Afficher quelques exemples de produits pour debug
      if (allProducts.length > 0) {
        console.log('📦 [Ventes] Exemples de produits (3 premiers):', allProducts.slice(0, 3).map(p => ({
          nom: p.nom,
          entrepot: p.entrepot,
          actif: p.actif
        })))
      }
      
      // Filtrer par produits actifs
      allProducts = allProducts.filter(p => p.actif === 1 || p.actif === true || p.actif === '1')
      
      console.log('📦 [Ventes] Produits actifs après filtrage:', allProducts.length)
      
      // Afficher les entrepôts des produits pour vérification
      if (allProducts.length > 0) {
        const entrepotsUniques = [...new Set(allProducts.map(p => p.entrepot).filter(Boolean))]
        console.log('📦 [Ventes] Entrepôts uniques dans les produits reçus:', entrepotsUniques)
      } else {
        console.warn('⚠️ [Ventes] AUCUN PRODUIT ACTIF TROUVÉ!')
        if (response.data && response.data.length > 0) {
          console.warn('⚠️ [Ventes] Produits inactifs trouvés:', response.data.filter(p => !(p.actif === 1 || p.actif === true || p.actif === '1')).length)
        }
      }
      
      products.value = allProducts
      
      // Extraire les catégories uniques
      const uniqueCategories = [...new Set(products.value.map(p => p.categorie))]
      categories.value = uniqueCategories.filter(c => c)
      console.log('✅ [Ventes] Produits finaux chargés dans l\'interface:', products.value.length)
      console.log('✅ [Ventes] Catégories:', categories.value)
    } else {
      console.error('❌ [Ventes] Réponse API invalide:', response)
      console.error('❌ [Ventes] response.success:', response?.success)
      console.error('❌ [Ventes] response.data:', response?.data)
      products.value = []
    }
  } catch (error) {
    console.error('❌ [Ventes] Erreur lors du chargement des produits:', error)
    products.value = []
  } finally {
    loadingProducts.value = false
  }
}

const loadPointsVente = async () => {
  loadingPointsVente.value = true
  try {
    const response = await apiService.get('/api_point_vente.php?action=all')
    if (response.success && response.data) {
      pointsVente.value = Array.isArray(response.data) ? response.data : []
      // Si un seul point de vente, le sélectionner automatiquement
      if (pointsVente.value.length === 1) {
        selectedPointVente.value = pointsVente.value[0]
      } else if (pointsVente.value.length > 1) {
        showPointVenteModal.value = true
      }
    }
  } catch (error) {
    console.error('Erreur lors du chargement des points de vente:', error)
  } finally {
    loadingPointsVente.value = false
  }
}

const addToCart = (product) => {
  if (product.quantite_stock === 0) return
  
  // Vérifier si le produit est déjà dans le panier
  const existingIndex = cart.value.findIndex(item => item.id_produit === product.id_produit)
  
  if (existingIndex >= 0) {
    // Augmenter la quantité si le produit existe déjà
    increaseQuantity(existingIndex)
  } else {
    // Ajouter un nouveau produit au panier
    const prixUnitaire = product.prix_vente || 0
    const quantite = 1
    cart.value.push({
      id_produit: product.id_produit,
      code_produit: product.code_produit,
      nom: product.nom,
      prix_unitaire: prixUnitaire,
      quantite: quantite,
      quantite_stock: product.quantite_stock || 0,
      sous_total: prixUnitaire * quantite
    })
  }
}

const removeFromCart = (index) => {
  cart.value.splice(index, 1)
}

const increaseQuantity = (index) => {
  const item = cart.value[index]
  if (item.quantite < item.quantite_stock) {
    item.quantite++
    updateCartItem(index)
  }
}

const decreaseQuantity = (index) => {
  const item = cart.value[index]
  if (item.quantite > 1) {
    item.quantite--
    updateCartItem(index)
  } else {
    removeFromCart(index)
  }
}

const updateCartItem = (index) => {
  const item = cart.value[index]
  if (item.quantite > item.quantite_stock) {
    item.quantite = item.quantite_stock
  }
  if (item.quantite < 1) {
    item.quantite = 1
  }
  // Recalculer le sous-total : prix_unitaire * quantite
  item.sous_total = (item.prix_unitaire || 0) * (item.quantite || 0)
}

const showClearCartModal = ref(false)

const clearCart = () => {
  showClearCartModal.value = true
}

const confirmClearCart = () => {
  cart.value = []
  discount.value = 0
  discountValue.value = 0
  discountType.value = 'fixed'
  showClearCartModal.value = false
}

const cancelClearCart = () => {
  showClearCartModal.value = false
}

const calculateDiscount = () => {
  if (discountValue.value <= 0) return 0
  if (discountType.value === 'percent') {
    return (subtotal.value * discountValue.value) / 100
  } else {
    return Math.min(discountValue.value, subtotal.value)
  }
}

const applyDiscount = () => {
  discount.value = calculateDiscount()
  showDiscountModal.value = false
}

const selectPointVente = (pv) => {
  selectedPointVente.value = pv
  showPointVenteModal.value = false
}

const lastSaleReceipt = ref(null)
const showReceiptModal = ref(false)

const processSale = async () => {
  if (cart.value.length === 0) {
    // Pas d'alerte, juste retourner silencieusement
    return
  }
  
  if (!selectedPointVente.value) {
    showPointVenteModal.value = true
    return
  }
  
  processingSale.value = true
  
  try {
    const produits = cart.value.map(item => ({
      id_produit: item.id_produit,
      quantite: item.quantite,
      prix_unitaire: item.prix_unitaire
    }))
    
    const saleData = {
      id_point_vente: selectedPointVente.value.id_point_vente,
      produits: produits,
      remise: discount.value,
      notes: discount.value > 0 ? `Remise appliquée: ${formatPrice(discount.value)}` : null
    }
    
    const response = await apiService.post('/api_vente.php', saleData)
    
    if (response.success) {
      // Récupérer l'ID de la vente depuis la réponse
      const idVente = response.data?.id_vente || response.data?.ventes?.[0]?.id_vente || Date.now()

      // Créer une écriture comptable automatiquement
      let id_entreprise = null
      if (selectedPointVente.value && selectedPointVente.value.id_entreprise) {
        id_entreprise = selectedPointVente.value.id_entreprise
      } else {
        // fallback: récupère depuis le localStorage si besoin
        const user = localStorage.getItem('prostock_user')
        if (user) {
          id_entreprise = JSON.parse(user).id_entreprise
        }
      }
      if (id_entreprise) {
        await createEcriture({
          date_ecriture: new Date().toISOString().slice(0, 10),
          type_ecriture: 'Entrée',
          montant: total.value,
          categorie: 'Vente',
          statut: 'validé',
          reference: idVente,
          details: `Vente au point de vente ${selectedPointVente.value?.nom_point_vente || ''}`,
          id_entreprise
        })
      }

      // Créer le reçu avec toutes les informations
      lastSaleReceipt.value = {
        id_vente: idVente,
        date: new Date().toLocaleString('fr-FR', {
          day: '2-digit',
          month: '2-digit',
          year: 'numeric',
          hour: '2-digit',
          minute: '2-digit'
        }),
        point_vente: selectedPointVente.value.nom_point_vente,
        produits: cart.value.map(item => ({
          nom: item.nom,
          code: item.code_produit,
          quantite: item.quantite,
          prix_unitaire: item.prix_unitaire,
          sous_total: item.sous_total
        })),
        nombre_articles: totalItems.value,
        sous_total: subtotal.value,
        remise: discount.value,
        total: total.value
      }

      // Vider le panier automatiquement après vente réussie (sans confirmation)
      cart.value = []
      discount.value = 0
      discountValue.value = 0
      discountType.value = 'fixed'

      // Afficher le reçu (pas d'alerte de succès, le reçu suffit)
      showReceiptModal.value = true

      // Recharger les produits pour mettre à jour les stocks
      await loadProducts()

      // Le reçu est automatiquement enregistré côté serveur dans api_vente.php
    } else {
      // Erreur silencieuse, juste dans la console
      console.error('Erreur lors de l\'enregistrement de la vente:', response.message || 'Erreur inconnue')
    }
  } catch (error) {
    // Erreur silencieuse, juste dans la console
    console.error('Erreur lors de la vente:', error)
  } finally {
    processingSale.value = false
  }
}

const closeReceipt = () => {
  showReceiptModal.value = false
  lastSaleReceipt.value = null
}

const printReceipt = () => {
  // Créer une nouvelle fenêtre pour l'impression du reçu uniquement
  const printWindow = window.open('', '_blank')
  if (!printWindow) {
    // Pas d'alerte, juste retourner silencieusement
    console.warn('Impossible d\'ouvrir la fenêtre d\'impression. Veuillez autoriser les popups.')
    return
  }
  
  // Récupérer le contenu HTML du reçu
  const receiptElement = document.querySelector('.receipt-modal')
  if (!receiptElement) {
    // Pas d'alerte, juste retourner silencieusement
    console.warn('Reçu non trouvé')
    return
  }
  
  // Cloner l'élément pour ne pas modifier l'original
  const receiptClone = receiptElement.cloneNode(true)
  
  // Supprimer les boutons d'action dans le clone
  const actions = receiptClone.querySelector('.receipt-actions')
  if (actions) {
    actions.remove()
  }
  
  // Créer le HTML complet pour l'impression
  const htmlContent = `
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="UTF-8">
      <title>Reçu de Vente #${lastSaleReceipt.value.id_vente}</title>
      <style>
        * {
          margin: 0;
          padding: 0;
          box-sizing: border-box;
        }
        body {
          font-family: Arial, sans-serif;
          padding: 20px;
          background: white;
        }
        .receipt-modal {
          background: white;
          max-width: 600px;
          margin: 0 auto;
          padding: 0;
        }
        .receipt-header {
          background: linear-gradient(135deg, #1a5f4a 0%, #145040 100%);
          color: white;
          padding: 1.5rem;
          border-radius: 16px 16px 0 0;
        }
        .receipt-header h2 {
          margin: 0;
          font-size: 1.5rem;
          font-weight: 700;
        }
        .receipt-body {
          padding: 2rem;
        }
        .receipt-info {
          margin-bottom: 2rem;
          padding-bottom: 1.5rem;
          border-bottom: 2px solid #e5e7eb;
        }
        .receipt-line {
          display: flex;
          justify-content: space-between;
          margin-bottom: 0.75rem;
          font-size: 0.95rem;
        }
        .receipt-line .label {
          color: #6b7280;
          font-weight: 600;
        }
        .receipt-line .value {
          color: #111827;
          font-weight: 600;
        }
        .receipt-products {
          margin-bottom: 2rem;
        }
        .receipt-table-header {
          display: grid;
          grid-template-columns: 2fr 1fr 1fr 1fr;
          gap: 1rem;
          padding: 1rem;
          background: #f9fafb;
          border-radius: 8px;
          font-weight: 700;
          color: #374151;
          font-size: 0.9rem;
          margin-bottom: 0.5rem;
        }
        .receipt-table-row {
          display: grid;
          grid-template-columns: 2fr 1fr 1fr 1fr;
          gap: 1rem;
          padding: 1rem;
          border-bottom: 1px solid #e5e7eb;
        }
        .receipt-table-row:last-child {
          border-bottom: none;
        }
        .col-name {
          display: flex;
          flex-direction: column;
        }
        .product-name {
          font-weight: 600;
          color: #111827;
          margin-bottom: 0.25rem;
        }
        .product-code {
          font-size: 0.85rem;
          color: #6b7280;
        }
        .col-qty, .col-price, .col-total {
          text-align: right;
          font-weight: 600;
          color: #111827;
        }
        .receipt-totals {
          background: #f9fafb;
          padding: 1.5rem;
          border-radius: 8px;
          margin-bottom: 2rem;
        }
        .receipt-total-row {
          display: flex;
          justify-content: space-between;
          margin-bottom: 0.75rem;
          font-size: 1rem;
        }
        .receipt-total-row:last-child {
          margin-bottom: 0;
        }
        .receipt-total-row.final {
          margin-top: 1rem;
          padding-top: 1rem;
          border-top: 2px solid #e5e7eb;
          font-size: 1.3rem;
        }
        .receipt-total-row.final .label {
          font-weight: 700;
          color: #111827;
        }
        .receipt-total-row.final .value {
          font-weight: 700;
          color: #1a5f4a;
          font-size: 1.5rem;
        }
        .receipt-total-row .value.discount {
          color: #dc2626;
        }
        .receipt-footer {
          text-align: center;
          padding-top: 1.5rem;
          border-top: 2px solid #e5e7eb;
        }
        .receipt-footer p {
          margin: 0.5rem 0;
          color: #6b7280;
        }
        .receipt-note {
          font-size: 0.85rem;
          color: #9ca3af;
          font-style: italic;
        }
        @media print {
          body {
            padding: 0;
          }
          .receipt-modal {
            box-shadow: none;
            max-width: 100%;
          }
        }
      </style>
    </head>
    <body>
      ${receiptClone.outerHTML}
    </body>
    </html>
  `
  
  printWindow.document.write(htmlContent)
  printWindow.document.close()
  
  // Attendre que le contenu soit chargé avant d'imprimer
  printWindow.onload = () => {
    setTimeout(() => {
      printWindow.print()
      // Fermer la fenêtre après impression (optionnel)
      // printWindow.close()
    }, 250)
  }
}

// Initialisation
onMounted(async () => {
  await loadProducts()
  await loadPointsVente()
})
</script>

<style scoped>
.ventes-page {
  padding: 0;
  height: calc(100vh - 70px);
  overflow: hidden;
  background: #f5f7fa;
  width: 100%;
}

.ventes-container {
  display: flex;
  height: 100%;
  gap: 0;
}

/* Colonne Panier (Gauche) */
.cart-column {
  width: 400px;
  background: white;
  border-right: 2px solid #e5e7eb;
  display: flex;
  flex-direction: column;
  height: 100%;
  box-shadow: 2px 0 8px rgba(0,0,0,0.05);
}

.cart-header {
  padding: 1.5rem;
  border-bottom: 2px solid #e5e7eb;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #1a5f4a;
  color: white;
}

.cart-header h2 {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 700;
}

.btn-clear {
  background: rgba(255,255,255,0.2);
  color: white;
  border: none;
  border-radius: 6px;
  padding: 0.5rem 1rem;
  font-size: 0.9rem;
  cursor: pointer;
  transition: background 0.2s;
}

.btn-clear:hover {
  background: rgba(255,255,255,0.3);
}

.cart-content {
  flex: 1;
  overflow-y: auto;
  padding: 1rem;
}

.empty-cart {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100%;
  color: #9ca3af;
  text-align: center;
}

.empty-cart-icon {
  font-size: 4rem;
  margin-bottom: 1rem;
}

.empty-cart-hint {
  font-size: 0.9rem;
  margin-top: 0.5rem;
}

.cart-items {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.cart-item {
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 1rem;
  transition: all 0.2s;
}

.cart-item:hover {
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.cart-item-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 0.75rem;
}

.cart-item-info {
  flex: 1;
}

.cart-item-name {
  margin: 0 0 0.25rem 0;
  font-size: 1rem;
  font-weight: 600;
  color: #111827;
}

.cart-item-code {
  font-size: 0.85rem;
  color: #6b7280;
}

.btn-remove {
  background: #fee2e2;
  color: #dc2626;
  border: none;
  border-radius: 50%;
  width: 28px;
  height: 28px;
  font-size: 1.2rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.btn-remove:hover {
  background: #dc2626;
  color: white;
}

.cart-item-details {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.cart-item-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.9rem;
}

.cart-item-row .label {
  color: #6b7280;
}

.cart-item-row .value {
  font-weight: 600;
  color: #111827;
}

.cart-item-row .value.low-stock {
  color: #dc2626;
}

.cart-item-row.total-row {
  margin-top: 0.5rem;
  padding-top: 0.5rem;
  border-top: 1px solid #e5e7eb;
}

.cart-item-row.total-row .value {
  font-size: 1.1rem;
  color: #1a5f4a;
}

.quantity-controls {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.qty-btn {
  background: #1a5f4a;
  color: white;
  border: none;
  border-radius: 6px;
  width: 32px;
  height: 32px;
  font-size: 1.2rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.2s;
}

.qty-btn:hover {
  background: #145040;
}

.qty-input {
  width: 60px;
  padding: 0.5rem;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  text-align: center;
  font-size: 1rem;
  font-weight: 600;
}

.cart-footer {
  border-top: 2px solid #e5e7eb;
  padding: 1.5rem;
  background: white;
}

.cart-summary {
  margin-bottom: 1.5rem;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem 0;
  font-size: 0.95rem;
}

.summary-row .summary-label {
  color: #6b7280;
}

.summary-row .summary-value {
  font-weight: 600;
  color: #111827;
}

.summary-row.discount-row .summary-value {
  color: #dc2626;
}

.summary-row.total-row {
  margin-top: 0.5rem;
  padding-top: 0.75rem;
  border-top: 2px solid #e5e7eb;
  font-size: 1.2rem;
}

.summary-row.total-row .summary-label {
  font-weight: 700;
  color: #111827;
}

.summary-row.total-row .summary-value {
  font-size: 1.5rem;
  color: #1a5f4a;
  font-weight: 700;
}

.cart-actions {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.btn-discount {
  background: #f3f4f6;
  color: #374151;
  border: none;
  border-radius: 8px;
  padding: 0.75rem;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
}

.btn-discount:hover {
  background: #e5e7eb;
}

.btn-checkout {
  background: #1a5f4a;
  color: white;
  border: none;
  border-radius: 8px;
  padding: 1rem;
  font-size: 1.1rem;
  font-weight: 700;
  cursor: pointer;
  transition: background 0.2s;
}

.btn-checkout:hover:not(:disabled) {
  background: #145040;
}

.btn-checkout:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Colonne Produits (Droite) */
.products-column {
  flex: 1;
  display: flex;
  flex-direction: column;
  height: 100%;
  overflow: hidden;
  background: #f9fafb;
}

.products-header {
  padding: 1.5rem;
  background: white;
  border-bottom: 2px solid #e5e7eb;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.header-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.point-vente-info {
  display: flex;
  align-items: center;
}

.pv-badge {
  background: #1a5f4a;
  color: white;
  padding: 0.5rem 1rem;
  border-radius: 8px;
  font-weight: 600;
  font-size: 0.95rem;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.btn-select-pv {
  background: #f59e0b;
  color: white;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
}

.btn-select-pv:hover {
  background: #d97706;
}

.search-section {
  display: flex;
  gap: 1rem;
  align-items: center;
}

.search-input {
  flex: 1;
  padding: 0.75rem 1rem;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-size: 1rem;
  transition: border-color 0.2s;
}

.search-input:focus {
  outline: none;
  border-color: #1a5f4a;
}

.filter-select {
  padding: 0.75rem 1rem;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-size: 1rem;
  background: white;
  cursor: pointer;
}

.products-stats {
  font-size: 0.9rem;
  color: #6b7280;
}

.loading-products,
.no-products {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem;
  text-align: center;
}

.loading-spinner {
  font-size: 3rem;
  margin-bottom: 1rem;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.no-products-icon {
  font-size: 4rem;
  margin-bottom: 1rem;
  opacity: 0.5;
}

.no-products p {
  font-size: 1.2rem;
  color: #6b7280;
  margin-bottom: 0.5rem;
}

.no-products-hint {
  font-size: 0.95rem;
  color: #9ca3af;
}

.products-grid {
  flex: 1;
  overflow-y: auto;
  padding: 1.5rem;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 1rem;
  align-content: start;
}

.product-card {
  background: white;
  border: 2px solid #e5e7eb;
  border-radius: 12px;
  padding: 1rem;
  cursor: pointer;
  transition: all 0.2s;
  position: relative;
  display: flex;
  flex-direction: column;
}

.product-card:hover {
  border-color: #1a5f4a;
  box-shadow: 0 4px 12px rgba(26,95,74,0.15);
  transform: translateY(-2px);
}

.product-card.out-of-stock {
  opacity: 0.6;
  cursor: not-allowed;
}

.product-card.low-stock {
  border-color: #f59e0b;
}

.product-badge {
  position: absolute;
  top: 0.5rem;
  right: 0.5rem;
  background: #dc2626;
  color: white;
  padding: 0.25rem 0.5rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 700;
}

.product-badge.warning {
  background: #f59e0b;
}

.product-image {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 120px;
  margin-bottom: 0.75rem;
  background: #f9fafb;
  border-radius: 8px;
  overflow: hidden;
}

.product-image-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center;
}

.product-icon {
  font-size: 3rem;
  color: #9ca3af;
}

.product-info {
  flex: 1;
  margin-bottom: 0.75rem;
}

.product-name {
  margin: 0 0 0.25rem 0;
  font-size: 1rem;
  font-weight: 600;
  color: #111827;
  line-height: 1.3;
}

.product-code {
  font-size: 0.85rem;
  color: #6b7280;
  margin-bottom: 0.5rem;
}

.product-stock {
  font-size: 0.85rem;
  color: #6b7280;
}

.product-stock strong {
  color: #1a5f4a;
  font-weight: 700;
}

.product-price {
  margin-bottom: 0.75rem;
  padding-top: 0.75rem;
  border-top: 1px solid #e5e7eb;
}

.price-label {
  font-size: 0.75rem;
  color: #6b7280;
  margin-bottom: 0.25rem;
}

.price-value {
  font-size: 1.3rem;
  font-weight: 700;
  color: #1a5f4a;
}

.product-add-btn {
  width: 100%;
  background: #1a5f4a;
  color: white;
  border: none;
  border-radius: 8px;
  padding: 0.75rem;
  font-size: 0.95rem;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
}

.product-add-btn:hover:not(:disabled) {
  background: #145040;
}

.product-add-btn:disabled {
  background: #9ca3af;
  cursor: not-allowed;
}

.no-products {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100%;
  color: #9ca3af;
  font-size: 1.1rem;
}

/* Reçu Modal */
.receipt-overlay {
  z-index: 2000;
}

.receipt-modal {
  background: white;
  border-radius: 16px;
  max-width: 600px;
  width: 90%;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}

.receipt-header {
  background: linear-gradient(135deg, #1a5f4a 0%, #145040 100%);
  color: white;
  padding: 1.5rem;
  border-radius: 16px 16px 0 0;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.receipt-header h2 {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 700;
}

.receipt-actions {
  display: flex;
  gap: 0.5rem;
}

.btn-print {
  background: rgba(255,255,255,0.2);
  color: white;
  border: 1px solid rgba(255,255,255,0.3);
  padding: 0.5rem 1rem;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
  transition: background 0.2s;
}

.btn-print:hover {
  background: rgba(255,255,255,0.3);
}

.btn-close-receipt {
  background: none;
  border: none;
  color: white;
  font-size: 2rem;
  cursor: pointer;
  line-height: 1;
  padding: 0;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.receipt-body {
  padding: 2rem;
}

.receipt-info {
  margin-bottom: 2rem;
  padding-bottom: 1.5rem;
  border-bottom: 2px solid #e5e7eb;
}

.receipt-line {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.75rem;
  font-size: 0.95rem;
}

.receipt-line .label {
  color: #6b7280;
  font-weight: 600;
}

.receipt-line .value {
  color: #111827;
  font-weight: 600;
}

.receipt-products {
  margin-bottom: 2rem;
}

.receipt-table-header {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr 1fr;
  gap: 1rem;
  padding: 1rem;
  background: #f9fafb;
  border-radius: 8px;
  font-weight: 700;
  color: #374151;
  font-size: 0.9rem;
  margin-bottom: 0.5rem;
}

.receipt-table-row {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr 1fr;
  gap: 1rem;
  padding: 1rem;
  border-bottom: 1px solid #e5e7eb;
}

.receipt-table-row:last-child {
  border-bottom: none;
}

.col-name {
  display: flex;
  flex-direction: column;
}

.product-name {
  font-weight: 600;
  color: #111827;
  margin-bottom: 0.25rem;
}

.product-code {
  font-size: 0.85rem;
  color: #6b7280;
}

.col-qty,
.col-price,
.col-total {
  text-align: right;
  font-weight: 600;
  color: #111827;
}

.receipt-totals {
  background: #f9fafb;
  padding: 1.5rem;
  border-radius: 8px;
  margin-bottom: 2rem;
}

.receipt-total-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.75rem;
  font-size: 1rem;
}

.receipt-total-row:last-child {
  margin-bottom: 0;
}

.receipt-total-row .label {
  color: #6b7280;
  font-weight: 600;
}

.receipt-total-row .value {
  color: #111827;
  font-weight: 600;
}

.receipt-total-row.final {
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 2px solid #e5e7eb;
  font-size: 1.3rem;
}

.receipt-total-row.final .label {
  font-weight: 700;
  color: #111827;
}

.receipt-total-row.final .value {
  font-weight: 700;
  color: #1a5f4a;
  font-size: 1.5rem;
}

.receipt-total-row .value.discount {
  color: #dc2626;
}

.receipt-footer {
  text-align: center;
  padding-top: 1.5rem;
  border-top: 2px solid #e5e7eb;
}

.receipt-footer p {
  margin: 0.5rem 0;
  color: #6b7280;
}

.receipt-note {
  font-size: 0.85rem;
  color: #9ca3af;
  font-style: italic;
}

@media print {
  /* Masquer tout sauf le reçu lors de l'impression */
  body * {
    visibility: hidden;
  }
  
  .receipt-overlay,
  .receipt-overlay * {
    visibility: visible;
  }
  
  .receipt-overlay {
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
    background: white;
    z-index: 9999;
  }
  
  .receipt-header .receipt-actions {
    display: none;
  }
  
  .receipt-modal {
    box-shadow: none;
    max-width: 100%;
    margin: 0;
    border-radius: 0;
  }
  
  .receipt-header {
    border-radius: 0;
  }
}

/* Styles spécifiques pour les modales de la page Ventes */

.form-group {
  margin-bottom: 1rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 600;
  color: #374151;
}

.form-input {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 1rem;
}

.discount-preview {
  margin-top: 1.5rem;
  padding: 1rem;
  background: #f9fafb;
  border-radius: 8px;
}

.preview-row {
  display: flex;
  justify-content: space-between;
  padding: 0.5rem 0;
  font-size: 0.95rem;
}

.preview-row.total {
  margin-top: 0.5rem;
  padding-top: 0.75rem;
  border-top: 1px solid #e5e7eb;
  font-weight: 700;
  font-size: 1.1rem;
}

.preview-row .discount {
  color: #dc2626;
}

/* Les styles .modal-actions sont définis dans style.css */

.btn-secondary {
  background: #f3f4f6;
  color: #374151;
  border: none;
  border-radius: 8px;
  padding: 0.75rem 1.5rem;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
}

.btn-secondary:hover {
  background: #e5e7eb;
}

.btn-primary {
  background: #1a5f4a;
  color: white;
  border: none;
  border-radius: 8px;
  padding: 0.75rem 1.5rem;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
}

.btn-primary:hover {
  background: #145040;
}

.points-vente-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.point-vente-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: #f9fafb;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
}

.point-vente-item:hover {
  background: #e5e7eb;
}

.pv-icon {
  font-size: 2rem;
}

.pv-name {
  font-weight: 600;
  color: #111827;
  margin-bottom: 0.25rem;
}

.pv-address {
  font-size: 0.9rem;
  color: #6b7280;
}

.loading, .empty-state {
  text-align: center;
  padding: 2rem;
  color: #6b7280;
}
</style>

