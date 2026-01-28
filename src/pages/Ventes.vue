<template>
  <div class="ventes-page">
    <div class="ventes-top-bar">
      <button @click="$router.push('/dashboard')" class="btn-back">
        ← Retour
      </button>
      <button @click="goToPointVente" class="btn-history" v-if="selectedPointVente">
        📋 Historique des ventes
      </button>
    </div>
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
                      :value="item.quantite"
                      @input="(e) => { item.quantite = parseInt(e.target.value) || 1; updateCartItem(index); }"
                      @blur="updateCartItem(index)"
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
                <div class="cart-item-row">
                  <span class="label">Remise:</span>
                  <div class="discount-controls">
                    <button 
                      @click="toggleRemiseType(index)"
                      class="discount-type-toggle"
                      :title="(item.remise_type || 'amount') === 'percent' ? 'Pourcentage' : 'Montant fixe'"
                    >
                      {{ (item.remise_type || 'amount') === 'percent' ? '%' : 'F' }}
                    </button>
                    <input 
                      type="number" 
                      :value="item.remise || 0"
                      @input="(e) => { item.remise = parseFloat(e.target.value) || 0; updateCartItem(index); }"
                      @blur="updateCartItem(index)"
                      :min="0"
                      :max="(item.remise_type || 'amount') === 'percent' ? 100 : item.sous_total"
                      :step="(item.remise_type || 'amount') === 'percent' ? 1 : 0.01"
                      class="discount-input"
                      placeholder="0"
                    />
                    <span class="discount-currency">
                      {{ (item.remise_type || 'amount') === 'percent' ? '%' : 'F CFA' }}
                    </span>
                  </div>
                </div>
                <div class="cart-item-row total-row">
                  <span class="label">Sous-total:</span>
                  <span class="value total">{{ formatPrice(calculateItemTotal(item)) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <div class="cart-footer" v-if="cart.length > 0">
          <div class="cart-summary">
            <div class="summary-row discount-row" v-if="discount > 0">
              <span class="summary-label">Remise globale:</span>
              <span class="summary-value discount">-{{ formatPrice(discount) }}</span>
            </div>
            <div class="summary-row total-row">
              <span class="summary-label">Total ({{ totalItems }} article{{ totalItems > 1 ? 's' : '' }}):</span>
              <span class="summary-value total">{{ formatPrice(total) }}</span>
            </div>
          </div>
          
          <div class="cart-actions">
            <button @click="showDiscountModal = true" class="btn-discount">
              💰 Remise globale
            </button>
            <button @click="openValidationModal" class="btn-checkout" :disabled="processingSale || !selectedPointVente">
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
              <button @click="goToPointVente" class="btn-history-small" title="Voir l'historique des ventes">
                📋
              </button>
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
            :class="{ 'out-of-stock': (product.quantite_stock || 0) === 0, 'low-stock': (product.quantite_stock || 0) > 0 && (product.quantite_stock || 0) <= (product.seuil_minimum || 0) }"
            @click.stop="addToCart(product)"
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
      <div class="modal-content confirmation-modal" @click.stop>
        <div class="modal-header modal-header-with-icon">
          <div class="modal-header-start">
            <span class="modal-header-icon">⚠️</span>
            <h3>Vider le panier</h3>
          </div>
          <button class="modal-close" @click="cancelClearCart">×</button>
        </div>
        <div class="modal-body">
          <p>Êtes-vous sûr de vouloir vider le panier ?</p>
          <p class="modal-warning">Cette action est irréversible et supprimera tous les produits du panier.</p>
        </div>
        <div class="modal-actions">
          <button class="btn-cancel" @click="cancelClearCart">Annuler</button>
          <button class="btn-danger" @click="confirmClearCart">Vider le panier</button>
        </div>
      </div>
    </div>
    
    <!-- Modal Remise -->
    <div v-if="showDiscountModal" class="modal-overlay" @click.self="showDiscountModal = false">
      <div class="modal-content user-modal" @click.stop>
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
          <button class="btn-cancel" @click="showDiscountModal = false">Annuler</button>
          <button class="btn-save" @click="applyDiscount">Appliquer</button>
        </div>
      </div>
    </div>
    
    <!-- Modal Validation Vente -->
    <div v-if="showValidationModal" class="modal-overlay" @click.self="closeValidationModal">
      <div class="modal-content large-modal" @click.stop>
        <div class="modal-header">
          <h3>Validation de la vente</h3>
          <button class="modal-close" @click="closeValidationModal">×</button>
        </div>
        <div class="modal-body validation-body">
          <!-- Étape 1: Choix du client -->
          <div class="validation-step">
            <h4 class="step-title">1. Client</h4>
            
            <!-- Afficher le client sélectionné -->
            <div v-if="selectedClient" class="selected-client-display">
              <div class="selected-client-info">
                <div class="selected-client-name">
                  <span v-if="selectedClient.type === 'entreprise'">
                    🏢 {{ selectedClient.nom_entreprise }}
                  </span>
                  <span v-else>
                    👤 {{ selectedClient.nom }} {{ selectedClient.prenom || '' }}
                  </span>
                </div>
                <div class="selected-client-details">
                  <span v-if="selectedClient.telephone">📞 {{ selectedClient.telephone }}</span>
                  <span v-if="selectedClient.email">📧 {{ selectedClient.email }}</span>
                </div>
              </div>
              <button @click="selectedClient = null; showClientSearch = false" class="btn-change-client">
                Changer
              </button>
            </div>
            
            <!-- Options de sélection si aucun client n'est sélectionné -->
            <div v-else class="client-selection-options">
              <div class="form-group">
                <label>
                  <input type="radio" v-model="clientSelectionMode" value="anonyme" />
                  Client anonyme
                </label>
              </div>
              <div class="form-group">
                <label>
                  <input type="radio" v-model="clientSelectionMode" value="recherche" />
                  Choisir un client
                </label>
              </div>
              
              <div v-if="clientSelectionMode === 'recherche'" class="client-search-section">
                <input 
                  v-model="clientSearchQuery"
                  type="text"
                  placeholder="🔍 Rechercher un client (nom, prénom, entreprise, téléphone, email)..."
                  class="form-input"
                  @input="searchClients"
                  autocomplete="off"
                />
                <div v-if="loadingClients" class="loading">Chargement des clients...</div>
                <div v-else-if="clients.length === 0" class="empty-state">
                  <p>⚠️ Aucun client disponible. Veuillez d'abord enregistrer des clients dans la page Clients.</p>
                </div>
                <div v-else-if="filteredClients.length === 0" class="empty-state">
                  <p v-if="clientSearchQuery">❌ Aucun client trouvé pour "{{ clientSearchQuery }}"</p>
                  <p v-else>💡 Tapez pour rechercher parmi {{ clients.length }} client(s) disponible(s)</p>
                </div>
                <div v-else class="clients-list">
                  <div class="clients-count" v-if="clientSearchQuery">
                    {{ filteredClients.length }} client(s) trouvé(s)
                  </div>
                  <div 
                    v-for="client in filteredClients" 
                    :key="client.id"
                    class="client-item"
                    :class="{ 'selected': selectedClient?.id === client.id }"
                    @click="selectClient(client)"
                  >
                    <div class="client-info">
                      <div class="client-name">
                        <span v-if="client.type === 'entreprise'">
                          🏢 {{ client.nom_entreprise }}
                        </span>
                        <span v-else>
                          👤 {{ client.nom }} {{ client.prenom || '' }}
                        </span>
                      </div>
                      <div class="client-details">
                        <span v-if="client.telephone" class="client-detail-item">📞 {{ client.telephone }}</span>
                        <span v-if="client.email" class="client-detail-item">📧 {{ client.email }}</span>
                        <span v-if="client.nom_point_vente" class="client-detail-item">🏪 {{ client.nom_point_vente }}</span>
                      </div>
                    </div>
                    <div v-if="selectedClient?.id === client.id" class="client-selected-badge">✓</div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Étape 2: Mode de paiement -->
          <div class="validation-step">
            <h4 class="step-title">2. Mode de paiement</h4>
            <div class="form-group">
              <label>
                <input type="radio" v-model="paymentMode" value="comptant" />
                Paiement comptant (total)
              </label>
            </div>
            <div class="form-group">
              <label>
                <input type="radio" v-model="paymentMode" value="partiel" />
                Paiement partiel
              </label>
              <div v-if="paymentMode === 'partiel'" class="form-group" style="margin-top: 0.5rem;">
                <label>Montant payé</label>
                <input 
                  v-model.number="partialAmount" 
                  type="number" 
                  :min="0"
                  :max="total"
                  class="form-input"
                  placeholder="0"
                />
                <p class="form-hint">Reste à payer: {{ formatPrice(Math.max(0, total - partialAmount)) }}</p>
              </div>
            </div>
            <div class="form-group">
              <label>
                <input type="radio" v-model="paymentMode" value="credit" />
                Paiement à crédit
              </label>
            </div>
          </div>

          <!-- Étape 3: Moyen de paiement -->
          <div class="validation-step">
            <h4 class="step-title">3. Moyen de paiement</h4>
            <div class="form-group">
              <label>
                <input type="radio" v-model="paymentMethod" value="espece" />
                Espèce
              </label>
            </div>
            <div class="form-group">
              <label>
                <input type="radio" v-model="paymentMethod" value="mobile_money" />
                Mobile Money
              </label>
              <div v-if="paymentMethod === 'mobile_money'" class="mobile-money-section" style="margin-top: 0.5rem;">
                <select v-model="mobileMoneyProvider" class="form-input" style="margin-bottom: 0.5rem;">
                  <option value="wave">Wave</option>
                  <option value="orange_money">Orange Money</option>
                  <option value="mtn_money">MTN Money</option>
                </select>
                <input 
                  v-model="mobileMoneyReference"
                  type="text"
                  placeholder="Numéro de référence"
                  class="form-input"
                />
              </div>
            </div>
          </div>

          <!-- Résumé -->
          <div class="validation-step">
            <h4 class="step-title">Résumé de la vente</h4>
            <div class="sale-summary-preview">
              <div class="summary-preview-row">
                <span>Sous-total:</span>
                <span>{{ formatPrice(subtotal) }}</span>
              </div>
              <div class="summary-preview-row" v-if="discount > 0">
                <span>Remise globale:</span>
                <span class="discount">-{{ formatPrice(discount) }}</span>
              </div>
              <div class="summary-preview-row total">
                <span>Total à payer:</span>
                <span>{{ formatPrice(total) }}</span>
              </div>
              <div class="summary-preview-row" v-if="paymentMode === 'partiel'">
                <span>Montant payé:</span>
                <span>{{ formatPrice(partialAmount) }}</span>
              </div>
              <div class="summary-preview-row" v-if="paymentMode === 'partiel'">
                <span>Reste à payer:</span>
                <span class="warning">{{ formatPrice(Math.max(0, total - partialAmount)) }}</span>
              </div>
              <div class="summary-preview-row" v-if="paymentMode === 'credit'">
                <span>Montant à crédit:</span>
                <span class="warning">{{ formatPrice(total) }}</span>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn-cancel" @click="closeValidationModal">Annuler</button>
          <button 
            class="btn-primary" 
            @click="confirmSale"
            :disabled="processingSale || (paymentMode === 'partiel' && partialAmount <= 0)"
          >
            {{ processingSale ? '⏳ Validation...' : '✅ Confirmer la vente' }}
          </button>
        </div>
      </div>
    </div>
    
    <!-- Modal Sélection Point de Vente -->
    <div v-if="showPointVenteModal" class="modal-overlay" @click.self="showPointVenteModal = false">
      <div class="modal-content user-modal" @click.stop>
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

    <!-- Modal Historique des ventes (admin uniquement, depuis Ventes) -->
    <div v-if="showHistoriqueModal && selectedPointVente" class="modal-overlay" @click.self="showHistoriqueModal = false">
      <div class="modal-content details-modal large-modal" @click.stop>
        <div class="modal-header">
          <h3>Historique des ventes — {{ selectedPointVente.nom_point_vente }}</h3>
          <button class="modal-close" @click="showHistoriqueModal = false">×</button>
        </div>
        <div class="modal-body">
          <!-- Recherche par nom client -->
          <div class="historique-search">
            <input 
              v-model="historiqueSearchQuery"
              type="text"
              placeholder="🔍 Rechercher par nom de client..."
              class="form-input"
            />
          </div>
          
          <div v-if="loadingHistorique" class="loading">Chargement...</div>
          <div v-else-if="filteredHistoriqueVentes.length === 0" class="empty-state">
            {{ historiqueSearchQuery ? 'Aucune vente trouvée pour ce client' : 'Aucune vente pour ce point.' }}
          </div>
          <div v-else class="historique-table-wrap">
            <table class="historique-table">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Client</th>
                  <th>Produits</th>
                  <th>Qté</th>
                  <th>Montant</th>
                  <th>Vendeur</th>
                  <th>Statut</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="v in filteredHistoriqueVentes" :key="v.id_vente + '_' + (v.date_vente || '')">
                  <td>{{ formatDate(v.date_vente) }}</td>
                  <td>
                    <span v-if="v.client_nom">{{ v.client_nom }} {{ v.client_prenom || '' }}</span>
                    <span v-else class="text-muted">Client anonyme</span>
                  </td>
                  <td>
                    <div class="produits-list">
                      <span v-for="(p, i) in (v.produits || [])" :key="i" class="produit-badge">
                        {{ p.produit_nom || p.nom }} ({{ p.quantite }})
                      </span>
                    </div>
                  </td>
                  <td>{{ v.nombre_produits || (v.produits?.length || 0) }}</td>
                  <td class="montant-cell">{{ formatPrice(v.montant_total) }}</td>
                  <td>
                    <span v-if="v.user_nom">{{ v.user_nom }} {{ v.user_prenom || '' }}</span>
                    <span v-else class="text-muted">—</span>
                  </td>
                  <td>
                    <span :class="['status-badge', v.statut === 'annule' ? 'cancelled' : 'active']">
                      {{ v.statut === 'annule' ? 'Annulée' : 'Active' }}
                    </span>
                  </td>
                  <td>
                    <div class="historique-actions">
                      <button 
                        v-if="v.statut !== 'annule'" 
                        @click="cancelVente(v)" 
                        class="btn-cancel-small"
                        title="Annuler la vente"
                      >
                        ❌
                      </button>
                      <button 
                        @click="reprintReceipt(v)" 
                        class="btn-print-small"
                        title="Réimprimer le reçu"
                      >
                        🖨️
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn-cancel" @click="showHistoriqueModal = false">Fermer</button>
        </div>
      </div>
    </div>
    
    <!-- Modal Reçu de Vente -->
    <div v-if="showReceiptModal && lastSaleReceipt" class="modal-overlay receipt-overlay" @click.self="closeReceipt">
      <div class="receipt-modal receipt-a4" @click.stop>
        <div class="receipt-header">
          <h2>Reçu de Vente <span v-if="lastSaleReceipt.is_duplicate" class="duplicate-badge">DUPLICATA</span></h2>
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
            <div class="receipt-line" v-if="lastSaleReceipt.client && lastSaleReceipt.client !== 'Client anonyme'">
              <span class="label">Client:</span>
              <span class="value client-name">{{ lastSaleReceipt.client }}</span>
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
          
          <!-- Informations de paiement -->
          <div class="receipt-payment-info">
            <div class="payment-mode-badge" :class="lastSaleReceipt.mode_paiement">
              <span v-if="lastSaleReceipt.mode_paiement === 'comptant'">💰 Paiement Comptant</span>
              <span v-else-if="lastSaleReceipt.mode_paiement === 'partiel'">📊 Paiement Partiel</span>
              <span v-else-if="lastSaleReceipt.mode_paiement === 'credit'">💳 Paiement à Crédit</span>
            </div>
            
            <div class="payment-details">
              <div class="payment-method">
                <span class="payment-label">Moyen de paiement:</span>
                <span class="payment-value">
                  <span v-if="lastSaleReceipt.moyen_paiement === 'espece'">💵 Espèce</span>
                  <span v-else-if="lastSaleReceipt.moyen_paiement === 'mobile_money'">
                    📱 Mobile Money
                    <span v-if="lastSaleReceipt.mobile_money_provider" class="provider-name">
                      ({{ getMobileMoneyProviderName(lastSaleReceipt.mobile_money_provider) }})
                    </span>
                  </span>
                </span>
              </div>
              
              <div v-if="lastSaleReceipt.mobile_money_reference" class="payment-reference">
                <span class="payment-label">Référence:</span>
                <span class="payment-value">{{ lastSaleReceipt.mobile_money_reference }}</span>
              </div>
              
              <div v-if="lastSaleReceipt.mode_paiement === 'partiel' || lastSaleReceipt.mode_paiement === 'credit'" class="payment-amounts">
                <div class="payment-amount-row">
                  <span class="payment-label">Montant payé:</span>
                  <span class="payment-value paid">{{ formatPrice(lastSaleReceipt.montant_paye || 0) }}</span>
                </div>
                <div class="payment-amount-row">
                  <span class="payment-label">Reste à payer:</span>
                  <span class="payment-value remaining">{{ formatPrice(lastSaleReceipt.reste_a_payer || 0) }}</span>
                </div>
              </div>
              <div v-else class="payment-amount-row">
                <span class="payment-label">Montant payé:</span>
                <span class="payment-value paid">{{ formatPrice(lastSaleReceipt.total) }}</span>
              </div>
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
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { apiService } from '../composables/Api/apiService.js'
import apiClient from '../composables/Api/apiClient.js'
import { useAuthStore } from '../stores/auth.js'
import { useCurrency } from '../composables/useCurrency.js'

const router = useRouter()
const authStore = useAuthStore()
const { formatPrice } = useCurrency()

const entrepriseNom = computed(() => authStore.user?.nom_entreprise || 'Nom de l\'entreprise')

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
const showHistoriqueModal = ref(false)
const historiqueVentes = ref([])
const historiqueSearchQuery = ref('')
const loadingHistorique = ref(false)
const loadingPointsVente = ref(false)
const processingSale = ref(false)

// Modale de validation
const showValidationModal = ref(false)
const clients = ref([])
const loadingClients = ref(false)
const selectedClient = ref(null)
const clientSearchQuery = ref('')
const showClientSearch = ref(false)
const clientSelectionMode = ref('anonyme') // 'anonyme' ou 'recherche'
const paymentMode = ref('comptant') // comptant, partiel, credit
const paymentMethod = ref('espece') // espece, mobile_money
const mobileMoneyProvider = ref('wave') // wave, orange_money, mtn_money
const mobileMoneyReference = ref('')
const partialAmount = ref(0)

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
  // Calculer explicitement : prix_unitaire * quantite pour chaque produit (sans remise individuelle)
  return cart.value.reduce((sum, item) => {
    const itemTotal = (item.prix_unitaire || 0) * (item.quantite || 0)
    return sum + itemTotal
  }, 0)
})

const totalRemisesIndividuelles = computed(() => {
  // Somme de toutes les remises individuelles (calculées)
  return cart.value.reduce((sum, item) => {
    const sousTotalBrut = (item.prix_unitaire || 0) * (item.quantite || 0)
    let remiseValue = 0
    
    if (item.remise && item.remise > 0) {
      if (item.remise_type === 'percent') {
        remiseValue = (sousTotalBrut * item.remise) / 100
      } else {
        remiseValue = Math.min(item.remise, sousTotalBrut)
      }
    }
    
    return sum + remiseValue
  }, 0)
})

const total = computed(() => {
  const calculatedSubtotal = subtotal.value
  const remisesIndividuelles = totalRemisesIndividuelles.value
  const calculatedDiscount = discount.value || 0
  // Total = sous-total - remises individuelles - remise globale
  return Math.max(0, calculatedSubtotal - remisesIndividuelles - calculatedDiscount)
})

// Méthodes
// formatPrice est maintenant fourni par useCurrency()

const loadingProducts = ref(false)

const loadProducts = async () => {
  loadingProducts.value = true
  try {
    // PRIORITÉ : Si un point de vente est sélectionné, charger UNIQUEMENT les produits disponibles dans ce point de vente
    if (selectedPointVente.value && selectedPointVente.value.id_point_vente) {
      const pvId = selectedPointVente.value.id_point_vente
      const url = `/api_produit.php?action=all&id_point_vente=${pvId}`
      console.log('📦 [Ventes] Chargement produits du point de vente:', pvId, 'url:', url)
      
      const response = await apiService.get(url)
      console.log('📦 [Ventes] Réponse API point de vente:', response)
      
      if (response && response.success && response.data) {
        let allProducts = Array.isArray(response.data) ? response.data.map(p => ({
          ...p,
          categorie: p.id_categorie || 'Non catégorisé',
          actif: p.actif === 1 || p.actif === true || p.actif === '1'
        })) : []
        
        // Filtrer par produits actifs
        allProducts = allProducts.filter(p => p.actif === 1 || p.actif === true || p.actif === '1')
        
        products.value = allProducts
        const uniqueCategories = [...new Set(products.value.map(p => p.categorie))]
        categories.value = uniqueCategories.filter(c => c)
        console.log('✅ [Ventes] Produits du point de vente chargés:', products.value.length, 'produits')
        return
      } else {
        console.warn('⚠️ [Ventes] Aucun produit disponible dans ce point de vente')
        products.value = []
        categories.value = []
        return
      }
    }
    
    // FALLBACK : Si aucun point de vente n'est sélectionné, ne rien charger
    console.warn('⚠️ [Ventes] Aucun point de vente sélectionné - aucun produit chargé')
    products.value = []
    categories.value = []
  } catch (error) {
    console.error('❌ [Ventes] Erreur lors du chargement des produits:', error)
    products.value = []
    categories.value = []
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
      
      // Non-admin : sélection automatique basée sur permissions_points_vente
      if (!isAdmin.value) {
        const user = authStore.user
        if (user && user.permissions_points_vente && Array.isArray(user.permissions_points_vente) && user.permissions_points_vente.length > 0) {
          // Trouver le point de vente correspondant aux permissions
          const pvId = parseInt(user.permissions_points_vente[0])
          const pv = pointsVente.value.find(p => p.id_point_vente === pvId)
          if (pv) {
            selectedPointVente.value = pv
            console.log('✅ [Ventes] Point de vente auto-sélectionné pour non-admin:', pv.nom_point_vente)
          } else if (pointsVente.value.length === 1) {
            selectedPointVente.value = pointsVente.value[0]
          } else if (pointsVente.value.length > 0) {
            selectedPointVente.value = pointsVente.value[0]
          }
        } else if (pointsVente.value.length === 1) {
          selectedPointVente.value = pointsVente.value[0]
        } else if (pointsVente.value.length > 0) {
          selectedPointVente.value = pointsVente.value[0]
        }
      } else {
        // Admin : sélection manuelle si plusieurs points de vente
        if (pointsVente.value.length === 1) {
          selectedPointVente.value = pointsVente.value[0]
        } else if (pointsVente.value.length > 1) {
          // Admin doit choisir manuellement
          showPointVenteModal.value = true
        }
      }
      
      // Charger les produits si un point de vente est sélectionné
      if (selectedPointVente.value) {
        await loadProducts()
      }
    }
  } catch (error) {
    console.error('Erreur lors du chargement des points de vente:', error)
  } finally {
    loadingPointsVente.value = false
  }
}

const addToCart = (product) => {
  console.log('🛒 [Ventes] Tentative d\'ajout au panier:', product)
  
  // Empêcher l'ajout si aucun point de vente n'est sélectionné
  if (!selectedPointVente.value) {
    console.warn('⚠️ [Ventes] Aucun point de vente sélectionné')
    if (isAdmin.value) {
      showPointVenteModal.value = true
    }
    return
  }
  
  // Vérifier le stock (permettre l'ajout même si stock = 0, mais afficher un avertissement)
  const stock = product.quantite_stock || 0
  if (stock === 0) {
    console.warn('⚠️ [Ventes] Produit en rupture de stock:', product.nom)
    // On peut quand même l'ajouter, mais on affichera un avertissement
  }
  
  // Vérifier si le produit est déjà dans le panier
  const existingIndex = cart.value.findIndex(item => item.id_produit === product.id_produit)
  
  if (existingIndex >= 0) {
    // Augmenter la quantité si le produit existe déjà
    console.log('➕ [Ventes] Produit déjà dans le panier, augmentation de la quantité')
    increaseQuantity(existingIndex)
  } else {
    // Ajouter un nouveau produit au panier
    const prixUnitaire = product.prix_vente || 0
    const quantite = 1
    const sousTotalBrut = prixUnitaire * quantite
    
    const newItem = {
      id_produit: product.id_produit,
      code_produit: product.code_produit,
      nom: product.nom,
      prix_unitaire: prixUnitaire,
      quantite: quantite,
      quantite_stock: stock,
      remise: 0,
      remise_type: 'amount', // 'percent' ou 'amount'
      sous_total: sousTotalBrut
    }
    
    cart.value.push(newItem)
    console.log('✅ [Ventes] Produit ajouté au panier:', newItem)
  }
}

const removeFromCart = (index) => {
  cart.value.splice(index, 1)
}

const calculateItemTotal = (item) => {
  if (!item) return 0
  
  const sousTotalBrut = (item.prix_unitaire || 0) * (item.quantite || 0)
  let remiseValue = 0
  
  // S'assurer que remise_type est défini
  const remiseType = item.remise_type || 'amount'
  
  if (item.remise && item.remise > 0) {
    if (remiseType === 'percent') {
      // Remise en pourcentage
      remiseValue = (sousTotalBrut * item.remise) / 100
    } else {
      // Remise en montant fixe
      remiseValue = Math.min(item.remise, sousTotalBrut)
    }
  }
  
  return Math.max(0, sousTotalBrut - remiseValue)
}

const toggleRemiseType = (index) => {
  const item = cart.value[index]
  if (!item) return
  
  // Basculer entre pourcentage et montant fixe
  item.remise_type = (item.remise_type || 'amount') === 'percent' ? 'amount' : 'percent'
  
  // Réinitialiser la remise lors du changement de type
  item.remise = 0
  
  // Forcer la réactivité
  cart.value[index] = { ...item }
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
  if (!item) return
  
  // Valider la quantité
  if (item.quantite > item.quantite_stock) {
    item.quantite = item.quantite_stock
  }
  if (item.quantite < 1) {
    item.quantite = 1
  }
  
  // Recalculer le sous-total brut : prix_unitaire * quantite
  const sousTotalBrut = (item.prix_unitaire || 0) * (item.quantite || 0)
  item.sous_total = sousTotalBrut
  
  // Initialiser remise_type si absent
  if (!item.remise_type) {
    item.remise_type = 'amount'
  }
  
  // Valider et ajuster la remise si nécessaire
  if (item.remise === undefined || item.remise === null) {
    item.remise = 0
  }
  
  // Valider la remise selon le type
  if (item.remise_type === 'percent') {
    // Pourcentage : entre 0 et 100
    if (item.remise > 100) {
      item.remise = 100
    }
    if (item.remise < 0) {
      item.remise = 0
    }
  } else {
    // Montant fixe : entre 0 et le sous-total
    if (item.remise > sousTotalBrut) {
      item.remise = sousTotalBrut
    }
    if (item.remise < 0) {
      item.remise = 0
    }
  }
  
  // Forcer la réactivité
  cart.value[index] = { ...item }
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

const formatDate = (dateString) => {
  if (!dateString) return '—'
  const date = new Date(dateString)
  return date.toLocaleString('fr-FR', {
    weekday: 'long',
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const loadHistoriqueVentes = async (pv) => {
  if (!pv || !pv.id_point_vente) return
  loadingHistorique.value = true
  historiqueVentes.value = []
  try {
    const response = await apiService.get(`/api_vente.php?action=all&id_point_vente=${pv.id_point_vente}`)
    if (!response || !response.success) return
    const allVentes = response.data || []
    const ventesFiltered = allVentes.filter(v => {
      const type = v.type_vente
      if (type === 'livraison' || type === 'expedition' || type === 'commande' || type === 'retour') return false
      return true
    })
    const ventesGrouped = {}
    ventesFiltered.forEach(vente => {
      const dateKey = new Date(vente.date_vente).toISOString().slice(0, 19)
      const key = `${dateKey}_${vente.id_user}_${vente.id_point_vente}`
      if (!ventesGrouped[key]) {
        ventesGrouped[key] = {
          id_vente: vente.id_vente,
          date_vente: vente.date_vente,
          id_user: vente.id_user,
          id_point_vente: vente.id_point_vente,
          id_client: vente.id_client,
          client_nom: vente.client_nom,
          client_prenom: vente.client_prenom,
          user_nom: vente.user_nom,
          user_prenom: vente.user_prenom,
          nom_point_vente: vente.nom_point_vente,
          montant_total: parseFloat(vente.montant_total) || 0,
          nombre_produits: 1,
          produits: [{
            id_produit: vente.id_produit,
            produit_nom: vente.produit_nom,
            code_produit: vente.code_produit,
            quantite: vente.quantite,
            prix_unitaire: vente.prix_unitaire,
            montant_total: vente.montant_total
          }]
        }
      } else {
        ventesGrouped[key].montant_total += parseFloat(vente.montant_total) || 0
        ventesGrouped[key].nombre_produits += 1
        ventesGrouped[key].produits.push({
          id_produit: vente.id_produit,
          produit_nom: vente.produit_nom,
          code_produit: vente.code_produit,
          quantite: vente.quantite,
          prix_unitaire: vente.prix_unitaire,
          montant_total: vente.montant_total
        })
      }
    })
    historiqueVentes.value = Object.values(ventesGrouped).sort((a, b) => new Date(b.date_vente) - new Date(a.date_vente))
  } catch (e) {
    console.error('Erreur chargement historique ventes:', e)
  } finally {
    loadingHistorique.value = false
  }
}

const filteredHistoriqueVentes = computed(() => {
  if (!historiqueSearchQuery.value) return historiqueVentes.value
  const query = historiqueSearchQuery.value.toLowerCase()
  return historiqueVentes.value.filter(v => {
    const clientNom = `${v.client_nom || ''} ${v.client_prenom || ''}`.toLowerCase()
    return clientNom.includes(query)
  })
})

const cancelVente = async (vente) => {
  if (!confirm(`Êtes-vous sûr de vouloir annuler cette vente ? Le stock sera remis en place.`)) {
    return
  }
  
  try {
    const response = await apiService.post(`/api_vente.php?action=cancel`, { id_vente: vente.id_vente })
    if (response && response.success) {
      // Recharger l'historique
      await loadHistoriqueVentes(selectedPointVente.value)
      // Recharger les produits pour mettre à jour le stock
      await loadProducts()
      alert('Vente annulée avec succès. Le stock a été remis en place.')
    } else {
      alert('Erreur lors de l\'annulation: ' + (response?.message || 'Erreur inconnue'))
    }
  } catch (error) {
    console.error('Erreur lors de l\'annulation de la vente:', error)
    alert('Erreur lors de l\'annulation de la vente')
  }
}

const reprintReceipt = async (vente) => {
  // Reconstruire le reçu à partir des données de la vente
  // Construire le nom complet du client (particulier ou entreprise)
  let clientName = 'Client anonyme'
  if (vente.client_nom) {
    // Si c'est une entreprise, client_nom contient le nom de l'entreprise
    // Sinon, c'est un particulier avec nom et prénom
    if (vente.client_prenom && vente.client_prenom === vente.client_nom) {
      // C'est probablement une entreprise (nom_entreprise dupliqué dans nom et prenom)
      clientName = vente.client_nom
    } else {
      // C'est un particulier
      clientName = `${vente.client_nom} ${vente.client_prenom || ''}`.trim()
    }
  }
  
  // Extraire les informations de paiement depuis les notes
  const notes = vente.notes || ''
  let modePaiement = 'comptant'
  let moyenPaiement = 'espece'
  let montantPaye = vente.montant_total || 0
  let resteAPayer = 0
  let mobileMoneyProvider = null
  let mobileMoneyReference = null
  
  // Parser les notes pour extraire les informations
  if (notes.includes('Paiement partiel:')) {
    modePaiement = 'partiel'
    const match = notes.match(/Paiement partiel:\s*([\d\s,]+)\s*\/\s*([\d\s,]+)/)
    if (match) {
      const montantPayeInitial = parseFloat(match[1].replace(/\s|,/g, '')) || 0
      const total = parseFloat(match[2].replace(/\s|,/g, '')) || vente.montant_total || 0
      
      // Calculer le total payé (paiement initial + paiements supplémentaires)
      let totalPaye = montantPayeInitial
      const paiementsMatches = notes.matchAll(/Paiement supplémentaire:\s*([\d\s,]+(?:\.[\d]+)?)(?:\s*F\s*CFA)?/g)
      for (const paiementMatch of paiementsMatches) {
        const montant = paiementMatch[1].replace(/\s/g, '').replace(',', '.')
        totalPaye += parseFloat(montant) || 0
      }
      
      montantPaye = totalPaye
      resteAPayer = Math.max(0, total - totalPaye)
    }
  } else if (notes.includes('Paiement à crédit:') && !notes.includes('Paiement partiel')) {
    modePaiement = 'credit'
    const match = notes.match(/Paiement à crédit:\s*([\d\s,]+)/)
    if (match) {
      const montantCredit = parseFloat(match[1].replace(/\s|,/g, '')) || vente.montant_total || 0
      
      // Calculer le total payé (paiements supplémentaires uniquement)
      let totalPaye = 0
      const paiementsMatches = notes.matchAll(/Paiement supplémentaire:\s*([\d\s,]+(?:\.[\d]+)?)(?:\s*F\s*CFA)?/g)
      for (const paiementMatch of paiementsMatches) {
        const montant = paiementMatch[1].replace(/\s/g, '').replace(',', '.')
        totalPaye += parseFloat(montant) || 0
      }
      
      montantPaye = totalPaye
      resteAPayer = Math.max(0, montantCredit - totalPaye)
    }
  }
  
  if (notes.includes('Mobile Money')) {
    moyenPaiement = 'mobile_money'
    const providerMatch = notes.match(/Mobile Money\s*\(([^)]+)\)/)
    if (providerMatch) {
      mobileMoneyProvider = providerMatch[1].toLowerCase().replace(/\s+/g, '_')
    }
    const refMatch = notes.match(/:\s*([A-Z0-9]+)(?:\s*\|)?/)
    if (refMatch && refMatch[1] !== 'N/A') {
      mobileMoneyReference = refMatch[1]
    }
  }
  
  lastSaleReceipt.value = {
    id_vente: vente.id_vente,
    date: formatDate(vente.date_vente),
    point_vente: vente.nom_point_vente || selectedPointVente.value?.nom_point_vente || '',
    client: clientName,
    produits: vente.produits || [],
    nombre_articles: vente.nombre_produits || 0,
    sous_total: vente.montant_total || 0,
    remise: 0,
    total: vente.montant_total || 0,
    mode_paiement: modePaiement,
    moyen_paiement: moyenPaiement,
    montant_paye: montantPaye,
    reste_a_payer: resteAPayer,
    mobile_money_provider: mobileMoneyProvider,
    mobile_money_reference: mobileMoneyReference,
    is_duplicate: true
  }
  showReceiptModal.value = true
}

const goToPointVente = () => {
  if (!selectedPointVente.value) {
    router.push('/point-vente')
    return
  }
  // Rediriger vers la page dédiée d'historique
  router.push(`/historique-ventes?point_vente=${selectedPointVente.value.id_point_vente}`)
}

const lastSaleReceipt = ref(null)
const showReceiptModal = ref(false)

// Fonctions pour la modale de validation
const openValidationModal = async () => {
  if (cart.value.length === 0) {
    return
  }
  if (!selectedPointVente.value) {
    if (isAdmin.value) {
      showPointVenteModal.value = true
    }
    return
  }
  
  // Réinitialiser les valeurs
  selectedClient.value = null
  clientSearchQuery.value = ''
  showClientSearch.value = false
  clientSelectionMode.value = 'anonyme'
  paymentMode.value = 'comptant'
  paymentMethod.value = 'espece'
  mobileMoneyProvider.value = 'wave'
  mobileMoneyReference.value = ''
  partialAmount.value = 0
  
  // Charger les clients
  await loadClients()
  
  showValidationModal.value = true
}

const closeValidationModal = () => {
  showValidationModal.value = false
  selectedClient.value = null
  clientSearchQuery.value = ''
  showClientSearch.value = false
  clientSelectionMode.value = 'anonyme'
}

const loadClients = async () => {
  loadingClients.value = true
  try {
    // Utiliser apiClient comme dans Clients.vue pour avoir la même structure de réponse
    const res = await apiClient.get('/clients.php')
    console.log('📋 [Ventes] Réponse API clients complète:', res)
    console.log('📋 [Ventes] res.data:', res?.data)
    console.log('📋 [Ventes] res.data est tableau?:', Array.isArray(res?.data))
    
    // Dans Clients.vue, ils utilisent res.data
    if (res && res.data) {
      clients.value = Array.isArray(res.data) ? res.data : []
      console.log('✅ [Ventes] Clients chargés:', clients.value.length)
      if (clients.value.length > 0) {
        console.log('📋 [Ventes] Premier client:', clients.value[0])
        console.log('📋 [Ventes] Exemple de noms:', clients.value.slice(0, 3).map(c => 
          c.type === 'entreprise' ? c.nom_entreprise : `${c.nom} ${c.prenom || ''}`
        ))
      }
    } else {
      console.warn('⚠️ [Ventes] Aucune donnée dans la réponse:', res)
      clients.value = []
    }
  } catch (error) {
    console.error('❌ [Ventes] Erreur chargement clients:', error)
    console.error('❌ [Ventes] Détails erreur:', {
      message: error.message,
      response: error.response?.data,
      status: error.response?.status,
      config: error.config
    })
    clients.value = []
  } finally {
    loadingClients.value = false
  }
}

const filteredClients = computed(() => {
  if (!clientSearchQuery.value) {
    // Afficher les 10 premiers clients si pas de recherche
    return clients.value.slice(0, 10)
  }
  
  const query = clientSearchQuery.value.toLowerCase().trim()
  if (!query) return clients.value.slice(0, 10)
  
  // Recherche améliorée : correspond à la logique de la page Clients
  return clients.value.filter(c => {
    // Pour les particuliers
    const nomMatch = (c.nom || '').toLowerCase().includes(query)
    const prenomMatch = (c.prenom || '').toLowerCase().includes(query)
    const nomComplet = `${c.nom || ''} ${c.prenom || ''}`.toLowerCase().includes(query)
    
    // Pour les entreprises
    const entrepriseMatch = (c.nom_entreprise || '').toLowerCase().includes(query)
    
    // Recherche dans les autres champs
    const telephoneMatch = (c.telephone || '').includes(query)
    const emailMatch = (c.email || '').toLowerCase().includes(query)
    
    return nomMatch || prenomMatch || nomComplet || entrepriseMatch || telephoneMatch || emailMatch
  }).slice(0, 20) // Augmenter à 20 résultats pour une meilleure recherche
})

const searchClients = () => {
  // Le computed filteredClients se met à jour automatiquement
}

const selectClient = (client) => {
  selectedClient.value = client
  showClientSearch.value = false
  clientSearchQuery.value = ''
  clientSelectionMode.value = 'recherche' // Marquer qu'un client a été sélectionné
}

// Watcher pour synchroniser clientSelectionMode et selectedClient
watch(() => clientSelectionMode.value, (newMode) => {
  if (newMode === 'anonyme') {
    selectedClient.value = null
    showClientSearch.value = false
  } else if (newMode === 'recherche') {
    showClientSearch.value = true
  }
})

const confirmSale = async () => {
  if (cart.value.length === 0 || !selectedPointVente.value) {
    return
  }
  
  if (paymentMode.value === 'partiel' && partialAmount.value <= 0) {
    return
  }
  
  processingSale.value = true
  
  try {
    const produits = cart.value.map(item => {
      // Calculer la remise en montant selon le type
      const sousTotalBrut = (item.prix_unitaire || 0) * (item.quantite || 0)
      let remiseMontant = 0
      
      if (item.remise && item.remise > 0) {
        if (item.remise_type === 'percent') {
          remiseMontant = (sousTotalBrut * item.remise) / 100
        } else {
          remiseMontant = Math.min(item.remise, sousTotalBrut)
        }
      }
      
      return {
        id_produit: item.id_produit,
        quantite: item.quantite,
        prix_unitaire: item.prix_unitaire,
        remise: remiseMontant
      }
    })
    
    // Construire les notes avec toutes les informations
    let notes = []
    if (totalRemisesIndividuelles.value > 0) {
      notes.push(`Remises individuelles: ${formatPrice(totalRemisesIndividuelles.value)}`)
    }
    if (discount.value > 0) {
      notes.push(`Remise globale: ${formatPrice(discount.value)}`)
    }
    if (paymentMode.value === 'partiel') {
      notes.push(`Paiement partiel: ${formatPrice(partialAmount.value)} / ${formatPrice(total.value)}`)
    }
    if (paymentMode.value === 'credit') {
      notes.push(`Paiement à crédit: ${formatPrice(total.value)}`)
    }
    if (paymentMethod.value === 'mobile_money') {
      notes.push(`Mobile Money (${mobileMoneyProvider.value}): ${mobileMoneyReference.value || 'N/A'}`)
    }
    
    const saleData = {
      id_point_vente: selectedPointVente.value.id_point_vente,
      id_client: selectedClient.value?.id || null,
      produits: produits,
      remise: discount.value + totalRemisesIndividuelles.value,
      mode_paiement: paymentMode.value,
      moyen_paiement: paymentMethod.value,
      montant_paye: paymentMode.value === 'partiel' ? partialAmount.value : (paymentMode.value === 'comptant' ? total.value : 0),
      mobile_money_provider: paymentMethod.value === 'mobile_money' ? mobileMoneyProvider.value : null,
      mobile_money_reference: paymentMethod.value === 'mobile_money' ? mobileMoneyReference.value : null,
      notes: notes.length > 0 ? notes.join(' | ') : null
    }
    
    const response = await apiService.post('/api_vente.php', saleData)
    
    if (response.success) {
      const idVente = response.data?.id_vente || response.data?.ventes?.[0]?.id_vente || Date.now()

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
        client: selectedClient.value 
          ? (selectedClient.value.type === 'entreprise' 
              ? selectedClient.value.nom_entreprise 
              : `${selectedClient.value.nom} ${selectedClient.value.prenom || ''}`.trim())
          : 'Client anonyme',
        produits: cart.value.map(item => {
          const itemTotal = calculateItemTotal(item)
          const sousTotalBrut = (item.prix_unitaire || 0) * (item.quantite || 0)
          let remiseMontant = 0
          
          if (item.remise && item.remise > 0) {
            if (item.remise_type === 'percent') {
              remiseMontant = (sousTotalBrut * item.remise) / 100
            } else {
              remiseMontant = Math.min(item.remise, sousTotalBrut)
            }
          }
          
          return {
            nom: item.nom,
            code: item.code_produit,
            quantite: item.quantite,
            prix_unitaire: item.prix_unitaire,
            remise: remiseMontant,
            sous_total: itemTotal
          }
        }),
        nombre_articles: totalItems.value,
        sous_total: subtotal.value,
        remise: discount.value + totalRemisesIndividuelles.value,
        total: total.value,
        mode_paiement: paymentMode.value,
        moyen_paiement: paymentMethod.value,
        montant_paye: saleData.montant_paye,
        reste_a_payer: paymentMode.value === 'partiel' ? (total.value - partialAmount.value) : (paymentMode.value === 'credit' ? total.value : 0),
        mobile_money_provider: saleData.mobile_money_provider,
        mobile_money_reference: saleData.mobile_money_reference
      }

      // Vider le panier
      cart.value = []
      discount.value = 0
      discountValue.value = 0
      discountType.value = 'fixed'

      // Fermer la modale de validation
      closeValidationModal()

      // Afficher le reçu
      showReceiptModal.value = true

      // Recharger les produits
      await loadProducts()
    } else {
      console.error('Erreur lors de l\'enregistrement de la vente:', response.message || 'Erreur inconnue')
    }
  } catch (error) {
    console.error('Erreur lors de la vente:', error)
  } finally {
    processingSale.value = false
  }
}

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
      prix_unitaire: item.prix_unitaire,
      remise: item.remise || 0
    }))
    
    // Construire les notes avec toutes les remises
    let notes = []
    if (totalRemisesIndividuelles.value > 0) {
      notes.push(`Remises individuelles: ${formatPrice(totalRemisesIndividuelles.value)}`)
    }
    if (discount.value > 0) {
      notes.push(`Remise globale: ${formatPrice(discount.value)}`)
    }
    
    const saleData = {
      id_point_vente: selectedPointVente.value.id_point_vente,
      produits: produits,
      remise: discount.value + totalRemisesIndividuelles.value,
      notes: notes.length > 0 ? notes.join(' | ') : null
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
      // Écriture comptable désactivée (api_compta supprimée)

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

const getMobileMoneyProviderName = (provider) => {
  const providers = {
    'wave': 'Wave',
    'orange_money': 'Orange Money',
    'mtn_money': 'MTN Money'
  }
  return providers[provider] || provider
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
        .receipt-line .value.client-name {
          color: #1a5f4a;
          font-weight: 700;
          font-size: 1.05rem;
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
          border: 2px solid #e5e7eb;
        }
        .receipt-total-row {
          display: flex;
          justify-content: space-between;
          margin-bottom: 0.5rem;
          font-size: 1rem;
        }
        .receipt-total-row:last-child {
          margin-bottom: 0;
        }
        .receipt-total-row.final {
          margin-top: 0.5rem;
          padding-top: 0.75rem;
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
          font-size: 1.3rem;
          font-weight: 700;
        }
        .receipt-payment-info {
          background: #f0f9ff;
          padding: 1.5rem;
          border-radius: 8px;
          margin-bottom: 2rem;
          border: 2px solid #bae6fd;
        }
        .payment-mode-badge {
          display: inline-block;
          padding: 0.5rem 1rem;
          border-radius: 6px;
          font-weight: 700;
          font-size: 0.95rem;
          margin-bottom: 1rem;
        }
        .payment-mode-badge.comptant {
          background: #d1fae5;
          color: #065f46;
          border: 2px solid #10b981;
        }
        .payment-mode-badge.partiel {
          background: #fef3c7;
          color: #92400e;
          border: 2px solid #f59e0b;
        }
        .payment-mode-badge.credit {
          background: #fee2e2;
          color: #991b1b;
          border: 2px solid #ef4444;
        }
        .payment-details {
          display: flex;
          flex-direction: column;
          gap: 0.75rem;
        }
        .payment-method, .payment-reference, .payment-amount-row {
          display: flex;
          justify-content: space-between;
          align-items: center;
          padding: 0.5rem 0;
          border-bottom: 1px solid #e5e7eb;
        }
        .payment-amount-row:last-child {
          border-bottom: none;
        }
        .payment-label {
          color: #6b7280;
          font-weight: 600;
          font-size: 0.9rem;
        }
        .payment-value {
          color: #111827;
          font-weight: 700;
          font-size: 0.95rem;
        }
        .payment-value.paid {
          color: #10b981;
        }
        .payment-value.remaining {
          color: #ef4444;
        }
        .provider-name {
          color: #1a5f4a;
          font-weight: 600;
        }
        .receipt-footer {
          text-align: center;
          padding-top: 2rem;
          border-top: 2px solid #e5e7eb;
          margin-top: 2rem;
        }
        .receipt-footer p {
          margin: 0.5rem 0;
          color: #6b7280;
          font-size: 1rem;
        }
        .receipt-note {
          font-size: 0.9rem;
          color: #9ca3af;
          font-style: italic;
        }
        @media print {
          body {
            padding: 0;
            width: 210mm;
          }
          .receipt-modal {
            box-shadow: none;
            max-width: 100%;
            width: 100%;
          }
          .receipt-header {
            border-radius: 0;
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
  display: flex;
  flex-direction: column;
}

.ventes-top-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.5rem;
  background: white;
  border-bottom: 2px solid #e5e7eb;
  z-index: 10;
}

.btn-back {
  background: #6b7280;
  color: white;
  border: none;
  border-radius: 8px;
  padding: 0.75rem 1.5rem;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-back:hover {
  background: #4b5563;
}

.btn-history {
  background: #1a5f4a;
  color: white;
  border: none;
  border-radius: 8px;
  padding: 0.75rem 1.5rem;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-history:hover {
  background: #145040;
}

.btn-history-small {
  background: rgba(255,255,255,0.2);
  color: white;
  border: none;
  border-radius: 6px;
  padding: 0.5rem 0.75rem;
  font-size: 0.9rem;
  cursor: pointer;
  transition: background 0.2s;
  margin-left: 0.5rem;
}

.btn-history-small:hover {
  background: rgba(255,255,255,0.3);
}

.ventes-container {
  flex: 1;
  overflow: hidden;
  display: flex;
  height: 100%;
  gap: 0;
}

/* Colonne Panier (Gauche) */
.cart-column {
  width: 320px;
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

.discount-controls {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.discount-type-toggle {
  background: #f3f4f6;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  padding: 0.5rem 0.75rem;
  font-size: 0.85rem;
  font-weight: 700;
  color: #1a5f4a;
  cursor: pointer;
  transition: all 0.2s;
  min-width: 40px;
  text-align: center;
}

.discount-type-toggle:hover {
  background: #e5e7eb;
  border-color: #1a5f4a;
}

.discount-type-toggle:active {
  transform: scale(0.95);
}

.discount-input {
  flex: 1;
  padding: 0.5rem;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  text-align: right;
  font-size: 0.9rem;
  font-weight: 600;
  max-width: 100px;
}

.discount-input:focus {
  outline: none;
  border-color: #1a5f4a;
}

.discount-currency {
  font-size: 0.85rem;
  color: #6b7280;
  font-weight: 500;
  min-width: 50px;
  text-align: left;
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
  margin-bottom: 0.75rem;
  padding: 0.5rem 0;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.25rem 0;
  font-size: 0.85rem;
}

.summary-row .summary-label {
  color: #6b7280;
}

.summary-row .summary-value {
  font-weight: 600;
  color: #111827;
}

.summary-row.discount-row {
  margin-bottom: 0.25rem;
  font-size: 1rem;
}

.summary-row.discount-row .summary-label {
  font-weight: 600;
  color: #111827;
}

.summary-row.discount-row .summary-value {
  font-size: 1.2rem;
  color: #dc2626;
  font-weight: 700;
}

.summary-row.total-row {
  margin-top: 0;
  padding-top: 0.5rem;
  border-top: 1px solid #e5e7eb;
  font-size: 1rem;
}

.summary-row.total-row .summary-label {
  font-weight: 600;
  color: #111827;
}

.summary-row.total-row .summary-value {
  font-size: 1.2rem;
  color: #1a5f4a;
  font-weight: 700;
}

.cart-actions {
  display: flex;
  flex-direction: row;
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
  cursor: pointer;
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

.receipt-a4 {
  max-width: 210mm;
  width: 100%;
  padding: 20mm;
  margin: 0 auto;
}

.duplicate-badge {
  display: inline-block;
  background: #f59e0b;
  color: white;
  padding: 0.25rem 0.75rem;
  border-radius: 4px;
  font-size: 0.875rem;
  font-weight: 700;
  margin-left: 1rem;
  text-transform: uppercase;
}

.receipt-company-info {
  text-align: center;
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid #e5e7eb;
}

.company-name {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1a5f4a;
  margin: 0 0 0.5rem 0;
}

.company-address {
  font-size: 0.9rem;
  color: #6b7280;
  margin: 0;
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

.receipt-line .value.client-name {
  color: #1a5f4a;
  font-weight: 700;
  font-size: 1.05rem;
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
  margin-bottom: 0.5rem;
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
  margin-top: 0.5rem;
  padding-top: 0.75rem;
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
  font-size: 1.3rem;
  font-weight: 700;
}

.receipt-payment-info {
  background: #f0f9ff;
  padding: 1.5rem;
  border-radius: 8px;
  margin-bottom: 2rem;
  border: 2px solid #bae6fd;
}

.payment-mode-badge {
  display: inline-block;
  padding: 0.5rem 1rem;
  border-radius: 6px;
  font-weight: 700;
  font-size: 0.95rem;
  margin-bottom: 1rem;
}

.payment-mode-badge.comptant {
  background: #d1fae5;
  color: #065f46;
  border: 2px solid #10b981;
}

.payment-mode-badge.partiel {
  background: #fef3c7;
  color: #92400e;
  border: 2px solid #f59e0b;
}

.payment-mode-badge.credit {
  background: #fee2e2;
  color: #991b1b;
  border: 2px solid #ef4444;
}

.payment-details {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.payment-method,
.payment-reference,
.payment-amount-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem 0;
  border-bottom: 1px solid #e5e7eb;
}

.payment-amount-row:last-child {
  border-bottom: none;
}

.payment-label {
  color: #6b7280;
  font-weight: 600;
  font-size: 0.9rem;
}

.payment-value {
  color: #111827;
  font-weight: 700;
  font-size: 0.95rem;
}

.payment-value.paid {
  color: #10b981;
}

.payment-value.remaining {
  color: #ef4444;
}

.provider-name {
  color: #1a5f4a;
  font-weight: 600;
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

/* Modal Historique des ventes */
.historique-table-wrap {
  overflow-x: auto;
  max-height: 60vh;
  overflow-y: auto;
}
.historique-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.9rem;
}
.historique-table th,
.historique-table td {
  padding: 0.6rem 0.75rem;
  border-bottom: 1px solid #e5e7eb;
  text-align: left;
}
.historique-table th {
  background: #f8fafc;
  font-weight: 600;
  color: #374151;
  position: sticky;
  top: 0;
}
.historique-table .produits-list {
  display: flex;
  flex-wrap: wrap;
  gap: 0.35rem;
}
.historique-table .produit-badge {
  display: inline-block;
  background: #e0f2fe;
  color: #0369a1;
  padding: 0.2rem 0.5rem;
  border-radius: 6px;
  font-size: 0.8rem;
}
.historique-table .montant-cell {
  font-weight: 600;
  color: #1a5f4a;
}
.historique-table .text-muted {
  color: #9ca3af;
}

.historique-search {
  margin-bottom: 1rem;
}

.historique-search .form-input {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 1rem;
}

.status-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 600;
}

.status-badge.active {
  background: #d1fae5;
  color: #065f46;
}

.status-badge.cancelled {
  background: #fee2e2;
  color: #991b1b;
}

.historique-actions {
  display: flex;
  gap: 0.5rem;
}

.btn-cancel-small,
.btn-print-small {
  background: transparent;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  padding: 0.5rem;
  font-size: 1rem;
  cursor: pointer;
  transition: all 0.2s;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-cancel-small:hover {
  background: #fee2e2;
  border-color: #dc2626;
  color: #dc2626;
}

.btn-print-small:hover {
  background: #dbeafe;
  border-color: #2563eb;
  color: #2563eb;
}

/* Styles pour la modale de validation */
.large-modal {
  max-width: 700px;
  max-height: 90vh;
  overflow-y: auto;
}

.validation-body {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.validation-step {
  padding: 1rem;
  background: #f9fafb;
  border-radius: 8px;
  border-left: 4px solid #1a5f4a;
}

.step-title {
  margin: 0 0 1rem 0;
  font-size: 1.1rem;
  font-weight: 700;
  color: #1a5f4a;
}

.form-group label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
  cursor: pointer;
  font-weight: 500;
}

.form-group input[type="radio"] {
  width: 18px;
  height: 18px;
  cursor: pointer;
}

.client-search-section {
  margin-top: 0.75rem;
  padding: 1rem;
  background: white;
  border-radius: 6px;
  border: 1px solid #e5e7eb;
}

.clients-list {
  max-height: 300px;
  overflow-y: auto;
  margin-top: 0.5rem;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 0.5rem;
  background: #f9fafb;
}

.clients-count {
  padding: 0.5rem;
  font-size: 0.875rem;
  color: #6b7280;
  font-weight: 500;
  border-bottom: 1px solid #e5e7eb;
  margin-bottom: 0.5rem;
}

.client-item {
  padding: 0.875rem;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  margin-bottom: 0.5rem;
  cursor: pointer;
  transition: all 0.2s;
  background: white;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.client-item:last-child {
  margin-bottom: 0;
}

.client-item:hover {
  background: #f3f4f6;
  border-color: #1a5f4a;
  transform: translateX(2px);
}

.client-item.selected {
  background: #e0f2fe;
  border-color: #1a5f4a;
  box-shadow: 0 2px 4px rgba(26, 95, 74, 0.1);
}

.client-info {
  flex: 1;
}

.client-name {
  font-weight: 600;
  color: #111827;
  margin-bottom: 0.375rem;
  font-size: 0.95rem;
}

.client-details {
  font-size: 0.8rem;
  color: #6b7280;
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
}

.client-detail-item {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
}

.client-selected-badge {
  color: #1a5f4a;
  font-weight: bold;
  font-size: 1.2rem;
  margin-left: 0.5rem;
}

.selected-client-display {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  background: #e0f2fe;
  border: 2px solid #1a5f4a;
  border-radius: 8px;
  margin-bottom: 1rem;
}

.selected-client-info {
  flex: 1;
}

.selected-client-name {
  font-weight: 600;
  color: #111827;
  font-size: 1rem;
  margin-bottom: 0.5rem;
}

.selected-client-details {
  font-size: 0.875rem;
  color: #6b7280;
  display: flex;
  gap: 1rem;
}

.btn-change-client {
  padding: 0.5rem 1rem;
  background: #1a5f4a;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 0.875rem;
  font-weight: 500;
  transition: background 0.2s;
}

.btn-change-client:hover {
  background: #145040;
}

.client-selection-options {
  margin-top: 0.5rem;
}

.mobile-money-section {
  padding: 0.75rem;
  background: white;
  border-radius: 6px;
  border: 1px solid #e5e7eb;
}

.sale-summary-preview {
  padding: 1rem;
  background: white;
  border-radius: 6px;
  border: 1px solid #e5e7eb;
}

.summary-preview-row {
  display: flex;
  justify-content: space-between;
  padding: 0.5rem 0;
  font-size: 0.95rem;
}

.summary-preview-row.total {
  margin-top: 0.5rem;
  padding-top: 0.75rem;
  border-top: 2px solid #e5e7eb;
  font-weight: 700;
  font-size: 1.1rem;
}

.summary-preview-row .discount {
  color: #dc2626;
}

.summary-preview-row .warning {
  color: #f59e0b;
  font-weight: 600;
}

.summary-note {
  margin-top: 0.5rem;
  font-size: 0.85rem;
  color: #6b7280;
  font-style: italic;
}
</style>

