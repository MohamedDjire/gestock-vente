<template>
  <div class="products-page">
    <div class="products-header">
      <h2 class="dashboard-title">Produits & Stocks</h2>
      <div style="display: flex; gap: 1rem;">
        <button @click="goToEntrepot" class="btn-secondary" style="background: #3b82f6; color: white;">
          <span>🏭</span> Gérer les Entrepôts
        </button>
        <button @click="openCreateModal" class="btn-primary">
          <span>+</span> Nouveau Produit
        </button>
      </div>
    </div>

    <!-- Vue synthétique comme la maquette -->
    <div class="inventory-overview">
            <!-- Statistiques Produits & Stocks (en haut avec icônes) -->
            <div class="stats-row">
              <StatCard 
                title="Total Produits" 
                :value="stats.totalProduits.toString()" 
                :variation="null" 
                icon="📦" />
              <StatCard 
                title="Valeur Stock (Achat)" 
                :value="formatCurrency(stats.valeurStockAchat)" 
                :variation="null" 
                icon="💶" />
              <StatCard 
                title="Valeur Stock (Vente)" 
                :value="formatCurrency(stats.valeurStockVente)" 
                :variation="null" 
                icon="💵" />
              <StatCard 
                title="Stock Total (Unités)" 
                :value="stats.stockTotal.toString()" 
                :variation="null" 
                icon="📊" />
              <StatCard 
                title="Produits en Alerte" 
                :value="stats.produitsAlerte.toString()" 
                :variation="null" 
                icon="⚠️" />
            </div>

      <div class="inventory-summary card">
        <div class="summary-row">
          <div class="summary-label">Quantité disponible</div>
          <div class="summary-value">{{ stats.stockTotal }}</div>
        </div>
        <div class="summary-row">
          <div class="summary-label">Quantité à recevoir</div>
          <div class="summary-value muted">—</div>
        </div>
        <div class="summary-footer">Ajustez via Entrée/Sortie ou commandes fournisseurs.</div>
      </div>
    </div>

    <div class="inventory-panels">
      <div class="panel card">
        <div class="panel-header">
          <div class="panel-title">Articles stock faible / rupture</div>
          <div class="panel-count">{{ lowStockProducts.length }}</div>
        </div>
        <div v-if="lowStockProducts.length === 0" class="panel-empty">Aucune alerte en cours</div>
        <table v-else class="panel-table">
          <thead>
            <tr>
              <th>Produit</th>
              <th>Stock</th>
              <th>Seuil</th>
              <th>Statut</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="p in lowStockProducts" :key="p.id_produit">
              <td>{{ p.nom }}</td>
              <td>{{ p.quantite_stock }}</td>
              <td>{{ p.seuil_minimum }}</td>
              <td><span :class="['status-badge', p.statut_stock]">{{ getStatusLabel(p.statut_stock) }}</span></td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="panel card">
        <div class="panel-header">
          <div class="panel-title">Mouvements récents</div>
          <div class="panel-actions">
            <div class="panel-count">{{ allMovements.length }}</div>
            <button @click="openAllMovementsModal" class="btn-more" title="Voir tous les mouvements">
              <span>+</span> Plus
            </button>
          </div>
        </div>
        <div v-if="recentMovements.length === 0" class="panel-empty">Enregistrez des entrées/sorties pour voir l'historique.</div>
        <ul v-else class="movement-list">
          <li v-for="m in recentMovements" :key="m.id">
            <div class="movement-top">
              <span class="movement-type" :class="m.type">{{ m.typeLabel }}</span>
              <span class="movement-date">{{ formatShortDate(m.date) }}</span>
            </div>
            <div class="movement-main">
              <strong>{{ m.nom }}</strong>
              <span class="movement-qty">Qté: {{ m.quantite }}</span>
            </div>
            <div class="movement-sub">{{ m.details }}</div>
          </li>
        </ul>
      </div>
    </div>

    <!-- Panneau d'Alertes -->
    <div v-if="alertesNonVues.length > 0" class="alertes-panel">
      <div class="alertes-header">
        <h3>⚠️ Alertes de Stock ({{ alertesNonVues.length }})</h3>
        <button @click="markAllAlertesAsVue" class="btn-mark-all">Tout marquer comme vu</button>
      </div>
      <div class="alertes-list">
        <div 
          v-for="alerte in alertesNonVues.slice(0, 5)" 
          :key="alerte.id_alerte"
          :class="['alerte-item', alerte.type_alerte]"
        >
          <span class="alerte-icon">{{ getAlerteIcon(alerte.type_alerte) }}</span>
          <div class="alerte-content">
            <strong>{{ alerte.produit_nom }}</strong>
            <p>{{ alerte.message }}</p>
            <small>{{ formatDate(alerte.date_alerte) }}</small>
          </div>
          <div class="alerte-actions">
            <button @click="viewProduct(alerte.id_produit)" class="btn-view">Voir</button>
            <button @click="markAlerteAsVue(alerte.id_alerte)" class="btn-mark-vue">✓</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Barre de recherche et filtres -->
    <div class="products-filters">
      <div class="search-box">
        <input 
          v-model="searchQuery" 
          @input="handleSearch"
          type="text" 
          placeholder="Rechercher un produit (nom ou code)..." 
          class="search-input"
        />
      </div>
      <div class="filter-buttons">
        <button 
          @click="filterStatus = null" 
          :class="['filter-btn', { active: filterStatus === null }]"
        >
          Tous
        </button>
        <button 
          @click="filterStatus = 'normal'" 
          :class="['filter-btn', { active: filterStatus === 'normal' }]"
        >
          En Stock
        </button>
        <button 
          @click="filterStatus = 'alerte'" 
          :class="['filter-btn', { active: filterStatus === 'alerte' }]"
        >
          Alerte
        </button>
        <button 
          @click="filterStatus = 'rupture'" 
          :class="['filter-btn', { active: filterStatus === 'rupture' }]"
        >
          Rupture
        </button>
      </div>
    </div>

    <!-- Tableau des produits -->
    <div class="products-table-container">
            <table class="products-table">
              <thead>
                <tr>
                  <th>Code</th>
                  <th>Nom du Produit</th>
                  <th>Prix Achat</th>
                  <th>Prix Vente</th>
                  <th>Marge</th>
                  <th>Stock Actuel</th>
                  <th>Seuil Min.</th>
                  <th>Statut Stock</th>
                  <th>Entrepôt</th>
                  <th>Valeur Stock</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="loading">
                  <td colspan="11" class="loading-cell">Chargement...</td>
                </tr>
                <tr v-else-if="filteredProducts.length === 0">
                  <td colspan="11" class="empty-cell">Aucun produit trouvé</td>
                </tr>
                <tr v-else v-for="product in filteredProducts" :key="product.id_produit">
                  <td><strong>{{ product.code_produit }}</strong></td>
                  <td style="vertical-align: middle;">
                    <span class="product-name">
                      <span class="product-icon" :title="product.nom">{{ getProductIcon(product) }}</span>
                      {{ product.nom }}
                    </span>
                  </td>
                  <td>{{ formatCurrency(product.prix_achat) }}</td>
                  <td>{{ formatCurrency(product.prix_vente) }}</td>
                  <td class="marge-cell" :class="getMargeClass(product.marge_beneficiaire)">
                    {{ formatCurrency(product.marge_beneficiaire) }}
                  </td>
                  <td>
                    <div class="stock-info">
                      <span :class="['stock-badge', getStockClass(product.statut_stock)]">
                        {{ product.quantite_stock }}
                      </span>
                      <button 
                        @click="openStockModal(product)" 
                        class="btn-stock-edit"
                        title="Ajuster le stock"
                      >
                        📝
                      </button>
                    </div>
                  </td>
                  <td>
                    <span class="seuil-badge">
                      {{ product.seuil_minimum }}
                    </span>
                  </td>
                  <td>
                    <span :class="['status-badge', product.statut_stock]">
                      {{ getStatusLabel(product.statut_stock) }}
                    </span>
                  </td>
                  <td>
                    <span class="entrepot-badge">{{ product.entrepot || 'Magasin' }}</span>
                  </td>
                  <td class="valeur-stock-cell">
                    {{ formatCurrency((product.quantite_stock || 0) * (product.prix_achat || 0)) }}
                  </td>
                  <td class="actions-cell">
                    <button @click="openEntreeModal(product)" class="btn-entree" title="Entrée stock">➕</button>
                    <button @click="openSortieModal(product)" class="btn-sortie" title="Sortie stock">➖</button>
                    <button @click="openHistoryModal(product)" class="btn-history" title="Historique">📋</button>
                    <button @click="openEditModal(product)" class="btn-edit" title="Modifier">✏️</button>
                    <button @click="confirmDelete(product)" class="btn-delete" title="Supprimer">🗑️</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
  </div>

  <!-- Modal Entrée de Stock -->
  <div v-if="showEntreeModal" class="modal-overlay" @click="closeEntreeModal">
      <div class="modal-content stock-modal" @click.stop>
        <div class="modal-header">
          <h3>Entrée de Stock - {{ stockProduct?.nom }}</h3>
          <button @click="closeEntreeModal" class="modal-close">×</button>
        </div>
        <form @submit.prevent="saveEntree" class="stock-form">
          <div class="form-group">
            <label>Quantité *</label>
            <input 
              v-model.number="entreeData.quantite" 
              type="number" 
              min="1"
              required
              placeholder="0"
            />
          </div>
          <div class="form-group">
            <label>Numéro de Bon de Réception</label>
            <input 
              v-model="entreeData.numero_bon" 
              type="text"
              placeholder="BON-001"
            />
          </div>
          <div class="form-group">
            <label>Notes</label>
            <textarea 
              v-model="entreeData.notes" 
              rows="3"
              placeholder="Notes supplémentaires..."
            ></textarea>
          </div>
          <div class="modal-actions">
            <button type="button" @click="closeEntreeModal" class="btn-cancel">Annuler</button>
            <button type="submit" class="btn-save" :disabled="saving">
              {{ saving ? 'Enregistrement...' : 'Enregistrer l\'entrée' }}
            </button>
          </div>
        </form>
      </div>
    </div>

  <!-- Modal Sortie de Stock -->
  <div v-if="showSortieModal" class="modal-overlay" @click="closeSortieModal">
    <div class="modal-content stock-modal" @click.stop>
        <div class="modal-header">
          <h3>Sortie de Stock - {{ stockProduct?.nom }}</h3>
          <button @click="closeSortieModal" class="modal-close">×</button>
        </div>
        <form @submit.prevent="saveSortie" class="stock-form">
          <div class="form-group">
            <label>Quantité *</label>
            <input 
              v-model.number="sortieData.quantite" 
              type="number" 
              min="1"
              :max="stockProduct?.quantite_stock || 0"
              required
              placeholder="0"
            />
            <small>Stock disponible: {{ stockProduct?.quantite_stock || 0 }}</small>
          </div>
          <div class="form-group">
            <label>Type de Sortie *</label>
            <select v-model="sortieData.type_sortie" required @change="handleSortieTypeChange">
              <option value="vente">Vente</option>
              <option value="perte">Perte</option>
              <option value="transfert">Transfert</option>
              <option value="retour">Retour</option>
              <option value="autre">Autre</option>
            </select>
          </div>
          <div v-if="sortieData.type_sortie === 'transfert'" class="form-group">
            <label>Entrepôt de destination *</label>
            <input 
              v-model="sortieData.entrepot_destination" 
              type="text" 
              required
              placeholder="Nom de l'entrepôt de destination"
            />
          </div>
          <div class="form-group">
            <label>Motif</label>
            <textarea 
              v-model="sortieData.motif" 
              rows="3"
              placeholder="Raison de la sortie..."
            ></textarea>
          </div>
          <div class="modal-actions">
            <button type="button" @click="closeSortieModal" class="btn-cancel">Annuler</button>
            <button type="submit" class="btn-save" :disabled="saving">
              {{ saving ? 'Enregistrement...' : 'Enregistrer la sortie' }}
            </button>
          </div>
        </form>
      </div>
  </div>

  <!-- Modal Historique -->
  <div v-if="showHistoryModal" class="modal-overlay" @click="closeHistoryModal">
      <div class="modal-content history-modal" @click.stop>
        <div class="modal-header">
          <h3>Historique des Mouvements - {{ historyProduct?.nom }}</h3>
          <button @click="closeHistoryModal" class="modal-close">×</button>
        </div>
        <div class="history-content">
          <div v-if="loadingHistory" class="loading-cell">Chargement...</div>
          <div v-else-if="productHistory.length === 0" class="empty-cell">Aucun mouvement enregistré</div>
          <div v-else class="history-list">
            <div 
              v-for="movement in productHistory" 
              :key="movement.id_entree || movement.id_sortie"
              :class="['history-item', movement.type]"
            >
              <div class="history-icon">
                {{ movement.type === 'entree' ? '➕' : '➖' }}
              </div>
              <div class="history-details">
                <div class="history-header">
                  <strong>{{ movement.type === 'entree' ? 'Entrée' : 'Sortie' }}</strong>
                  <span class="history-date">{{ formatDateTime(movement.date_entree || movement.date_sortie) }}</span>
                </div>
                <div class="history-info">
                  <span>Quantité: <strong>{{ movement.quantite }}</strong></span>
                  <span v-if="movement.prix_unitaire">
                    Prix: <strong>{{ formatCurrency(movement.prix_unitaire) }}</strong>
                  </span>
                  <span v-if="movement.type_sortie">
                    Type: <strong>{{ getSortieTypeLabel(movement.type_sortie) }}</strong>
                  </span>
                </div>
                <div v-if="movement.motif || movement.notes" class="history-notes">
                  {{ movement.motif || movement.notes }}
                </div>
                <div class="history-user">
                  Par: {{ movement.user_prenom }} {{ movement.user_nom }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
  </div>

  <!-- Modal d'ajustement de stock -->
    <div v-if="showStockModal" class="modal-overlay" @click="closeStockModal">
      <div class="modal-content stock-modal" @click.stop>
        <div class="modal-header">
          <h3>Ajuster le Stock - {{ stockProduct?.nom }}</h3>
          <button @click="closeStockModal" class="modal-close">×</button>
        </div>
        <div class="stock-adjustment">
          <div class="current-stock">
            <label>Stock Actuel</label>
            <div class="stock-value">{{ stockProduct?.quantite_stock || 0 }}</div>
          </div>
          <div class="stock-actions">
            <div class="stock-action-group">
              <label>Type d'ajustement</label>
              <select v-model="stockAdjustment.type" class="stock-select">
                <option value="set">Définir la quantité</option>
                <option value="add">Ajouter</option>
                <option value="remove">Retirer</option>
              </select>
            </div>
            <div class="stock-action-group">
              <label>Quantité</label>
              <input 
                v-model.number="stockAdjustment.quantity" 
                type="number" 
                min="0"
                class="stock-input"
                placeholder="0"
              />
            </div>
          </div>
          <div v-if="stockAdjustment.type === 'set'" class="stock-preview">
            Nouveau stock: <strong>{{ stockAdjustment.quantity }}</strong>
          </div>
          <div v-else-if="stockAdjustment.type === 'add'" class="stock-preview">
            Nouveau stock: <strong>{{ (stockProduct?.quantite_stock || 0) + (stockAdjustment.quantity || 0) }}</strong>
          </div>
          <div v-else-if="stockAdjustment.type === 'remove'" class="stock-preview">
            Nouveau stock: <strong>{{ Math.max(0, (stockProduct?.quantite_stock || 0) - (stockAdjustment.quantity || 0)) }}</strong>
          </div>
          <div class="modal-actions">
            <button type="button" @click="closeStockModal" class="btn-cancel">Annuler</button>
            <button @click="saveStockAdjustment" class="btn-save" :disabled="saving">
              {{ saving ? 'Enregistrement...' : 'Enregistrer' }}
            </button>
          </div>
        </div>
      </div>
  </div>

  <!-- Modal de création/édition -->
  <div v-if="showModal" class="modal-overlay" @click="closeModal">
    <div class="modal-content" @click.stop>
      <div class="modal-header">
        <h3>{{ editingProduct ? 'Modifier le Produit' : 'Nouveau Produit' }}</h3>
        <button @click="closeModal" class="modal-close">×</button>
      </div>
      <form @submit.prevent="saveProduct" class="product-form">
        <div class="form-row">
            <div class="form-group">
              <label>Code Produit</label>
              <input 
                v-model="formData.code_produit" 
                type="text" 
              />
              <small class="form-hint">Si vide, un code sera généré automatiquement</small>
            </div>
            <div class="form-group">
              <label>Nom de produit (Libellé) *</label>
              <input 
                v-model="formData.nom" 
                type="text" 
                required 
                placeholder="Nom du produit"
              />
            </div>
          </div>
          
          <div class="form-row">
            <div class="form-group">
              <label>Prix d'Achat *</label>
              <input 
                v-model.number="formData.prix_achat" 
                type="number" 
                step="0.01" 
                required 
                min="0"
                placeholder="0.00"
                :class="getPriceValidationClass()"
              />
            </div>
            <div class="form-group">
              <label>Prix de Vente *</label>
              <input 
                v-model.number="formData.prix_vente" 
                type="number" 
                step="0.01" 
                required 
                min="0"
                placeholder="0.00"
                :class="getPriceValidationClass()"
              />
              <small v-if="formData.prix_achat && formData.prix_vente" :class="['price-validation-hint', getPriceValidationClass()]">
                <span v-if="formData.prix_vente < formData.prix_achat">⚠️ Le prix de vente doit être supérieur ou égal au prix d'achat</span>
                <span v-else-if="formData.prix_vente === formData.prix_achat">⚠️ Pas de marge bénéficiaire</span>
                <span v-else>✅ Marge bénéficiaire: {{ formatCurrency(formData.prix_vente - formData.prix_achat) }}</span>
              </small>
            </div>
          </div>

          <!-- Section Stock -->
          <div class="form-section">
            <h4 class="section-title">📦 Gestion du Stock</h4>
            <div class="form-row">
              <div class="form-group">
                <label>Quantité en Stock *</label>
                <input 
                  v-model.number="formData.quantite_stock" 
                  type="number" 
                  min="0"
                  placeholder="0"
                  required
                />
                <small class="form-hint">Quantité actuellement disponible</small>
              </div>
              <div class="form-group">
                <label>Unité</label>
                <select v-model="formData.unite">
                  <option value="unité">Unité</option>
                  <option value="kg">Kg</option>
                  <option value="g">g</option>
                  <option value="l">Litre</option>
                  <option value="ml">ml</option>
                  <option value="m">m</option>
                  <option value="cm">cm</option>
                  <option value="paquet">Paquet</option>
                  <option value="boîte">Boîte</option>
                  <option value="carton">Carton</option>
                </select>
                <small class="form-hint">Sélectionnez l'unité</small>
              </div>
              <div class="form-group">
                <label>Seuil Minimum d'Alerte *</label>
                <input 
                  v-model.number="formData.seuil_minimum" 
                  type="number" 
                  min="0"
                  placeholder="0"
                  required
                />
                <small class="form-hint">Alerte quand le stock atteint ce niveau</small>
              </div>
            </div>
            <div v-if="formData.quantite_stock !== null && formData.seuil_minimum !== null" class="stock-status-preview">
              <span :class="['stock-status-indicator', getStockStatusClass(formData.quantite_stock, formData.seuil_minimum)]">
                {{ getStockStatusText(formData.quantite_stock, formData.seuil_minimum) }}
              </span>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Date d'Expiration</label>
              <input 
                v-model="formData.date_expiration" 
                type="date"
              />
            </div>
            <div class="form-group">
              <label>Statut</label>
              <select v-model="formData.actif">
                <option :value="1">Actif</option>
                <option :value="0">Inactif</option>
              </select>
            </div>
          </div>

          <div v-if="formData.prix_achat && formData.prix_vente" class="marge-preview" :class="getPriceValidationClass()">
            <strong>Marge bénéficiaire: </strong>
            <span :class="getMargeClass(formData.prix_vente - formData.prix_achat)">
              {{ formatCurrency(formData.prix_vente - formData.prix_achat) }}
            </span>
          </div>

          <div class="modal-actions">
            <button type="button" @click="closeModal" class="btn-cancel">Annuler</button>
            <button type="submit" class="btn-save" :disabled="saving">
              {{ saving ? 'Enregistrement...' : 'Enregistrer' }}
            </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal tous les mouvements -->
  <div v-if="showAllMovementsModal" class="modal-overlay" @click="closeAllMovementsModal">
    <div class="modal-content movements-modal" @click.stop>
      <div class="modal-header">
        <h3>Tous les mouvements de stock</h3>
        <button @click="closeAllMovementsModal" class="modal-close">×</button>
      </div>
      <div class="movements-table-container">
        <table class="movements-table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Type</th>
              <th>Produit</th>
              <th>Quantité</th>
              <th>Détails</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="allMovements.length === 0">
              <td colspan="5" class="empty-cell">Aucun mouvement enregistré</td>
            </tr>
            <tr v-else v-for="m in allMovements" :key="m.id">
              <td>{{ formatDateTime(m.date) }}</td>
              <td>
                <span class="movement-type-badge" :class="m.type">{{ m.typeLabel }}</span>
              </td>
              <td><strong>{{ m.nom }}</strong></td>
              <td>{{ m.quantite }}</td>
              <td class="text-muted">{{ m.details }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Modale de notification -->
  <div v-if="notification.show" class="modal-overlay notification-overlay" @click="closeNotification">
    <div class="modal-content notification-modal" :class="notification.type" @click.stop>
      <div class="notification-header">
        <span class="notification-icon">
          <span v-if="notification.type === 'success'">✅</span>
          <span v-else-if="notification.type === 'error'">❌</span>
          <span v-else>ℹ️</span>
        </span>
        <h3>{{ notification.title }}</h3>
        <button @click="closeNotification" class="modal-close">×</button>
      </div>
      <div class="notification-body">
        <p>{{ notification.message }}</p>
      </div>
      <div class="notification-actions">
        <button @click="closeNotification" class="btn-primary">OK</button>
      </div>
    </div>
  </div>

  <!-- Modale de confirmation -->
  <div v-if="confirmation.show" class="modal-overlay confirmation-overlay" @click="closeConfirmation">
    <div class="modal-content confirmation-modal" @click.stop>
      <div class="confirmation-header">
        <span class="confirmation-icon">⚠️</span>
        <h3>{{ confirmation.title }}</h3>
      </div>
      <div class="confirmation-body">
        <p>{{ confirmation.message }}</p>
      </div>
      <div class="confirmation-actions">
        <button @click="closeConfirmation" class="btn-cancel">Annuler</button>
        <button @click="confirmAction" class="btn-danger">Confirmer</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, inject } from 'vue'
import { useRouter } from 'vue-router'
import { apiService } from '../composables/Api/apiService.js'
import StatCard from '../components/StatCard.vue'
import { useCurrency } from '../composables/useCurrency.js'

const router = useRouter()
const { formatPrice: formatCurrency } = useCurrency()
const products = ref([])
const loading = ref(false)
const saving = ref(false)
const searchQuery = ref('')
const filterStatus = ref(null)
const showModal = ref(false)
const showStockModal = ref(false)
const showEntreeModal = ref(false)
const showSortieModal = ref(false)
const showHistoryModal = ref(false)
const showAllMovementsModal = ref(false)
const editingProduct = ref(null)
const stockProduct = ref(null)
const historyProduct = ref(null)
const productHistory = ref([])
const loadingHistory = ref(false)
const alertes = ref([])
const alertesNonVues = ref([])
const stockAdjustment = ref({
  type: 'set',
  quantity: 0
})

// États pour les notifications et confirmations
const notification = ref({
  show: false,
  type: 'success', // 'success', 'error', 'info'
  title: '',
  message: ''
})

const confirmation = ref({
  show: false,
  title: '',
  message: '',
  onConfirm: null
})
const entreeData = ref({
  quantite: 0,
  prix_unitaire: 0,
  numero_bon: '',
  notes: ''
})
const sortieData = ref({
  quantite: 0,
  type_sortie: 'vente',
  prix_unitaire: null,
  motif: ''
})

const formData = ref({
  code_produit: '',
  nom: '',
  id_categorie: null,
  prix_achat: 0,
  prix_vente: 0,
  quantite_stock: 0,
  seuil_minimum: 0,
  date_expiration: '',
  unite: 'unité',
  entrepot: 'Magasin',
  actif: 1
})

// Statistiques calculées
const stats = computed(() => {
  const total = products.value.length
  // Valeur stock à l'achat = quantite_stock * prix_achat (calcul exact)
  const valeurStockAchat = products.value.reduce((sum, p) => {
    const qte = Number(p.quantite_stock) || 0
    const prixAchat = Number(p.prix_achat) || 0
    return sum + (qte * prixAchat)
  }, 0)
  // Valeur stock à la vente = quantite_stock * prix_vente (calcul exact)
  const valeurStockVente = products.value.reduce((sum, p) => {
    const qte = Number(p.quantite_stock) || 0
    const prixVente = Number(p.prix_vente) || 0
    return sum + (qte * prixVente)
  }, 0)
  const produitsAlerte = products.value.filter(p => p.statut_stock === 'alerte' || p.statut_stock === 'rupture').length
  const stockTotal = products.value.reduce((sum, p) => sum + (Number(p.quantite_stock) || 0), 0)
  const margeMoyenne = products.value.length > 0
    ? products.value.reduce((sum, p) => sum + (Number(p.marge_beneficiaire) || 0), 0) / products.value.length
    : 0
  
  return {
    totalProduits: total,
    valeurStockAchat,
    valeurStockVente,
    produitsAlerte,
    stockTotal,
    margeMoyenne
  }
})

const lowStockProducts = computed(() => {
  return products.value
    .filter(p => p.statut_stock === 'alerte' || p.statut_stock === 'rupture')
    .slice(0, 5)
})

// Tous les mouvements
const allMovements = computed(() => {
  return products.value
    .filter(p => p.date_modification || p.date_creation)
    .sort((a, b) => new Date(b.date_modification || b.date_creation) - new Date(a.date_modification || a.date_creation))
    .map((p, idx) => ({
      id: p.id_produit + '-' + idx,
      type: p.statut_stock === 'rupture' ? 'sortie' : 'entree',
      typeLabel: p.statut_stock === 'rupture' ? 'Sortie' : 'Entrée',
      date: p.date_modification || p.date_creation,
      nom: p.nom,
      quantite: p.quantite_stock,
      details: p.code_produit ? `Code: ${p.code_produit}` : ''
    }))
})

// Mouvements récents (limité à 3)
const recentMovements = computed(() => {
  return allMovements.value.slice(0, 3)
})

// Produits filtrés
const filteredProducts = computed(() => {
  let filtered = products.value

  // Filtre par recherche
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(p => 
      p.nom.toLowerCase().includes(query) || 
      p.code_produit.toLowerCase().includes(query)
    )
  }

  // Filtre par statut
  if (filterStatus.value) {
    filtered = filtered.filter(p => p.statut_stock === filterStatus.value)
  }

  return filtered
})

// Charger les produits
const loadProducts = async () => {
  loading.value = true
  try {
    const response = await apiService.get('/api_produit.php?action=all')
    if (response.success) {
      products.value = response.data
      // Générer les alertes après chargement des produits
      await generateAlertes()
      await loadAlertes()
    }
  } catch (error) {
    console.error('Erreur lors du chargement des produits:', error)
    alert('Erreur lors du chargement des produits')
  } finally {
    loading.value = false
  }
}

// Charger les alertes
const loadAlertes = async () => {
  try {
    const response = await apiService.get('/api_alerte.php?action=all&non_vues=true')
    if (response.success) {
      alertes.value = response.data
      alertesNonVues.value = response.data.filter(a => !a.vue)
    }
  } catch (error) {
    console.error('Erreur lors du chargement des alertes:', error)
  }
}

// Générer les alertes automatiquement
const generateAlertes = async () => {
  try {
    await apiService.get('/api_alerte.php?action=generate')
  } catch (error) {
    console.error('Erreur lors de la génération des alertes:', error)
  }
}

// Marquer une alerte comme vue
const markAlerteAsVue = async (alerteId) => {
  try {
    const response = await apiService.put(`/api_alerte.php?id=${alerteId}&action=vue`)
    if (response.success) {
      await loadAlertes()
    }
  } catch (error) {
    console.error('Erreur lors du marquage de l\'alerte:', error)
  }
}

// Marquer toutes les alertes comme vues
const markAllAlertesAsVue = async () => {
  try {
    const response = await apiService.put('/api_alerte.php?action=vue_all')
    if (response.success) {
      await loadAlertes()
    }
  } catch (error) {
    console.error('Erreur lors du marquage des alertes:', error)
  }
}

// Voir un produit depuis une alerte
const viewProduct = (productId) => {
  const product = products.value.find(p => p.id_produit === productId)
  if (product) {
    openEditModal(product)
  }
}

// Recherche
const handleSearch = () => {
  // La recherche est gérée par le computed filteredProducts
}

// Ouvrir modal de création
const openCreateModal = () => {
  editingProduct.value = null
  formData.value = {
    code_produit: '',
    nom: '',
    id_categorie: null,
    prix_achat: 0,
    prix_vente: 0,
    quantite_stock: 0,
    seuil_minimum: 0,
    date_expiration: '',
    actif: 1
  }
  showModal.value = true
}

// Ouvrir modal d'édition
const openEditModal = (product) => {
  editingProduct.value = product
  formData.value = {
    code_produit: product.code_produit,
    nom: product.nom,
    id_categorie: product.id_categorie,
    prix_achat: parseFloat(product.prix_achat),
    prix_vente: parseFloat(product.prix_vente),
    quantite_stock: parseInt(product.quantite_stock),
    seuil_minimum: parseInt(product.seuil_minimum),
    date_expiration: product.date_expiration || '',
    unite: product.unite || 'unité',
    entrepot: product.entrepot || 'Magasin',
    actif: product.actif ? 1 : 0
  }
  showModal.value = true
}

// Fermer modal
const closeModal = () => {
  showModal.value = false
  editingProduct.value = null
}

// Générer un code produit automatiquement
const generateProductCode = (nom) => {
  if (!nom) return 'PROD-' + Date.now()
  // Prendre les 3 premières lettres du nom en majuscules
  const prefix = nom.substring(0, 3).toUpperCase().replace(/[^A-Z]/g, '') || 'PROD'
  // Ajouter un timestamp pour l'unicité
  const timestamp = Date.now().toString().slice(-6)
  return `${prefix}-${timestamp}`
}

// Validation des prix
const getPriceValidationClass = () => {
  if (!formData.value.prix_achat || !formData.value.prix_vente) return ''
  if (formData.value.prix_vente < formData.value.prix_achat) return 'price-invalid'
  if (formData.value.prix_vente === formData.value.prix_achat) return 'price-warning'
  return 'price-valid'
}

// Sauvegarder produit
const saveProduct = async () => {
  // Validation : prix de vente doit être >= prix d'achat
  if (formData.value.prix_vente < formData.value.prix_achat) {
    showNotification('error', 'Erreur de validation', 'Le prix de vente doit être supérieur ou égal au prix d\'achat')
    return
  }

  // Générer le code produit si vide
  const dataToSave = { ...formData.value }
  if (!dataToSave.code_produit || dataToSave.code_produit.trim() === '') {
    dataToSave.code_produit = generateProductCode(dataToSave.nom)
  }

  saving.value = true
  try {
    if (editingProduct.value) {
      // Mise à jour
      const response = await apiService.put(`/api_produit.php?id=${editingProduct.value.id_produit}`, dataToSave)
      if (response.success) {
        await loadProducts()
        closeModal()
        showNotification('success', 'Succès', 'Produit modifié avec succès')
      }
    } else {
      // Création
      const response = await apiService.post('/api_produit.php', dataToSave)
      if (response.success) {
        await loadProducts()
        closeModal()
        showNotification('success', 'Succès', 'Produit créé avec succès')
      }
    }
  } catch (error) {
    console.error('Erreur lors de la sauvegarde:', error)
    showNotification('error', 'Erreur', 'Erreur lors de la sauvegarde du produit')
  } finally {
    saving.value = false
  }
}

// Fonctions pour les notifications
const showNotification = (type, title, message) => {
  notification.value = {
    show: true,
    type,
    title,
    message
  }
  // Auto-fermeture après 3 secondes pour les succès
  if (type === 'success') {
    setTimeout(() => {
      closeNotification()
    }, 3000)
  }
}

const closeNotification = () => {
  notification.value.show = false
}

// Fonctions pour les confirmations
const showConfirmation = (title, message, onConfirm) => {
  confirmation.value = {
    show: true,
    title,
    message,
    onConfirm
  }
}

const closeConfirmation = () => {
  confirmation.value.show = false
  confirmation.value.onConfirm = null
}

const confirmAction = () => {
  if (confirmation.value.onConfirm) {
    confirmation.value.onConfirm()
  }
  closeConfirmation()
}

// Confirmer suppression
const confirmDelete = (product) => {
  showConfirmation(
    'Confirmer la suppression',
    `Êtes-vous sûr de vouloir supprimer le produit "${product.nom}" ? Cette action est irréversible.`,
    () => deleteProduct(product.id_produit)
  )
}

// Supprimer produit
const deleteProduct = async (productId) => {
  try {
    const response = await apiService.delete(`/api_produit.php?id=${productId}`)
    if (response.success) {
      await loadProducts()
      showNotification('success', 'Succès', 'Produit supprimé avec succès')
    }
  } catch (error) {
    console.error('Erreur lors de la suppression:', error)
    showNotification('error', 'Erreur', 'Erreur lors de la suppression du produit')
  }
}

// Ouvrir modal d'ajustement de stock
const openStockModal = (product) => {
  stockProduct.value = product
  stockAdjustment.value = {
    type: 'set',
    quantity: product.quantite_stock
  }
  showStockModal.value = true
}

// Fermer modal de stock
const closeStockModal = () => {
  showStockModal.value = false
  stockProduct.value = null
  stockAdjustment.value = { type: 'set', quantity: 0 }
}

// Sauvegarder ajustement de stock
const saveStockAdjustment = async () => {
  if (!stockProduct.value) return
  
  saving.value = true
  try {
    let newQuantity = stockProduct.value.quantite_stock
    
    if (stockAdjustment.value.type === 'set') {
      newQuantity = stockAdjustment.value.quantity
    } else if (stockAdjustment.value.type === 'add') {
      newQuantity = stockProduct.value.quantite_stock + stockAdjustment.value.quantity
    } else if (stockAdjustment.value.type === 'remove') {
      newQuantity = Math.max(0, stockProduct.value.quantite_stock - stockAdjustment.value.quantity)
    }
    
    const response = await apiService.put(`/api_produit.php?id=${stockProduct.value.id_produit}`, {
      quantite_stock: newQuantity
    })
    
    if (response.success) {
      await loadProducts()
      closeStockModal()
      showNotification('success', 'Succès', 'Stock ajusté avec succès')
    }
  } catch (error) {
    console.error('Erreur lors de l\'ajustement du stock:', error)
    showNotification('error', 'Erreur', 'Erreur lors de l\'ajustement du stock')
  } finally {
    saving.value = false
  }
}

// Utilitaires pour le statut du stock
const getStockStatusClass = (quantite, seuil) => {
  if (quantite === 0) return 'rupture'
  if (quantite <= seuil) return 'alerte'
  return 'normal'
}

const getStockStatusText = (quantite, seuil) => {
  if (quantite === 0) return '⚠️ Rupture de stock'
  if (quantite <= seuil) return '⚠️ Stock faible (alerte)'
  return '✅ Stock normal'
}

// formatCurrency est maintenant fourni par useCurrency() via formatPrice
// Les valeurs sont supposées être en XOF (F CFA) par défaut dans la base de données

const getMargeClass = (marge) => {
  if (marge > 0) return 'marge-positive'
  if (marge < 0) return 'marge-negative'
  return 'marge-zero'
}

const getStockClass = (status) => {
  return status
}

const getStatusLabel = (status) => {
  const labels = {
    'normal': 'Normal',
    'alerte': 'Alerte',
    'rupture': 'Rupture'
  }
  return labels[status] || status
}

// Icônes automatiques selon le nom/catégorie du produit
const getProductIcon = (product) => {
  const name = (product.nom || '').toLowerCase()
  const mapping = [
    { key: ['boisson', 'café', 'jus', 'soda', 'eau'], icon: '🥤' },
    { key: ['aliment', 'repas', 'snack', 'biscuit', 'viande', 'poisson'], icon: '🍽️' },
    { key: ['fruit', 'légume', 'legume'], icon: '🍎' },
    { key: ['pain', 'boulanger', 'pâtisserie', 'patisserie'], icon: '🥐' },
    { key: ['lait', 'fromage', 'yaourt'], icon: '🥛' },
    { key: ['ordinateur', 'laptop', 'pc', 'portable'], icon: '💻' },
    { key: ['téléphone', 'telephone', 'smartphone', 'iphone', 'android'], icon: '📱' },
    { key: ['imprimante', 'scanner'], icon: '🖨️' },
    { key: ['tv', 'télé', 'tele', 'écran', 'ecran'], icon: '📺' },
    { key: ['routeur', 'modem', 'wifi'], icon: '📡' },
    { key: ['chaussure', 'basket'], icon: '👟' },
    { key: ['vêtement', 'vetement', 't-shirt', 'pantalon', 'robe'], icon: '👕' },
    { key: ['beauté', 'cosmetique', 'cosmétique'], icon: '💄' },
    { key: ['santé', 'pharma', 'pharmacie', 'médicament', 'medicament'], icon: '💊' },
    { key: ['outil', 'matériel', 'materiel'], icon: '🛠️' },
    { key: ['auto', 'voiture', 'pneu', 'huile'], icon: '🚗' },
    { key: ['jouet', 'enfant', 'lego'], icon: '🧸' },
    { key: ['sport', 'ballon', 'fitness'], icon: '🏀' },
    { key: ['meuble', 'chaise', 'table', 'bureau'], icon: '🪑' }
  ]
  for (const map of mapping) {
    if (map.key.some(k => name.includes(k))) return map.icon
  }
  return '📦'
}

// Ouvrir modal entrée
const openEntreeModal = (product) => {
  stockProduct.value = product
  entreeData.value = {
    quantite: 0,
    prix_unitaire: product.prix_achat || 0, // Gardé pour l'API mais non affiché
    numero_bon: '',
    notes: ''
  }
  showEntreeModal.value = true
}

// Fermer modal entrée
const closeEntreeModal = () => {
  showEntreeModal.value = false
  stockProduct.value = null
  entreeData.value = { quantite: 0, prix_unitaire: 0, numero_bon: '', notes: '' }
}

// Sauvegarder entrée (utilise le prix d'achat du produit)
const saveEntree = async () => {
  if (!stockProduct.value) return
  
  saving.value = true
  try {
    // Utiliser le prix d'achat du produit actuel
    const response = await apiService.post(`/api_stock.php?type=entree`, {
      id_produit: stockProduct.value.id_produit,
      quantite: entreeData.value.quantite,
      prix_unitaire: stockProduct.value.prix_achat || 0,
      numero_bon: entreeData.value.numero_bon,
      notes: entreeData.value.notes
    })
    
    if (response.success) {
      await loadProducts()
      closeEntreeModal()
      showNotification('success', 'Succès', 'Entrée de stock enregistrée avec succès')
    }
  } catch (error) {
    console.error('Erreur lors de l\'entrée:', error)
    showNotification('error', 'Erreur', error.message || 'Erreur lors de l\'enregistrement de l\'entrée')
  } finally {
    saving.value = false
  }
}

// Ouvrir modal sortie
const openSortieModal = (product) => {
  stockProduct.value = product
  sortieData.value = {
    quantite: 0,
    type_sortie: 'vente',
    prix_unitaire: product.prix_vente || null, // Gardé pour l'API mais non affiché
    motif: ''
  }
  showSortieModal.value = true
}

// Fermer modal sortie
const closeSortieModal = () => {
  showSortieModal.value = false
  stockProduct.value = null
  sortieData.value = { quantite: 0, type_sortie: 'vente', prix_unitaire: null, entrepot_destination: '', motif: '' }
}

// Sauvegarder sortie (utilise le prix de vente du produit)
const saveSortie = async () => {
  if (!stockProduct.value) return
  
  // Validation : entrepot_destination requis pour les transferts
  if (sortieData.value.type_sortie === 'transfert' && !sortieData.value.entrepot_destination?.trim()) {
    showNotification('error', 'Erreur de validation', 'L\'entrepôt de destination est requis pour un transfert')
    return
  }
  
  saving.value = true
  try {
    // Utiliser le prix de vente du produit actuel
    const dataToSend = {
      id_produit: stockProduct.value.id_produit,
      quantite: sortieData.value.quantite,
      type_sortie: sortieData.value.type_sortie,
      prix_unitaire: stockProduct.value.prix_vente || null,
      motif: sortieData.value.motif
    }
    
    // Ajouter entrepot_destination si c'est un transfert
    if (sortieData.value.type_sortie === 'transfert' && sortieData.value.entrepot_destination) {
      dataToSend.entrepot_destination = sortieData.value.entrepot_destination
    }
    
    const response = await apiService.post(`/api_stock.php?type=sortie`, dataToSend)
    
    if (response.success) {
      await loadProducts()
      closeSortieModal()
      showNotification('success', 'Succès', 'Sortie de stock enregistrée avec succès')
    }
  } catch (error) {
    console.error('Erreur lors de la sortie:', error)
    showNotification('error', 'Erreur', error.message || 'Erreur lors de l\'enregistrement de la sortie')
  } finally {
    saving.value = false
  }
}

// Ouvrir modal historique
const openHistoryModal = async (product) => {
  historyProduct.value = product
  loadingHistory.value = true
  showHistoryModal.value = true
  
  try {
    const response = await apiService.get(`/api_stock.php?action=history&product_id=${product.id_produit}`)
    if (response.success) {
      productHistory.value = response.data
    }
  } catch (error) {
    console.error('Erreur lors du chargement de l\'historique:', error)
    productHistory.value = []
  } finally {
    loadingHistory.value = false
  }
}

// Fermer modal historique
const closeHistoryModal = () => {
  showHistoryModal.value = false
  historyProduct.value = null
  productHistory.value = []
}

// Ouvrir modal tous les mouvements
const openAllMovementsModal = () => {
  showAllMovementsModal.value = true
}

// Fermer modal tous les mouvements
const closeAllMovementsModal = () => {
  showAllMovementsModal.value = false
}

// Utilitaires supplémentaires
const getAlerteIcon = (type) => {
  const icons = {
    'rupture': '🔴',
    'stock_faible': '🟡',
    'expiration': '⏰',
    'autre': '⚠️'
  }
  return icons[type] || '⚠️'
}

const formatDate = (dateString) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleDateString('fr-FR', { 
    day: '2-digit', 
    month: '2-digit', 
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatDateTime = (dateString) => {
  return formatDate(dateString)
}

const formatShortDate = (dateString) => {
  if (!dateString) return ''
  const d = new Date(dateString)
  return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: '2-digit' })
}

const getSortieTypeLabel = (type) => {
  const labels = {
    'vente': 'Vente',
    'perte': 'Perte',
    'transfert': 'Transfert',
    'retour': 'Retour',
    'autre': 'Autre'
  }
  return labels[type] || type
}

const goToEntrepot = () => {
  router.push('/entrepot')
}

onMounted(() => {
  loadProducts()
})
</script>

<style scoped>
/* Page Products - Layout géré par MainLayout */
.products-page {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  width: 100%;
}

.dashboard-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1a5f4a;
  margin-bottom: 1rem;
  letter-spacing: 0.01em;
}

.products-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.btn-primary {
  background: #1a5f4a;
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  transition: background 0.2s;
}

.btn-primary:hover {
  background: #134e3a;
}

.btn-secondary {
  background: #f3f4f6;
  color: #374151;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  transition: background 0.2s;
}

.btn-secondary:hover {
  background: #e5e7eb;
}

.products-filters {
  display: flex;
  gap: 1rem;
  margin-bottom: 0.5rem;
  align-items: center;
  justify-content: flex-start;
}

.search-box {
  flex: 0 0 50%;
  max-width: 480px;
}

.currency-select {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  min-width: 140px;
}

.currency-select select {
  padding: 0.55rem 0.6rem;
  border: 1.5px solid #10b981;
  border-radius: 8px;
  font-size: 0.95rem;
}

.search-input {
  width: 100%;
  padding: 0.75rem;
  border: 1.5px solid #10b981;
  border-radius: 8px;
  font-size: 1rem;
}

.filter-buttons {
  display: flex;
  gap: 0.5rem;
}

.filter-btn {
  padding: 0.5rem 1rem;
  border: 1.5px solid #10b981;
  background: white;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s;
}

.filter-btn.active {
  background: #1a5f4a;
  color: white;
  border-color: #1a5f4a;
}

/* Statistiques alignées horizontalement */
.stats-row {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  justify-content: flex-start;
  margin-bottom: 1rem;
}

.stats-row :deep(.stat-card) {
  flex: 1 1 180px;
  min-width: 180px;
  padding: 0.9rem 1rem 0.7rem 1rem;
}

.stats-row :deep(.stat-title) {
  font-size: 0.9rem;
}

.stats-row :deep(.stat-value) {
  font-size: 1.5rem;
}

/* Blocs synthèse comme la maquette */
.inventory-overview {
  display: flex;
  gap: 1.5rem;
  flex-wrap: wrap;
}

.overview-cards {
  flex: 3 1 0%;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
  gap: 1rem;
}

.overview-card {
  background: #fff;
  border-radius: 16px;
  padding: 1.2rem 1.4rem;
  box-shadow: 0 4px 18px rgba(26, 95, 74, 0.08);
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.card-label {
  font-size: 0.95rem;
  color: #6b7280;
}

.card-value {
  font-size: 1.8rem;
  font-weight: 800;
  color: #1a5f4a;
}

.card-value.warn {
  color: #d97706;
}

.card-value.accent {
  color: #0f766e;
}

.card-sub {
  font-size: 0.9rem;
  color: #9ca3af;
}

.inventory-summary {
  flex: 1 1 240px;
  background: #fff;
  border-radius: 16px;
  box-shadow: 0 4px 18px rgba(26, 95, 74, 0.08);
  padding: 1.4rem;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-weight: 700;
  color: #1f2937;
}

.summary-value {
  font-size: 1.4rem;
}

.summary-value.muted {
  color: #9ca3af;
  font-weight: 600;
}

.summary-footer {
  font-size: 0.9rem;
  color: #6b7280;
}

.inventory-panels {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1rem;
  margin-bottom: 1rem;
  align-items: start;
}

.panel {
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(26, 95, 74, 0.06);
  padding: 1rem 1.2rem;
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
  min-height: auto;
}

.panel-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.panel-title {
  font-weight: 700;
  font-size: 0.95rem;
  color: #1a1a1a;
}

.panel-actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.panel-count {
  background: #fef3c7;
  color: #92400e;
  font-weight: 700;
  padding: 0.25rem 0.6rem;
  border-radius: 8px;
  font-size: 0.85rem;
}

.btn-more {
  background: #1a5f4a;
  color: white;
  border: none;
  border-radius: 6px;
  padding: 0.4rem 0.75rem;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  gap: 0.3rem;
}

.btn-more:hover {
  background: #145040;
  transform: translateY(-1px);
}

.panel-empty {
  color: #9ca3af;
  font-size: 0.95rem;
}

.panel-table {
  width: 100%;
  border-collapse: collapse;
}

.panel-table th,
.panel-table td {
  padding: 0.65rem 0.4rem;
  text-align: left;
  border-bottom: 1px solid #e5e7eb;
  font-size: 0.95rem;
}

.panel-table th {
  color: #6b7280;
  font-weight: 700;
}

.movement-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  overflow: visible;
  max-height: none;
}

.movement-list li {
  background: #f9fafb;
  border-radius: 10px;
  padding: 0.75rem 0.9rem;
  border: 1px solid #e5e7eb;
}

.movement-top {
  display: flex;
  justify-content: space-between;
  font-size: 0.9rem;
  color: #6b7280;
  margin-bottom: 0.3rem;
}

.movement-type {
  font-weight: 700;
  padding: 0.2rem 0.55rem;
  border-radius: 8px;
}

.movement-type.entree {
  background: #e0f2fe;
  color: #075985;
}

.movement-type.sortie {
  background: #fee2e2;
  color: #991b1b;
}

.movement-date {
  color: #9ca3af;
}

.movement-main {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-weight: 700;
  color: #1a1a1a;
}

.movement-qty {
  color: #10b981;
  font-weight: 800;
}

.movement-sub {
  font-size: 0.9rem;
  color: #6b7280;
  margin-top: 0.2rem;
}

.products-table-container {
  background: #ffffff;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  margin-top: 0;
  width: 100%;
}

.products-table {
  width: 100%;
  border-collapse: collapse;
  background: #ffffff;
  display: table;
  table-layout: auto;
}

.products-table thead {
  background: #f9fafb;
  border-bottom: 1px solid #e5e7eb;
}

.products-table th {
  padding: 0.875rem 1rem;
  text-align: left;
  font-size: 0.75rem;
  font-weight: 600;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.products-table td {
  padding: 1rem;
  font-size: 0.875rem;
  color: #1f2937;
  border-bottom: 1px solid #f3f4f6;
  vertical-align: middle;
  line-height: 1.5;
}

.products-table tbody tr {
  transition: background 0.2s;
}

.products-table tbody tr:hover {
  background: #f9fafb;
}

.products-table tbody tr:last-child td {
  border-bottom: none;
}

.product-name {
  font-weight: 600;
  color: #1a1a1a;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  vertical-align: middle;
  line-height: 1.5;
}

.product-icon {
  font-size: 1.2rem;
  display: inline-block;
  vertical-align: middle;
  line-height: 1;
}

/* Masquer la variation % des StatCard si présente */
:deep(.stat-card .variation) {
  display: none !important;
}

.marge-cell.marge-positive {
  color: #10b981;
  font-weight: 600;
}

.marge-cell.marge-negative {
  color: #ef4444;
  font-weight: 600;
}

.stock-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-weight: 600;
  font-size: 0.875rem;
}

.stock-badge.normal {
  background: #d1fae5;
  color: #065f46;
}

.stock-badge.alerte {
  background: #fef3c7;
  color: #92400e;
}

.stock-badge.rupture {
  background: #fee2e2;
  color: #991b1b;
}

.status-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.875rem;
  font-weight: 500;
}

.status-badge.normal {
  background: #d1fae5;
  color: #065f46;
}

.status-badge.alerte {
  background: #fef3c7;
  color: #92400e;
}

.status-badge.rupture {
  background: #fee2e2;
  color: #991b1b;
}

.stock-info {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-stock-edit {
  padding: 0.25rem 0.5rem;
  border: none;
  background: #d1fae5;
  cursor: pointer;
  font-size: 1rem;
  border-radius: 4px;
  transition: background 0.2s;
}

.btn-stock-edit:hover {
  background: #a7f3d0;
}

.seuil-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-weight: 500;
  font-size: 0.875rem;
  background: #e5e7eb;
  color: #374151;
}

.entrepot-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-weight: 600;
  font-size: 0.875rem;
  background: #dbeafe;
  color: #1e40af;
}

.valeur-stock-cell {
  font-weight: 600;
  color: #1a5f4a;
}

.actions-cell {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  grid-template-rows: repeat(2, 1fr);
  gap: 0.3rem;
  width: 120px;
  padding: 0.25rem;
}

.actions-cell button {
  width: 32px;
  height: 32px;
  border: 1px solid #d1d5db;
  border-radius: 50%;
  background: white;
  cursor: pointer;
  font-size: 0.9rem;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
  padding: 0;
  margin: 0;
}

.actions-cell button:hover {
  box-shadow: 0 2px 8px rgba(0,0,0,0.12);
  transform: scale(1.1);
}

.btn-entree { color: #065f46; border-color: #10b981; }
.btn-sortie { color: #991b1b; border-color: #fca5a5; }
.btn-history { color: #1d4ed8; border-color: #bfdbfe; }
.btn-edit { color: #1f2937; border-color: #d1d5db; }
.btn-delete { color: #991b1b; border-color: #fca5a5; }

.loading-cell, .empty-cell {
  text-align: center;
  padding: 2rem;
  color: #6b7280;
}

/* Modal */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  border-radius: 16px;
  width: 90%;
  max-width: 600px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid #e5e7eb;
}

.modal-header h3 {
  margin: 0;
  color: #1a1a1a;
}

.modal-close {
  background: none;
  border: none;
  font-size: 2rem;
  cursor: pointer;
  color: #6b7280;
  line-height: 1;
}

.product-form {
  padding: 1.5rem;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
  margin-bottom: 1rem;
}

.form-group {
  display: flex;
  flex-direction: column;
}

.form-group label {
  margin-bottom: 0.5rem;
  font-weight: 500;
  color: #374151;
}

.form-group input,
.form-group select {
  padding: 0.75rem;
  border: 1.5px solid #10b981;
  border-radius: 8px;
  font-size: 1rem;
  transition: border-color 0.2s, background-color 0.2s;
}

/* Validation des prix */
.form-group input.price-invalid {
  border-color: #ef4444;
  background-color: #fef2f2;
}

.form-group input.price-warning {
  border-color: #f59e0b;
  background-color: #fffbeb;
}

.form-group input.price-valid {
  border-color: #10b981;
  background-color: #f0fdf4;
}

.price-validation-hint {
  display: block;
  margin-top: 0.5rem;
  font-size: 0.875rem;
  font-weight: 500;
  padding: 0.5rem;
  border-radius: 6px;
}

.price-validation-hint.price-invalid {
  color: #991b1b;
  background-color: #fee2e2;
}

.price-validation-hint.price-warning {
  color: #92400e;
  background-color: #fef3c7;
}

.price-validation-hint.price-valid {
  color: #065f46;
  background-color: #d1fae5;
}

.marge-preview {
  padding: 1rem;
  background: #f6faf9;
  border-radius: 8px;
  margin-bottom: 1rem;
}

.modal-actions {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  margin-top: 1.5rem;
}

.btn-cancel {
  padding: 0.75rem 1.5rem;
  border: 1.5px solid #6b7280;
  background: white;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
}

.btn-save {
  padding: 0.75rem 1.5rem;
  background: #1a5f4a;
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
}

.btn-save:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Modal Stock */
.stock-modal {
  max-width: 500px;
}

.stock-adjustment {
  padding: 1.5rem;
}

.current-stock {
  margin-bottom: 1.5rem;
  text-align: center;
  padding: 1rem;
  background: #f6faf9;
  border-radius: 8px;
}

.current-stock label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
  color: #6b7280;
}

.stock-value {
  font-size: 2rem;
  font-weight: 700;
  color: #1a5f4a;
}

.stock-actions {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  margin-bottom: 1rem;
}

.stock-action-group {
  display: flex;
  flex-direction: column;
}

.stock-action-group label {
  margin-bottom: 0.5rem;
  font-weight: 500;
  color: #374151;
}

.stock-select,
.stock-input {
  padding: 0.75rem;
  border: 1.5px solid #10b981;
  border-radius: 8px;
  font-size: 1rem;
}

.stock-preview {
  padding: 1rem;
  background: #f6faf9;
  border-radius: 8px;
  margin-bottom: 1rem;
  text-align: center;
  font-size: 1.1rem;
}

/* Section Stock dans le formulaire */
.form-section {
  margin-bottom: 1.5rem;
  padding: 1.5rem;
  background: #f6faf9;
  border-radius: 12px;
  border: 2px solid #1a5f4a22;
}

.section-title {
  margin: 0 0 1rem 0;
  color: #1a5f4a;
  font-size: 1.1rem;
  font-weight: 700;
}

.form-hint {
  display: block;
  margin-top: 0.25rem;
  font-size: 0.75rem;
  color: #6b7280;
}

.stock-status-preview {
  margin-top: 1rem;
  padding: 0.75rem;
  background: white;
  border-radius: 8px;
  text-align: center;
}

.stock-status-indicator {
  display: inline-block;
  padding: 0.5rem 1rem;
  border-radius: 8px;
  font-weight: 600;
  font-size: 0.9rem;
}

.stock-status-indicator.normal {
  background: #d1fae5;
  color: #065f46;
}

.stock-status-indicator.alerte {
  background: #fef3c7;
  color: #92400e;
}

.stock-status-indicator.rupture {
  background: #fee2e2;
  color: #991b1b;
}

/* Panneau d'Alertes */
.alertes-panel {
  background: #fff3cd;
  border: 2px solid #ffc107;
  border-radius: 16px;
  padding: 1.5rem;
  margin-bottom: 1rem;
  position: relative;
  z-index: 1;
}

.alertes-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.alertes-header h3 {
  margin: 0;
  color: #856404;
}

.btn-mark-all {
  padding: 0.5rem 1rem;
  background: #ffc107;
  color: #856404;
  border: none;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
  font-size: 0.875rem;
}

.alertes-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.alerte-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: white;
  border-radius: 8px;
  border-left: 4px solid;
}

.alerte-item.rupture {
  border-left-color: #dc3545;
}

.alerte-item.stock_faible {
  border-left-color: #ffc107;
}

.alerte-item.expiration {
  border-left-color: #17a2b8;
}

.alerte-icon {
  font-size: 1.5rem;
}

.alerte-content {
  flex: 1;
}

.alerte-content strong {
  display: block;
  margin-bottom: 0.25rem;
  color: #1a1a1a;
}

.alerte-content p {
  margin: 0.25rem 0;
  color: #6b7280;
  font-size: 0.9rem;
}

.alerte-content small {
  color: #9ca3af;
  font-size: 0.75rem;
}

.alerte-actions {
  display: flex;
  gap: 0.5rem;
}

.btn-view, .btn-mark-vue {
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 6px;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
}

.btn-view {
  background: #1a5f4a;
  color: white;
}

.btn-mark-vue {
  background: #10b981;
  color: white;
}

/* Modal Historique */
.history-modal {
  max-width: 700px;
  max-height: 80vh;
}

.history-content {
  padding: 1.5rem;
  max-height: 60vh;
  overflow-y: auto;
}

.history-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.history-item {
  display: flex;
  gap: 1rem;
  padding: 1rem;
  background: #f6faf9;
  border-radius: 8px;
  border-left: 4px solid;
}

.history-item.entree {
  border-left-color: #10b981;
}

.history-item.sortie {
  border-left-color: #ef4444;
}

.history-icon {
  font-size: 1.5rem;
}

.history-details {
  flex: 1;
}

.history-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}

.history-header strong {
  color: #1a1a1a;
}

/* Modales de notification et confirmation */
.notification-overlay,
.confirmation-overlay {
  z-index: 2000;
}

.notification-modal,
.confirmation-modal {
  max-width: 450px;
  padding: 0;
}

.notification-header,
.confirmation-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1.5rem;
  border-bottom: 1px solid #e5e7eb;
}

.notification-header h3,
.confirmation-header h3 {
  margin: 0;
  flex: 1;
  font-size: 1.25rem;
  font-weight: 600;
}

.notification-icon,
.confirmation-icon {
  font-size: 2rem;
}

.notification-modal.success .notification-header {
  background: #f0fdf4;
  border-bottom-color: #10b981;
}

.notification-modal.success .notification-icon {
  color: #10b981;
}

.notification-modal.error .notification-header {
  background: #fef2f2;
  border-bottom-color: #ef4444;
}

.notification-modal.error .notification-icon {
  color: #ef4444;
}

.notification-modal.info .notification-header {
  background: #eff6ff;
  border-bottom-color: #3b82f6;
}

.notification-modal.info .notification-icon {
  color: #3b82f6;
}

.notification-body,
.confirmation-body {
  padding: 1.5rem;
}

.notification-body p,
.confirmation-body p {
  margin: 0;
  color: #374151;
  line-height: 1.6;
}

.notification-actions,
.confirmation-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding: 1rem 1.5rem;
  border-top: 1px solid #e5e7eb;
  background: #f9fafb;
}

.confirmation-header {
  background: #fffbeb;
  border-bottom-color: #f59e0b;
}

.confirmation-icon {
  color: #f59e0b;
}

.btn-danger {
  background: #ef4444;
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-danger:hover {
  background: #dc2626;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}

/* Modal tous les mouvements */
.movements-modal {
  max-width: 900px;
  max-height: 85vh;
}

.movements-table-container {
  padding: 1.5rem;
  max-height: 60vh;
  overflow-y: auto;
}

.movements-table {
  width: 100%;
  border-collapse: collapse;
}

.movements-table thead {
  background: #f9fafb;
  border-bottom: 1px solid #e5e7eb;
  position: sticky;
  top: 0;
  z-index: 10;
}

.movements-table th {
  padding: 0.875rem 1rem;
  text-align: left;
  font-size: 0.75rem;
  font-weight: 600;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.movements-table td {
  padding: 1rem;
  font-size: 0.875rem;
  color: #1f2937;
  border-bottom: 1px solid #f3f4f6;
}

.movements-table tbody tr {
  transition: background 0.2s;
}

.movements-table tbody tr:hover {
  background: #f9fafb;
}

.movements-table tbody tr:last-child td {
  border-bottom: none;
}

.movement-type-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: capitalize;
}

.movement-type-badge.entree {
  background: #d1fae5;
  color: #065f46;
}

.movement-type-badge.sortie {
  background: #fee2e2;
  color: #991b1b;
}

.text-muted {
  color: #6b7280;
}

.history-date {
  color: #6b7280;
  font-size: 0.875rem;
}

.history-info {
  display: flex;
  gap: 1rem;
  margin-bottom: 0.5rem;
  font-size: 0.9rem;
  color: #374151;
}

.history-notes {
  margin: 0.5rem 0;
  padding: 0.5rem;
  background: white;
  border-radius: 4px;
  font-size: 0.875rem;
  color: #6b7280;
}

.history-user {
  font-size: 0.75rem;
  color: #9ca3af;
}

/* Formulaires Stock */
.stock-form {
  padding: 1.5rem;
}

.stock-form .form-group {
  margin-bottom: 1rem;
}

.stock-form textarea {
  width: 100%;
  padding: 0.75rem;
  border: 1.5px solid #10b981;
  border-radius: 8px;
  font-size: 1rem;
  font-family: inherit;
  resize: vertical;
}

/* Media queries pour responsive - identique à Dashboard */
@media (max-width: 1100px) {
  .main-content {
    margin-left: 0;
  }
  .dashboard-wrapper {
    border-radius: 0;
  }
  .dashboard-content {
    padding: 1.2rem 0.5rem 0 0.5rem;
    gap: 1.2rem;
  }
}

@media (max-width: 800px) {
  .dashboard-layout {
    flex-direction: column;
  }
  .main-content {
    margin-left: 0;
    width: 100vw;
  }
  .dashboard-content {
    padding: 0.5rem 0.2rem 0 0.2rem;
  }
}
</style>
