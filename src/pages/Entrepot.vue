<template>
  <div class="entrepot-page">
    <!-- Vue Admin -->
    <template v-if="isAdmin">
          <div class="products-header">
            <h2 class="dashboard-title">Entrepôts</h2>
            <button 
              @click.stop="openCreateModal" 
              class="btn-primary"
            >
              <span>+</span> Nouvel Entrepôt
            </button>
          </div>

          <!-- Contenu masqué jusqu'à ce qu'un entrepôt soit ajouté -->
          <div v-if="entrepots.length > 0">
            <!-- Statistiques -->
            <div class="stats-row">
              <StatCard 
                title="Total Entrepôts" 
                :value="stats.totalEntrepots.toString()" 
                :variation="null" 
                icon="🏭" />
              <StatCard 
                title="Valeur Stock Total (Achat)" 
                :value="formatCurrency(stats.valeurStockAchat)" 
                :variation="null" 
                icon="💶" />
              <StatCard 
                title="Valeur Stock Total (Vente)" 
                :value="formatCurrency(stats.valeurStockVente)" 
                :variation="null" 
                icon="💵" />
              <StatCard 
                title="Stock Total (Unités)" 
                :value="stats.stockTotal.toString()" 
                :variation="null" 
                icon="📊" />
            </div>

            <!-- Filtres et recherche -->
            <div class="products-filters">
              <div class="search-box">
                <input
                  v-model="searchQuery"
                  type="text"
                  placeholder="Rechercher un entrepôt..."
                  class="search-input"
                />
              </div>
              <div class="filter-buttons">
                <button 
                  @click="filterActif = null" 
                  :class="['filter-btn', { active: filterActif === null }]"
                >
                  Tous
                </button>
                <button 
                  @click="filterActif = 1" 
                  :class="['filter-btn', { active: filterActif === 1 }]"
                >
                  Actifs
                </button>
                <button 
                  @click="filterActif = 0" 
                  :class="['filter-btn', { active: filterActif === 0 }]"
                >
                  Inactifs
                </button>
                <button @click.stop="openExportModal" class="btn-export" title="Exporter">
                  📥 Exporter
                </button>
              </div>
            </div>

            <!-- Tableau des entrepôts -->
            <div class="products-table-container">
            <table class="products-table">
              <thead>
                <tr>
                  <th>Nom</th>
                  <th>Ville</th>
                  <th>Responsable</th>
                  <th>Produits</th>
                  <th>Stock Total</th>
                  <th>Valeur Stock (Achat)</th>
                  <th>Valeur Stock (Vente)</th>
                  <th>Statut</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="loading">
                  <td colspan="9" class="loading-cell">Chargement...</td>
                </tr>
                <tr v-else-if="filteredEntrepots.length === 0">
                  <td colspan="9" class="empty-cell">Aucun entrepôt trouvé</td>
                </tr>
                <tr v-else v-for="entrepot in filteredEntrepots" :key="entrepot.id_entrepot">
                  <td>
                    <strong>{{ entrepot.nom_entrepot }}</strong>
                  </td>
                  <td>{{ entrepot.ville || '—' }}</td>
                  <td>{{ entrepot.responsable || '—' }}</td>
                  <td>
                    <span class="stock-badge normal">{{ entrepot.nombre_produits || 0 }}</span>
                  </td>
                  <td>
                    <span class="stock-badge normal">{{ entrepot.stock_total || 0 }}</span>
                  </td>
                  <td class="valeur-stock-cell">
                    {{ formatCurrency(entrepot.valeur_stock_achat || 0) }}
                  </td>
                  <td class="valeur-stock-cell">
                    {{ formatCurrency(entrepot.valeur_stock_vente || 0) }}
                  </td>
                  <td>
                    <span :class="['status-badge', entrepot.actif ? 'normal' : 'rupture']">
                      {{ entrepot.actif ? 'Actif' : 'Inactif' }}
                    </span>
                  </td>
                  <td class="actions-cell">
                    <button @click.stop="viewEntrepot(entrepot)" class="btn-view" title="Voir détails">👁️</button>
                    <button @click.stop="openRapportModal(entrepot)" class="btn-rapport" title="Rapport hebdomadaire">📊</button>
                    <button @click.stop="openEditModal(entrepot)" class="btn-edit" title="Modifier">✏️</button>
                    <button @click.stop="confirmDelete(entrepot)" class="btn-delete" title="Supprimer">🗑️</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          </div>

    <!-- Modal Création/Modification Entrepôt -->
    <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal-content entrepot-modal" @click.stop>
        <div class="modal-header">
          <h3>{{ isEditMode ? 'Modifier l\'Entrepôt' : 'Nouvel Entrepôt' }}</h3>
          <button @click="closeModal" class="modal-close">×</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Nom de l'Entrepôt *</label>
            <input
              v-model="formData.nom_entrepot"
              type="text"
              required
              placeholder="Ex: Magasin Principal"
            />
          </div>
          <div class="form-group">
            <label>Adresse</label>
            <textarea
              v-model="formData.adresse"
              rows="2"
              placeholder="Adresse complète"
            ></textarea>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Ville</label>
              <input
                v-model="formData.ville"
                type="text"
                placeholder="Ville"
              />
            </div>
            <div class="form-group">
              <label>Pays</label>
              <input
                v-model="formData.pays"
                type="text"
                placeholder="Pays"
              />
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Téléphone</label>
              <input
                v-model="formData.telephone"
                type="text"
                placeholder="Téléphone"
              />
            </div>
            <div class="form-group">
              <label>Email</label>
              <input
                v-model="formData.email"
                type="email"
                placeholder="Email"
              />
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Responsable</label>
              <input
                v-model="formData.responsable"
                type="text"
                placeholder="Nom du responsable"
              />
            </div>
            <div class="form-group">
              <label>Capacité Maximale</label>
              <input
                v-model.number="formData.capacite_max"
                type="number"
                min="0"
                placeholder="Capacité en unités"
              />
            </div>
          </div>
          <div class="form-group">
            <label>
              <input
                v-model="formData.actif"
                type="checkbox"
              />
              Entrepôt actif
            </label>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="closeModal" class="btn-secondary">Annuler</button>
          <button @click="saveEntrepot" class="btn-primary" :disabled="saving">
            {{ saving ? 'Enregistrement...' : 'Enregistrer' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Détails Entrepôt avec Produits -->
    <div v-if="showDetailsModal" class="modal-overlay" @click.self="closeDetailsModal">
      <div class="modal-content details-modal" @click.stop>
        <div class="modal-header">
          <h3>{{ selectedEntrepot?.nom_entrepot }}</h3>
          <button @click="closeDetailsModal" class="modal-close">×</button>
        </div>
        <div class="modal-body">
          <div v-if="loadingProduits" class="loading-cell">Chargement des produits...</div>
          <div v-else-if="produitsEntrepot.length === 0" class="empty-cell">
            Aucun produit dans cet entrepôt
          </div>
          <table v-else class="products-table">
            <thead>
              <tr>
                <th>Code</th>
                <th>Nom du Produit</th>
                <th>Prix Achat</th>
                <th>Prix Vente</th>
                <th>Stock</th>
                <th>Valeur Stock (Achat)</th>
                <th>Valeur Stock (Vente)</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="produit in produitsEntrepot" :key="produit.id_produit">
                <td><strong>{{ produit.code_produit }}</strong></td>
                <td>{{ produit.nom }}</td>
                <td>{{ formatCurrency(produit.prix_achat) }}</td>
                <td>{{ formatCurrency(produit.prix_vente) }}</td>
                <td>
                  <span class="stock-badge normal">{{ produit.quantite_stock }}</span>
                </td>
                <td>{{ formatCurrency(produit.valeur_stock_achat || 0) }}</td>
                <td>{{ formatCurrency(produit.valeur_stock_vente || 0) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="modal-footer">
          <button @click="openAddProductModal(selectedEntrepot)" class="btn-secondary">➕ Ajouter un Produit</button>
          <div class="export-dropdown">
            <button @click="toggleExportMenu" class="btn-export-main">📥 Exporter</button>
            <div v-if="showExportMenu" class="export-menu">
              <button @click="exportEntrepotExcel(selectedEntrepot)" class="export-option excel">
                📊 Excel
              </button>
              <button @click="exportEntrepotPDF(selectedEntrepot)" class="export-option pdf">
                📄 PDF
              </button>
            </div>
          </div>
          <button @click="closeDetailsModal" class="btn-primary">Fermer</button>
        </div>
      </div>
    </div>

    <!-- Modal Export -->
    <div v-if="showExportModal" class="modal-overlay" @click.self="closeExportModal">
      <div class="modal-content export-modal" @click.stop>
        <div class="modal-header">
          <h3>Exporter les Produits</h3>
          <button @click="closeExportModal" class="modal-close">×</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Entrepôt</label>
            <select v-model="exportEntrepot" class="form-input">
              <option :value="null">Tous les entrepôts</option>
              <option v-for="entrepot in entrepots" :key="entrepot.id_entrepot" :value="entrepot">
                {{ entrepot.nom_entrepot }}
              </option>
            </select>
          </div>
          <div class="form-group">
            <label>Type de Produits</label>
            <select v-model="exportType" class="form-input">
              <option value="all">Tous les produits</option>
              <option value="in_stock">En stock</option>
              <option value="out_of_stock">En rupture</option>
              <option value="alert">En alerte</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <div class="export-dropdown">
            <button @click="toggleExportMenu" class="btn-export-main">📥 Exporter</button>
            <div v-if="showExportMenu" class="export-menu">
              <button @click="exportProductsExcel" class="export-option excel">
                📊 Excel
              </button>
              <button @click="exportProductsPDF" class="export-option pdf">
                📄 PDF
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Notifications -->
    <div v-if="notification.show" class="notification-overlay" @click="closeNotification">
      <div :class="['notification-modal', notification.type]">
        <div class="notification-title">{{ notification.title }}</div>
        <div class="notification-message">{{ notification.message }}</div>
        <button @click="closeNotification" class="notification-close">OK</button>
      </div>
    </div>

    <!-- Modal Ajout de Produit -->
    <div v-if="showProductModal" class="modal-overlay" @click.self="closeProductModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>Nouveau Produit - {{ selectedEntrepotForProduct?.nom_entrepot }}</h3>
          <button @click="closeProductModal" class="modal-close">×</button>
        </div>
        <form @submit.prevent="saveProduct" class="product-form">
          <div class="form-row">
            <div class="form-group">
              <label>Code Produit</label>
              <input v-model="productFormData.code_produit" type="text" />
              <small class="form-hint">Si vide, un code sera généré automatiquement</small>
            </div>
            <div class="form-group">
              <label>Nom de produit (Libellé) *</label>
              <input v-model="productFormData.nom" type="text" required placeholder="Nom du produit" />
            </div>
          </div>
          
          <div class="form-row">
            <div class="form-group">
              <label>Prix d'Achat *</label>
              <input v-model.number="productFormData.prix_achat" type="number" step="0.01" required min="0" placeholder="0.00" />
            </div>
            <div class="form-group">
              <label>Prix de Vente *</label>
              <input v-model.number="productFormData.prix_vente" type="number" step="0.01" required min="0" placeholder="0.00" />
            </div>
          </div>

          <div class="form-section">
            <h4 class="section-title">📦 Gestion du Stock</h4>
            <div class="form-row">
              <div class="form-group">
                <label>Quantité en Stock *</label>
                <input v-model.number="productFormData.quantite_stock" type="number" min="0" placeholder="0" required />
                <small class="form-hint">Quantité actuellement disponible</small>
              </div>
              <div class="form-group">
                <label>Unité</label>
                <select v-model="productFormData.unite">
                  <option value="unité">Unité</option>
                  <option value="paquet">Paquet</option>
                  <option value="boîte">Boîte</option>
                  <option value="carton">Carton</option>
                  <option value="casier">Casier</option>
                  <option value="palette">Palette</option>
                  <option value="lot">Lot</option>
                  <option value="sac">Sac</option>
                  <option value="sachet">Sachet</option>
                  <option value="pièce">Pièce</option>
                  <option value="bouteille">Bouteille</option>
                  <option value="caisse">Caisse</option>
                  <option value="pack">Pack</option>
                </select>
                <small class="form-hint">Sélectionnez l'unité</small>
              </div>
              <div class="form-group">
                <label>Seuil Minimum d'Alerte *</label>
                <input v-model.number="productFormData.seuil_minimum" type="number" min="0" placeholder="0" required />
                <small class="form-hint">Alerte quand le stock atteint ce niveau</small>
              </div>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Date d'Expiration</label>
              <input v-model="productFormData.date_expiration" type="date" />
            </div>
            <div class="form-group">
              <label>Entrepôt</label>
              <input v-model="productFormData.entrepot" type="text" readonly />
              <small class="form-hint">Entrepôt sélectionné</small>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Statut</label>
              <select v-model="productFormData.actif">
                <option :value="1">Actif</option>
                <option :value="0">Inactif</option>
              </select>
            </div>
          </div>

          <div class="modal-actions">
            <button type="button" @click="closeProductModal" class="btn-cancel">Annuler</button>
            <button type="submit" class="btn-save" :disabled="savingProduct">
              {{ savingProduct ? 'Enregistrement...' : 'Enregistrer' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Rapport Hebdomadaire -->
    <div v-if="showRapportModal" class="modal-overlay" @click.self="closeRapportModal">
      <div class="modal-content rapport-modal" @click.stop>
        <div class="modal-header">
          <h3>📊 Rapport Hebdomadaire - {{ rapportEntrepot?.nom_entrepot }}</h3>
          <button @click="closeRapportModal" class="modal-close">×</button>
        </div>
        <div class="rapport-content">
          <div v-if="loadingRapport" class="loading-cell">Chargement du rapport...</div>
          <div v-else>
            <!-- Période du rapport -->
            <div class="rapport-period">
              <strong>Période :</strong> {{ rapportPeriod.debut }} au {{ rapportPeriod.fin }}
            </div>

            <!-- Statistiques du rapport -->
            <div class="rapport-stats">
              <div class="rapport-stat-card">
                <div class="rapport-stat-icon">➕</div>
                <div class="rapport-stat-info">
                  <div class="rapport-stat-label">Entrées</div>
                  <div class="rapport-stat-value">{{ rapportData.totalEntrees || 0 }}</div>
                </div>
              </div>
              <div class="rapport-stat-card">
                <div class="rapport-stat-icon">➖</div>
                <div class="rapport-stat-info">
                  <div class="rapport-stat-label">Sorties</div>
                  <div class="rapport-stat-value">{{ rapportData.totalSorties || 0 }}</div>
                </div>
              </div>
              <div class="rapport-stat-card">
                <div class="rapport-stat-icon">📦</div>
                <div class="rapport-stat-info">
                  <div class="rapport-stat-label">Mouvements</div>
                  <div class="rapport-stat-value">{{ rapportData.totalMouvements || 0 }}</div>
                </div>
              </div>
            </div>

            <!-- Détails des mouvements -->
            <div class="rapport-section">
              <h4 class="rapport-section-title">📋 Détails des Mouvements</h4>
              <div v-if="rapportData.mouvements && rapportData.mouvements.length === 0" class="empty-cell">
                Aucun mouvement enregistré cette semaine
              </div>
              <div v-else class="rapport-mouvements">
                <div 
                  v-for="mouvement in rapportData.mouvements" 
                  :key="mouvement.id"
                  class="rapport-mouvement-item"
                  :class="mouvement.type"
                >
                  <div class="mouvement-header">
                    <span class="mouvement-type-badge" :class="mouvement.type">
                      {{ mouvement.type === 'entree' ? '➕ Entrée' : '➖ Sortie' }}
                    </span>
                    <span class="mouvement-date">{{ formatDate(mouvement.date) }}</span>
                  </div>
                  <div class="mouvement-details">
                    <div class="mouvement-product">
                      <strong>{{ mouvement.produit_nom }}</strong>
                      <span class="mouvement-code">{{ mouvement.code_produit }}</span>
                    </div>
                    <div class="mouvement-info">
                      <span>Quantité: <strong>{{ mouvement.quantite }}</strong></span>
                      <span v-if="mouvement.type_sortie">Type: <strong>{{ getSortieTypeLabel(mouvement.type_sortie) }}</strong></span>
                      <span v-if="mouvement.entrepot_destination">Vers: <strong>{{ mouvement.entrepot_destination }}</strong></span>
                    </div>
                    <div v-if="mouvement.motif || mouvement.notes" class="mouvement-notes">
                      {{ mouvement.motif || mouvement.notes }}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="closeRapportModal" class="btn-secondary">Fermer</button>
        </div>
      </div>
    </div>

    <!-- Modale Nouvelle Sortie (Agent) -->
    <div v-if="showSortieModal" class="modal-overlay" @click.self="closeSortieModal">
      <div class="modal-content medium" @click.stop>
        <div class="modal-header">
          <h3>Nouvelle Sortie vers Point de Vente</h3>
          <button @click="closeSortieModal" class="modal-close">×</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Produit *</label>
            <select v-model="sortieFormData.id_produit" required class="form-input">
              <option value="">Sélectionner un produit</option>
              <option 
                v-for="produit in agentProduits.filter(p => p.quantite_stock > 0)" 
                :key="produit.id_produit"
                :value="produit.id_produit"
              >
                {{ produit.nom }} (Stock: {{ produit.quantite_stock }})
              </option>
            </select>
          </div>
          <div class="form-group">
            <label>Point de Vente Destination *</label>
            <select v-model="sortieFormData.point_vente_destination" required class="form-input">
              <option value="">Sélectionner un point de vente</option>
              <option 
                v-for="pv in agentPointsVente" 
                :key="pv.id_point_vente"
                :value="pv.id_point_vente"
              >
                {{ pv.nom_point_vente }}
              </option>
            </select>
          </div>
          <div class="form-group">
            <label>Quantité *</label>
            <input 
              v-model.number="sortieFormData.quantite" 
              type="number" 
              min="1"
              required
              class="form-input"
              :max="getMaxQuantite()"
            />
            <small v-if="sortieFormData.id_produit" class="form-hint" style="display: block; margin-top: 0.5rem; color: #6b7280; font-size: 0.875rem;">
              Stock disponible: {{ getStockDisponible() }}
            </small>
          </div>
          <div class="form-group">
            <label>Motif</label>
            <textarea 
              v-model="sortieFormData.motif" 
              rows="3"
              placeholder="Raison de la sortie..."
              class="form-input"
            ></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="closeSortieModal" class="btn-secondary">Annuler</button>
          <button @click="createSortie" class="btn-primary" :disabled="loadingSortie || !canCreateSortie">
            {{ loadingSortie ? 'Enregistrement...' : 'Enregistrer la Sortie' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Confirmations -->
    <div v-if="confirmation.show" class="modal-overlay" @click.self="closeConfirmation">
      <div class="modal-content" @click.stop>
        <div class="modal-header" style="display:flex;align-items:center;gap:0.7rem;">
          <span style="font-size:2rem;color:#f59e0b;">⚠️</span>
          <h3 style="margin:0;flex:1;">{{ confirmation.title }}</h3>
          <button @click="closeConfirmation" class="modal-close">×</button>
        </div>
        <div class="modal-body">
          <p>{{ confirmation.message }}</p>
        </div>
        <div class="modal-actions">
          <button @click="closeConfirmation" class="btn-cancel">Annuler</button>
          <button @click="confirmAction" class="btn-save" style="background:#dc2626;">Confirmer</button>
        </div>
      </div>
    </div>
    </template>

    <!-- Vue Agent -->
    <template v-else>
      <div class="agent-entrepot-page">
        <div class="agent-header">
          <h2 class="dashboard-title">{{ currentEntrepot?.nom_entrepot || 'Chargement...' }}</h2>
          <div class="agent-stats-cards">
            <div class="agent-stat-card">
              <div class="stat-icon">📦</div>
              <div class="stat-info">
                <div class="stat-label">Stock Total</div>
                <div class="stat-value">{{ agentStats.stockTotal }}</div>
              </div>
            </div>
            <div class="agent-stat-card">
              <div class="stat-icon">💶</div>
              <div class="stat-info">
                <div class="stat-label">Valeur Stock (Achat)</div>
                <div class="stat-value">{{ formatCurrency(agentStats.valeurStockAchat) }}</div>
              </div>
            </div>
            <div class="agent-stat-card">
              <div class="stat-icon">💵</div>
              <div class="stat-info">
                <div class="stat-label">Valeur Stock (Vente)</div>
                <div class="stat-value">{{ formatCurrency(agentStats.valeurStockVente) }}</div>
              </div>
            </div>
            <div class="agent-stat-card">
              <div class="stat-icon">📊</div>
              <div class="stat-info">
                <div class="stat-label">Produits</div>
                <div class="stat-value">{{ agentStats.nombreProduits }}</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Onglets de navigation -->
        <div class="agent-tabs">
          <button 
            v-for="tab in agentTabs" 
            :key="tab.id"
            @click="activeAgentTab = tab.id"
            :class="['agent-tab', { active: activeAgentTab === tab.id }]"
          >
            <span class="tab-icon">{{ tab.icon }}</span>
            <span class="tab-label">{{ tab.label }}</span>
          </button>
        </div>

        <!-- Contenu des onglets -->
        <div class="agent-tab-content">
          <!-- Onglet Produits -->
          <div v-if="activeAgentTab === 'produits'" class="agent-tab-panel">
            <div class="panel-header">
              <h3>Produits dans l'Entrepôt</h3>
            </div>
            <div class="produits-table-container">
              <div v-if="loadingProduits" class="loading-state">Chargement des produits...</div>
              <div v-else-if="agentProduits.length === 0" class="empty-state">Aucun produit trouvé</div>
              <table v-else class="produits-table">
                <thead>
                  <tr>
                    <th>Code</th>
                    <th>Nom</th>
                    <th>Stock</th>
                    <th>Prix Achat</th>
                    <th>Prix Vente</th>
                    <th>Valeur Stock</th>
                    <th>Statut</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="produit in agentProduits" :key="produit.id_produit">
                    <td>{{ produit.code_produit || '—' }}</td>
                    <td><strong>{{ produit.nom }}</strong></td>
                    <td>{{ produit.quantite_stock || 0 }}</td>
                    <td>{{ formatCurrency(produit.prix_achat || 0) }}</td>
                    <td>{{ formatCurrency(produit.prix_vente || 0) }}</td>
                    <td class="montant-cell">{{ formatCurrency((produit.quantite_stock || 0) * (produit.prix_vente || 0)) }}</td>
                    <td>
                      <span :class="['status-badge', getStatutClass(produit.statut_stock)]">
                        {{ getStatutLabel(produit.statut_stock) }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Onglet Sorties -->
          <div v-if="activeAgentTab === 'sorties'" class="agent-tab-panel">
            <div class="panel-header">
              <h3>Produits Sortis</h3>
              <button @click="openSortieModal" class="btn-primary">
                <span>+</span> Nouvelle Sortie
              </button>
            </div>
            <div class="sorties-table-container">
              <div v-if="loadingSorties" class="loading-state">Chargement des sorties...</div>
              <div v-else-if="agentSorties.length === 0" class="empty-state">Aucune sortie trouvée</div>
              <table v-else class="sorties-table">
                <thead>
                  <tr>
                    <th>Date</th>
                    <th>Produit</th>
                    <th>Quantité</th>
                    <th>Type</th>
                    <th>Montant</th>
                    <th>Destination</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="sortie in agentSorties" :key="sortie.id">
                    <td>{{ formatDate(sortie.date) }}</td>
                    <td>
                      <strong>{{ sortie.produit_nom }}</strong>
                      <div class="text-muted">{{ sortie.code_produit }}</div>
                    </td>
                    <td>{{ sortie.quantite }}</td>
                    <td>{{ getSortieTypeLabel(sortie.type_sortie) }}</td>
                    <td class="montant-cell">{{ formatCurrency(sortie.montant || 0) }}</td>
                    <td>{{ sortie.entrepot_destination || '—' }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- Modale Nouvelle Sortie -->
      <div v-if="showSortieModal" class="modal-overlay" @click.self="closeSortieModal">
        <div class="modal-content medium" @click.stop>
          <div class="modal-header">
            <h3>Nouvelle Sortie vers Point de Vente</h3>
            <button @click="closeSortieModal" class="modal-close">×</button>
          </div>
          <div class="modal-body">
            <div class="form-group">
              <label>Produit *</label>
              <select v-model="sortieFormData.id_produit" required class="form-input">
                <option value="">Sélectionner un produit</option>
                <option 
                  v-for="produit in agentProduits.filter(p => p.quantite_stock > 0)" 
                  :key="produit.id_produit"
                  :value="produit.id_produit"
                >
                  {{ produit.nom }} (Stock: {{ produit.quantite_stock }})
                </option>
              </select>
            </div>
            <div class="form-group">
              <label>Point de Vente Destination *</label>
              <select v-model="sortieFormData.point_vente_destination" required class="form-input">
                <option value="">Sélectionner un point de vente</option>
                <option 
                  v-for="pv in agentPointsVente" 
                  :key="pv.id_point_vente"
                  :value="pv.id_point_vente"
                >
                  {{ pv.nom_point_vente }}
                </option>
              </select>
            </div>
            <div class="form-group">
              <label>Quantité *</label>
              <input 
                v-model.number="sortieFormData.quantite" 
                type="number" 
                min="1"
                required
                class="form-input"
                :max="getMaxQuantite()"
              />
              <small v-if="sortieFormData.id_produit" class="form-hint" style="display: block; margin-top: 0.5rem; color: #6b7280; font-size: 0.875rem;">
                Stock disponible: {{ getStockDisponible() }}
              </small>
            </div>
            <div class="form-group">
              <label>Motif</label>
              <textarea 
                v-model="sortieFormData.motif" 
                rows="3"
                placeholder="Raison de la sortie..."
                class="form-input"
              ></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button @click="closeSortieModal" class="btn-secondary">Annuler</button>
            <button @click="createSortie" class="btn-primary" :disabled="loadingSortie || !canCreateSortie">
              {{ loadingSortie ? 'Enregistrement...' : 'Enregistrer la Sortie' }}
            </button>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import Sidebar from '../components/Sidebar.vue'
import Topbar from '../components/Topbar.vue'
import StatCard from '../components/StatCard.vue'
import { apiService } from '../composables/Api/apiService.js'
import { useCurrency } from '../composables/useCurrency.js'
import { logJournal } from '../composables/useJournal'
import { useAuthStore } from '../stores/auth.js'
import * as XLSX from 'xlsx'
import jsPDF from 'jspdf'
import autoTable from 'jspdf-autotable'

const router = useRouter()
const authStore = useAuthStore()
const { formatPrice: formatCurrency } = useCurrency()

// Vérifier si l'utilisateur est admin
const isAdmin = computed(() => {
  const user = authStore.user
  if (!user) return false
  const role = String(user.role || user.user_role || '').toLowerCase()
  return role === 'admin' || role === 'superadmin'
})

const entrepots = ref([])
const loading = ref(false)
const searchQuery = ref('')
const filterActif = ref(null)
const showModal = ref(false)
const isEditMode = ref(false)
const saving = ref(false)
const showDetailsModal = ref(false)
const selectedEntrepot = ref(null)
const produitsEntrepot = ref([])
const loadingProduits = ref(false)

// Vue Agent
const currentEntrepot = ref(null)
const agentStats = ref({
  stockTotal: 0,
  valeurStockAchat: 0,
  valeurStockVente: 0,
  nombreProduits: 0
})
const agentProduits = ref([])
const agentSorties = ref([])
const loadingSorties = ref(false)
const activeAgentTab = ref('produits')
const agentTabs = [
  { id: 'produits', label: 'Produits', icon: '📦' },
  { id: 'sorties', label: 'Sorties', icon: '📤' }
]

// Modale de sortie
const showSortieModal = ref(false)
const sortieFormData = ref({
  id_produit: null,
  quantite: 1,
  type_sortie: 'transfert',
  motif: '',
  point_vente_destination: null
})
const agentPointsVente = ref([])
const loadingSortie = ref(false)

const showRapportModal = ref(false)
const rapportEntrepot = ref(null)
const rapportData = ref({
  totalEntrees: 0,
  totalSorties: 0,
  totalMouvements: 0,
  mouvements: []
})
const loadingRapport = ref(false)
const rapportPeriod = ref({ debut: '', fin: '' })
const showExportModal = ref(false)
const showExportMenu = ref(false)
const exportEntrepot = ref(null)
const exportType = ref('all')
const showProductModal = ref(false)
const selectedEntrepotForProduct = ref(null)
const savingProduct = ref(false)
const productFormData = ref({
  code_produit: '',
  nom: '',
  prix_achat: 0,
  prix_vente: 0,
  quantite_stock: 0,
  seuil_minimum: 0,
  date_expiration: '',
  unite: 'unité',
  entrepot: '',
  actif: 1
})

const formData = ref({
  nom_entrepot: '',
  adresse: '',
  ville: '',
  pays: '',
  telephone: '',
  email: '',
  responsable: '',
  capacite_max: null,
  actif: 1
})

const notification = ref({ show: false, type: 'success', title: '', message: '' })
const confirmation = ref({ show: false, title: '', message: '', action: null })

const stats = computed(() => {
  const totalEntrepots = entrepots.value.length
  const valeurStockAchat = entrepots.value.reduce((sum, e) => sum + (parseFloat(e.valeur_stock_achat) || 0), 0)
  const valeurStockVente = entrepots.value.reduce((sum, e) => sum + (parseFloat(e.valeur_stock_vente) || 0), 0)
  const stockTotal = entrepots.value.reduce((sum, e) => sum + (parseInt(e.stock_total) || 0), 0)
  
  return {
    totalEntrepots,
    valeurStockAchat,
    valeurStockVente,
    stockTotal
  }
})

const filteredEntrepots = computed(() => {
  let filtered = entrepots.value

  // Filtrer selon les droits d'accès de l'utilisateur connecté
  const user = authStore.user
  if (user && Array.isArray(user.permissions_entrepots) && user.permissions_entrepots.length > 0) {
    filtered = filtered.filter(e => user.permissions_entrepots.includes(e.id_entrepot))
  }

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(e => 
      e.nom_entrepot?.toLowerCase().includes(query) ||
      e.ville?.toLowerCase().includes(query) ||
      e.responsable?.toLowerCase().includes(query)
    )
  }

  if (filterActif.value !== null) {
    filtered = filtered.filter(e => e.actif == filterActif.value)
  }

  return filtered
})

// formatCurrency est maintenant fourni par useCurrency() via formatPrice
// Les valeurs sont supposées être en XOF (F CFA) par défaut dans la base de données

const loadEntrepots = async () => {
  loading.value = true
  try {
    const response = await apiService.get('/api_entrepot.php?action=all')
    if (response && response.success) {
      entrepots.value = response.data || []
    } else {
      console.error('Erreur API:', response)
      showNotification('error', 'Erreur', response?.message || 'Erreur lors du chargement des entrepôts')
    }
  } catch (error) {
    console.error('Erreur lors du chargement des entrepôts:', error)
    showNotification('error', 'Erreur', error?.message || 'Erreur lors du chargement des entrepôts')
  } finally {
    loading.value = false
  }
}

const openCreateModal = () => {
  isEditMode.value = false
  formData.value = {
    nom_entrepot: '',
    adresse: '',
    ville: '',
    pays: '',
    telephone: '',
    email: '',
    responsable: '',
    capacite_max: null,
    actif: 1
  }
  showModal.value = true
}

const openEditModal = (entrepot) => {
  isEditMode.value = true
  formData.value = {
    id_entrepot: entrepot.id_entrepot,
    nom_entrepot: entrepot.nom_entrepot,
    adresse: entrepot.adresse || '',
    ville: entrepot.ville || '',
    pays: entrepot.pays || '',
    telephone: entrepot.telephone || '',
    email: entrepot.email || '',
    responsable: entrepot.responsable || '',
    capacite_max: entrepot.capacite_max || null,
    actif: entrepot.actif ? 1 : 0
  }
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  formData.value = {
    nom_entrepot: '',
    adresse: '',
    ville: '',
    pays: '',
    telephone: '',
    email: '',
    responsable: '',
    capacite_max: null,
    actif: 1
  }
}

const saveEntrepot = async () => {
  if (!formData.value.nom_entrepot?.trim()) {
    showNotification('error', 'Erreur de validation', 'Le nom de l\'entrepôt est requis')
    return
  }

  saving.value = true
  try {
    let response
    if (isEditMode.value) {
      response = await apiService.put('/api_entrepot.php', formData.value)
    } else {
      response = await apiService.post('/api_entrepot.php', formData.value)
    }

    if (response.success) {
      showNotification('success', 'Succès', isEditMode.value ? 'Entrepôt mis à jour' : 'Entrepôt créé')
      closeModal()
      await loadEntrepots()
      logJournal({ user: getJournalUser(), action: isEditMode.value ? 'Modifier l\'entrepôt' : 'Ajouter un entrepôt', details: response.data })
    } else {
      showNotification('error', 'Erreur', response.message || 'Erreur lors de l\'enregistrement')
    }
  } catch (error) {
    showNotification('error', 'Erreur', 'Erreur lors de l\'enregistrement')
  } finally {
    saving.value = false
  }
}

const viewEntrepot = async (entrepot) => {
  selectedEntrepot.value = entrepot
  loadingProduits.value = true
  showDetailsModal.value = true
  
  try {
    const response = await apiService.get(`/api_entrepot.php?action=produits&id_entrepot=${entrepot.id_entrepot}`)
    if (response.success) {
      produitsEntrepot.value = response.data || []
    } else {
      showNotification('error', 'Erreur', response.message || 'Erreur lors du chargement des produits')
    }
  } catch (error) {
    showNotification('error', 'Erreur', 'Erreur lors du chargement des produits')
  } finally {
    loadingProduits.value = false
  }
}

const closeDetailsModal = () => {
  showDetailsModal.value = false
  selectedEntrepot.value = null
  produitsEntrepot.value = []
  showExportMenu.value = false
}

// Fermer le menu d'export si on clique en dehors
const handleClickOutside = (event) => {
  if (showExportMenu.value && !event.target.closest('.export-dropdown')) {
    showExportMenu.value = false
  }
}

// Fonctions pour la vue Agent
const loadAgentEntrepot = async () => {
  const user = authStore.user
  if (!user || !user.id_utilisateur) {
    console.error('Utilisateur non connecté')
    return
  }
  
  try {
    // Récupérer les entrepôts de l'utilisateur depuis l'API
    const entrepotsResponse = await apiService.get('/api_entrepot.php?action=all')
    console.log('Réponse entrepôts:', entrepotsResponse)
    
    if (entrepotsResponse && entrepotsResponse.success && entrepotsResponse.data && entrepotsResponse.data.length > 0) {
      // Prendre le premier entrepôt disponible pour cet utilisateur
      const entrepot = entrepotsResponse.data[0]
      currentEntrepot.value = entrepot
      
      // Charger les statistiques
      agentStats.value = {
        stockTotal: parseInt(entrepot.stock_total || 0),
        valeurStockAchat: parseFloat(entrepot.valeur_stock_achat || 0),
        valeurStockVente: parseFloat(entrepot.valeur_stock_vente || 0),
        nombreProduits: parseInt(entrepot.nombre_produits || 0)
      }
      
      // Charger les produits
      await loadAgentProduits()
      // Charger les sorties
      await loadAgentSorties()
    } else {
      console.error('Aucun entrepôt trouvé pour cet utilisateur')
    }
  } catch (error) {
    console.error('Erreur lors du chargement de l\'entrepôt:', error)
  }
}

const loadAgentProduits = async () => {
  if (!currentEntrepot.value) return
  
  loadingProduits.value = true
  try {
    const response = await apiService.get(`/api_entrepot.php?action=produits&id_entrepot=${currentEntrepot.value.id_entrepot}`)
    if (response && response.success) {
      agentProduits.value = response.data || []
    } else {
      agentProduits.value = []
    }
  } catch (error) {
    console.error('Erreur lors du chargement des produits:', error)
    agentProduits.value = []
  } finally {
    loadingProduits.value = false
  }
}

const loadAgentSorties = async () => {
  if (!currentEntrepot.value) return
  
  loadingSorties.value = true
  try {
    const response = await apiService.get(`/api_entrepot.php?action=rapport&id_entrepot=${currentEntrepot.value.id_entrepot}`)
    if (response && response.success) {
      // Filtrer uniquement les sorties
      const mouvements = response.data?.mouvements || []
      agentSorties.value = mouvements
        .filter(m => m.type === 'sortie')
        .map(sortie => {
          // L'API ne retourne pas prix_unitaire dans les sorties, donc montant = 0
          // On pourrait récupérer le prix depuis le produit si nécessaire
          return {
            ...sortie,
            montant: 0 // Le montant n'est pas disponible dans les données de sortie
          }
        })
    } else {
      agentSorties.value = []
    }
  } catch (error) {
    console.error('Erreur lors du chargement des sorties:', error)
    agentSorties.value = []
  } finally {
    loadingSorties.value = false
  }
}

const getStatutClass = (statut) => {
  if (statut === 'rupture') return 'rupture'
  if (statut === 'alerte') return 'alerte'
  return 'normal'
}

const getStatutLabel = (statut) => {
  if (statut === 'rupture') return 'Rupture'
  if (statut === 'alerte') return 'Alerte'
  return 'Normal'
}

const formatDate = (dateString) => {
  if (!dateString) return '—'
  const date = new Date(dateString)
  return date.toLocaleString('fr-FR', { 
    day: '2-digit', 
    month: '2-digit', 
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Fonctions pour la modale de sortie
const openSortieModal = async () => {
  showSortieModal.value = true
  sortieFormData.value = {
    id_produit: null,
    quantite: 1,
    type_sortie: 'transfert',
    motif: '',
    point_vente_destination: null
  }
  // Charger les points de vente de l'agent
  await loadAgentPointsVente()
}

const closeSortieModal = () => {
  showSortieModal.value = false
  sortieFormData.value = {
    id_produit: null,
    quantite: 1,
    type_sortie: 'transfert',
    motif: '',
    point_vente_destination: null
  }
}

const loadAgentPointsVente = async () => {
  try {
    const response = await apiService.get('/api_point_vente.php?action=all')
    if (response && response.success) {
      agentPointsVente.value = response.data || []
    }
  } catch (error) {
    console.error('Erreur lors du chargement des points de vente:', error)
  }
}

const getMaxQuantite = () => {
  if (!sortieFormData.value.id_produit) return 0
  const produit = agentProduits.value.find(p => p.id_produit === sortieFormData.value.id_produit)
  return produit ? produit.quantite_stock : 0
}

const getStockDisponible = () => {
  if (!sortieFormData.value.id_produit) return 0
  const produit = agentProduits.value.find(p => p.id_produit === sortieFormData.value.id_produit)
  return produit ? produit.quantite_stock : 0
}

const canCreateSortie = computed(() => {
  return sortieFormData.value.id_produit && 
         sortieFormData.value.point_vente_destination && 
         sortieFormData.value.quantite > 0 &&
         sortieFormData.value.quantite <= getMaxQuantite()
})

const createSortie = async () => {
  if (!canCreateSortie.value) return
  
  loadingSortie.value = true
  try {
    const produit = agentProduits.value.find(p => p.id_produit === sortieFormData.value.id_produit)
    const pointVente = agentPointsVente.value.find(pv => pv.id_point_vente === sortieFormData.value.point_vente_destination)
    
    const sortieData = {
      id_produit: sortieFormData.value.id_produit,
      quantite: sortieFormData.value.quantite,
      type_sortie: 'transfert',
      motif: sortieFormData.value.motif || `Transfert vers ${pointVente?.nom_point_vente || 'Point de vente'}`,
      entrepot_destination: pointVente?.nom_point_vente || null,
      prix_unitaire: produit?.prix_vente || null
    }
    
    const response = await apiService.post('/api_stock.php?type=sortie', sortieData)
    
    if (response && response.success) {
      showNotification('success', 'Succès', 'Sortie créée avec succès')
      closeSortieModal()
      // Recharger les données
      await loadAgentProduits()
      await loadAgentSorties()
      // Recharger les statistiques
      await loadAgentEntrepot()
    } else {
      showNotification('error', 'Erreur', response?.message || 'Erreur lors de la création de la sortie')
    }
  } catch (error) {
    console.error('Erreur lors de la création de la sortie:', error)
    showNotification('error', 'Erreur', 'Erreur lors de la création de la sortie')
  } finally {
    loadingSortie.value = false
  }
}

// Watcher pour recharger les données quand on change d'onglet (agent)
watch(activeAgentTab, (newTab) => {
  if (!isAdmin.value) {
    if (newTab === 'produits') loadAgentProduits()
    else if (newTab === 'sorties') loadAgentSorties()
  }
})

onMounted(async () => {
  if (isAdmin.value) {
    loadEntrepots()
  } else {
    // Pour les agents, charger leur entrepôt et les données
    await loadAgentEntrepot()
  }
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})

const toggleExportMenu = () => {
  showExportMenu.value = !showExportMenu.value
}

const confirmDelete = (entrepot) => {
  confirmation.value = {
    show: true,
    title: 'Supprimer l\'entrepôt',
    message: `Êtes-vous sûr de vouloir supprimer l'entrepôt "${entrepot.nom_entrepot}" ?`,
    action: async () => {
      try {
        const response = await apiService.delete(`/api_entrepot.php?id_entrepot=${entrepot.id_entrepot}`)
        if (response.success) {
          showNotification('success', 'Succès', 'Entrepôt supprimé')
          await loadEntrepots()
          logJournal({ user: getJournalUser(), action: 'Supprimer l\'entrepôt', details: entrepot })
        } else {
          showNotification('error', 'Erreur', response.message || 'Erreur lors de la suppression')
        }
      } catch (error) {
        showNotification('error', 'Erreur', 'Erreur lors de la suppression')
      }
    }
  }
}

const showNotification = (type, title, message) => {
  notification.value = { show: true, type, title, message }
}

const closeNotification = () => {
  notification.value = { show: false, type: 'success', title: '', message: '' }
}

const closeConfirmation = () => {
  confirmation.value = { show: false, title: '', message: '', action: null }
}

const confirmAction = () => {
  if (confirmation.value.action) {
    confirmation.value.action()
  }
  closeConfirmation()
}

// Calculer la période de la semaine (7 derniers jours)
const getWeekPeriod = () => {
  const today = new Date()
  const lastWeek = new Date(today)
  lastWeek.setDate(today.getDate() - 7)
  
  return {
    debut: lastWeek.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' }),
    fin: today.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })
  }
}

const openRapportModal = async (entrepot) => {
  rapportEntrepot.value = entrepot
  showRapportModal.value = true
  loadingRapport.value = true
  rapportPeriod.value = getWeekPeriod()
  
  try {
    const response = await apiService.get(`/api_entrepot.php?action=rapport&id_entrepot=${entrepot.id_entrepot}`)
    if (response.success) {
      rapportData.value = response.data || {
        totalEntrees: 0,
        totalSorties: 0,
        totalMouvements: 0,
        mouvements: []
      }
    } else {
      showNotification('error', 'Erreur', response.message || 'Erreur lors du chargement du rapport')
    }
  } catch (error) {
    console.error('Erreur lors du chargement du rapport:', error)
    showNotification('error', 'Erreur', 'Erreur lors du chargement du rapport')
  } finally {
    loadingRapport.value = false
  }
}

const closeRapportModal = () => {
  showRapportModal.value = false
  rapportEntrepot.value = null
  rapportData.value = {
    totalEntrees: 0,
    totalSorties: 0,
    totalMouvements: 0,
    mouvements: []
  }
}

// formatDate est déjà défini plus haut pour la vue agent

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

function getJournalUser() {
  const userStr = localStorage.getItem('prostock_user');
  if (userStr) {
    try {
      const user = JSON.parse(userStr);
      return user.nom || user.email || 'inconnu';
    } catch {
      return 'inconnu';
    }
  }
  return 'inconnu';
}

const openExportModal = () => {
  showExportModal.value = true
  exportEntrepot.value = null
  exportType.value = 'all'
  showExportMenu.value = false
}

const closeExportModal = () => {
  showExportModal.value = false
  showExportMenu.value = false
}

const getProductsToExport = async () => {
  let products = []
  
  if (exportEntrepot.value) {
    // Produits d'un entrepôt spécifique
    const response = await apiService.get(`/api_entrepot.php?action=produits&id_entrepot=${exportEntrepot.value.id_entrepot}`)
    if (response.success) {
      products = response.data || []
    }
  } else {
    // Tous les produits
    const response = await apiService.get('/api_produit.php?action=all')
    if (response.success) {
      products = response.data || []
    }
  }
  
  // Filtrer par type
  if (exportType.value === 'in_stock') {
    products = products.filter(p => p.quantite_stock > 0 && p.statut_stock === 'normal')
  } else if (exportType.value === 'out_of_stock') {
    products = products.filter(p => p.statut_stock === 'rupture')
  } else if (exportType.value === 'alert') {
    products = products.filter(p => p.statut_stock === 'alerte' || p.statut_stock === 'rupture')
  }
  
  return products
}

const exportProductsExcel = async () => {
  showExportMenu.value = false
  try {
    const products = await getProductsToExport()
    const data = products.map(p => ({
      'Code Produit': p.code_produit,
      'Nom': p.nom,
      'Entrepôt': p.entrepot || 'Magasin',
      'Prix Achat': p.prix_achat,
      'Prix Vente': p.prix_vente,
      'Stock': p.quantite_stock,
      'Seuil Minimum': p.seuil_minimum,
      'Statut': p.statut_stock === 'rupture' ? 'Rupture' : p.statut_stock === 'alerte' ? 'Alerte' : 'Normal',
      'Valeur Stock (Achat)': (p.quantite_stock || 0) * (p.prix_achat || 0),
      'Valeur Stock (Vente)': (p.quantite_stock || 0) * (p.prix_vente || 0)
    }))
    
    const ws = XLSX.utils.json_to_sheet(data)
    const wb = XLSX.utils.book_new()
    XLSX.utils.book_append_sheet(wb, ws, 'Produits')
    const fileName = exportEntrepot.value 
      ? `produits_${exportEntrepot.value.nom_entrepot}_${exportType.value}.xlsx`
      : `produits_${exportType.value}.xlsx`
    XLSX.writeFile(wb, fileName)
    showNotification('success', 'Succès', 'Export Excel réussi')
    closeExportModal()
  } catch (error) {
    console.error('Erreur export Excel:', error)
    showNotification('error', 'Erreur', 'Erreur lors de l\'export Excel')
  }
}

const exportProductsPDF = async () => {
  showExportMenu.value = false
  try {
    const products = await getProductsToExport()
    const doc = new jsPDF()
    const title = exportEntrepot.value 
      ? `Produits - ${exportEntrepot.value.nom_entrepot}`
      : 'Produits'
    doc.text(title, 14, 16)
    
    const rows = products.map(p => [
      p.code_produit,
      p.nom,
      p.entrepot || 'Magasin',
      formatCurrency(p.prix_achat),
      formatCurrency(p.prix_vente),
      p.quantite_stock.toString(),
      p.statut_stock === 'rupture' ? 'Rupture' : p.statut_stock === 'alerte' ? 'Alerte' : 'Normal'
    ])
    
    autoTable(doc, {
      head: [['Code', 'Nom', 'Entrepôt', 'Prix Achat', 'Prix Vente', 'Stock', 'Statut']],
      body: rows,
      startY: 22,
      theme: 'grid',
      styles: { fontSize: 9 }
    })
    
    const fileName = exportEntrepot.value 
      ? `produits_${exportEntrepot.value.nom_entrepot}_${exportType.value}.pdf`
      : `produits_${exportType.value}.pdf`
    doc.save(fileName)
    showNotification('success', 'Succès', 'Export PDF réussi')
    closeExportModal()
  } catch (error) {
    console.error('Erreur export PDF:', error)
    showNotification('error', 'Erreur', 'Erreur lors de l\'export PDF')
  }
}

const exportEntrepotExcel = async (entrepot) => {
  showExportMenu.value = false
  try {
    const response = await apiService.get(`/api_entrepot.php?action=produits&id_entrepot=${entrepot.id_entrepot}`)
    if (response.success) {
      const products = response.data || []
      const data = products.map(p => ({
        'Code Produit': p.code_produit,
        'Nom': p.nom,
        'Prix Achat': p.prix_achat,
        'Prix Vente': p.prix_vente,
        'Stock': p.quantite_stock,
        'Valeur Stock (Achat)': p.valeur_stock_achat || 0,
        'Valeur Stock (Vente)': p.valeur_stock_vente || 0
      }))
      
      const ws = XLSX.utils.json_to_sheet(data)
      const wb = XLSX.utils.book_new()
      XLSX.utils.book_append_sheet(wb, ws, 'Produits')
      XLSX.writeFile(wb, `produits_${entrepot.nom_entrepot}.xlsx`)
      showNotification('success', 'Succès', 'Export Excel réussi')
    }
  } catch (error) {
    console.error('Erreur export Excel:', error)
    showNotification('error', 'Erreur', 'Erreur lors de l\'export Excel')
  }
}

const exportEntrepotPDF = async (entrepot) => {
  showExportMenu.value = false
  try {
    const response = await apiService.get(`/api_entrepot.php?action=produits&id_entrepot=${entrepot.id_entrepot}`)
    if (response.success) {
      const products = response.data || []
      const doc = new jsPDF()

      // Header chic : logo rond + nom entreprise + fond
      doc.setFillColor(26, 95, 74)
      doc.roundedRect(0, 0, 210, 30, 0, 0, 'F')
      doc.setFillColor(255, 255, 255)
      doc.circle(22, 15, 8, 'F')
      doc.setTextColor(26, 95, 74)
      doc.setFontSize(13)
      doc.setFont('helvetica', 'bold')
      doc.text('PS', 18, 18)
      doc.setTextColor(255, 255, 255)
      doc.setFontSize(15)
      const entrepriseNom = authStore.user?.nom_entreprise || 'Nom de l\'entreprise'
      doc.text(entrepriseNom, 210 - 14, 18, { align: 'right' })

      // Titre centré
      doc.setFontSize(16)
      doc.setTextColor(30, 30, 30)
      doc.setFont('helvetica', 'bold')
      doc.text(`Produits - ${entrepot.nom_entrepot}`, 105, 32, { align: 'center' })

      // Bloc statistiques
      const total = products.length
      const enStock = products.filter(p => p.quantite_stock > 0).length
      const enRupture = products.filter(p => p.quantite_stock === 0).length
      const enAlerte = products.filter(p => p.quantite_stock > 0 && p.quantite_stock <= p.seuil_minimum).length
      doc.setFontSize(11)
      doc.setTextColor(60, 60, 60)
      doc.setFont('helvetica', 'normal')
      doc.text(`Total : ${total}   |   En stock : ${enStock}   |   En rupture : ${enRupture}   |   En alerte : ${enAlerte}`, 105, 42, { align: 'center' })

      // Tableau
      const rows = products.map(p => [
        p.code_produit,
        p.nom,
        formatCurrency(p.prix_achat),
        formatCurrency(p.prix_vente),
        p.quantite_stock.toString(),
        formatCurrency(p.valeur_stock_achat || 0),
        formatCurrency(p.valeur_stock_vente || 0)
      ])
      
      autoTable(doc, {
        head: [['Code', 'Nom', 'Prix Achat', 'Prix Vente', 'Stock', 'Valeur (Achat)', 'Valeur (Vente)']],
        body: rows,
        startY: 48,
        theme: 'grid',
        styles: { fontSize: 10 },
        headStyles: { fillColor: [26, 95, 74] },
        margin: { left: 14, right: 14 }
      })

      // Pied de page
      const pageCount = doc.internal.getNumberOfPages()
      for (let i = 1; i <= pageCount; i++) {
        doc.setPage(i)
        doc.setFontSize(9)
        doc.setTextColor(120, 120, 120)
        doc.text('ProStock - Export PDF', 14, 290)
        doc.text(`Page ${i} / ${pageCount}`, 200, 290, { align: 'right' })
        doc.text(new Date().toLocaleDateString(), 105, 290, { align: 'center' })
      }
      
      doc.save(`produits_${entrepot.nom_entrepot}.pdf`)
      showNotification('success', 'Succès', 'Export PDF réussi')
    }
  } catch (error) {
    console.error('Erreur export PDF:', error)
    showNotification('error', 'Erreur', 'Erreur lors de l\'export PDF')
  }
}

const openAddProductModal = (entrepot) => {
  selectedEntrepotForProduct.value = entrepot
  productFormData.value = {
    code_produit: '',
    nom: '',
    prix_achat: 0,
    prix_vente: 0,
    quantite_stock: 0,
    seuil_minimum: 0,
    date_expiration: '',
    unite: 'unité',
    entrepot: entrepot.nom_entrepot,
    actif: 1
  }
  showProductModal.value = true
}

const closeProductModal = () => {
  showProductModal.value = false
  selectedEntrepotForProduct.value = null
  productFormData.value = {
    code_produit: '',
    nom: '',
    prix_achat: 0,
    prix_vente: 0,
    quantite_stock: 0,
    seuil_minimum: 0,
    date_expiration: '',
    unite: 'unité',
    entrepot: '',
    actif: 1
  }
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

const saveProduct = async () => {
  // Validation : prix de vente doit être >= prix d'achat
  if (productFormData.value.prix_vente < productFormData.value.prix_achat) {
    showNotification('error', 'Erreur de validation', 'Le prix de vente doit être supérieur ou égal au prix d\'achat')
    return
  }

  // S'assurer que tous les champs requis sont présents
  if (!productFormData.value.nom || productFormData.value.nom.trim() === '') {
    showNotification('error', 'Erreur de validation', 'Le nom du produit est obligatoire')
    return
  }

  // Générer le code produit si vide
  let codeProduit = productFormData.value.code_produit
  if (!codeProduit || codeProduit.trim() === '') {
    codeProduit = generateProductCode(productFormData.value.nom)
  }

  // Préparer les données pour l'API (enlever les champs non utilisés par l'API)
  const dataToSave = {
    code_produit: codeProduit,
    nom: productFormData.value.nom.trim(),
    id_categorie: null,
    prix_achat: parseFloat(productFormData.value.prix_achat) || 0,
    prix_vente: parseFloat(productFormData.value.prix_vente) || 0,
    quantite_stock: parseInt(productFormData.value.quantite_stock) || 0,
    seuil_minimum: parseInt(productFormData.value.seuil_minimum) || 0,
    date_expiration: productFormData.value.date_expiration || null,
    entrepot: productFormData.value.entrepot || 'Magasin',
    actif: productFormData.value.actif ? 1 : 0
  }

  savingProduct.value = true
  try {
    const response = await apiService.post('/api_produit.php', dataToSave)
    if (response.success) {
      showNotification('success', 'Succès', 'Produit créé avec succès')
      closeProductModal()
      // Recharger les produits de l'entrepôt si le modal de détails est ouvert
      if (selectedEntrepot.value && showDetailsModal.value) {
        await viewEntrepot(selectedEntrepot.value)
      }
      await logJournal({
        user: getJournalUser(),
        action: 'Ajout produit',
        details: `Produit: ${productFormData.value.nom}, Entrepôt: ${productFormData.value.entrepot}`
      })
    } else {
      showNotification('error', 'Erreur', response.message || 'Erreur lors de la création du produit')
    }
  } catch (error) {
    console.error('Erreur lors de la sauvegarde:', error)
    showNotification('error', 'Erreur', 'Erreur lors de la création du produit')
  } finally {
    savingProduct.value = false
  }
}

onMounted(() => {
  loadEntrepots()
})
</script>

<style scoped>
/* Styles similaires à Products.vue */
.dashboard-layout {
  display: flex;
  width: 100vw;
  background: #f6f7fa;
}

.main-content {
  flex: 1;
  margin-left: 280px;
  width: calc(100vw - 280px);
  display: flex;
  flex-direction: column;
}

.dashboard-wrapper {
  background: #fff;
  border-radius: 0 32px 32px 0;
  box-shadow: 0 8px 32px 0 rgba(26, 95, 74, 0.10);
  width: 100%;
  display: flex;
  flex-direction: column;
  transition: box-shadow 0.2s;
}

.dashboard-content {
  padding: 1rem 2.5rem 2.5rem 2.5rem;
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  box-sizing: border-box;
  background: #f6f7fa;
  position: relative;
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

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

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

.btn-export {
  padding: 0.5rem 1rem;
  border: 1.5px solid #3b82f6;
  background: #3b82f6;
  color: white;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s;
  font-weight: 600;
}

.btn-export:hover {
  background: #2563eb;
  border-color: #2563eb;
}

.btn-export-main {
  background: #3b82f6;
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  position: relative;
  transition: all 0.2s;
}

.btn-export-main:hover {
  background: #2563eb;
}

.export-dropdown {
  position: relative;
}

.export-menu {
  position: absolute;
  bottom: 100%;
  right: 0;
  margin-bottom: 0.5rem;
  background: white;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  border: 1px solid #e5e7eb;
  overflow: hidden;
  z-index: 100;
  min-width: 150px;
}

.export-option {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  width: 100%;
  padding: 0.875rem 1.25rem;
  border: none;
  background: white;
  cursor: pointer;
  font-weight: 600;
  font-size: 0.95rem;
  transition: all 0.2s;
  text-align: left;
}

.export-option.excel {
  color: #10b981;
}

.export-option.excel:hover {
  background: #d1fae5;
  color: #065f46;
}

.export-option.pdf {
  color: #ef4444;
  border-top: 1px solid #e5e7eb;
}

.export-option.pdf:hover {
  background: #fee2e2;
  color: #991b1b;
}

.products-table-container {
  background: #ffffff;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
  overflow: hidden;
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

.loading-cell, .empty-cell {
  text-align: center;
  padding: 2rem;
  color: #6b7280;
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

.status-badge.rupture {
  background: #fee2e2;
  color: #991b1b;
}

.valeur-stock-cell {
  font-weight: 600;
}

.actions-cell {
  display: flex;
  gap: 0.5rem;
  justify-content: center;
}

.actions-cell button {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
  transition: all 0.2s;
}

.btn-view {
  background: #dbeafe;
  color: #1e40af;
}

.btn-rapport {
  background: #e0e7ff;
  color: #3730a3;
}

.btn-edit {
  background: #fef3c7;
  color: #92400e;
}

.btn-delete {
  background: #fee2e2;
  color: #991b1b;
}

.actions-cell button:hover {
  transform: scale(1.1);
}

/* Modals */
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
  max-width: 600px;
  width: 90%;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
}

.details-modal {
  max-width: 900px;
}

.rapport-modal {
  max-width: 900px;
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
  font-size: 1.25rem;
  color: #1a5f4a;
}

.modal-close {
  background: none;
  border: none;
  font-size: 2rem;
  cursor: pointer;
  color: #6b7280;
  line-height: 1;
}

.modal-body {
  padding: 1.5rem;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding: 1.5rem;
  border-top: 1px solid #e5e7eb;
}

.form-group {
  margin-bottom: 1rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 600;
  color: #374151;
}

.form-group input,
.form-group textarea {
  width: 100%;
  padding: 0.75rem;
  border: 1.5px solid #d1d5db;
  border-radius: 8px;
  font-size: 1rem;
  box-sizing: border-box;
}

.form-group input:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #1a5f4a;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.btn-secondary {
  background: #f3f4f6;
  color: #374151;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
}

/* Notifications */
.notification-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.3);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2000;
}

.notification-modal {
  background: white;
  border-radius: 12px;
  padding: 2rem;
  max-width: 400px;
  width: 90%;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
}

.notification-modal.success {
  border-left: 4px solid #10b981;
}

.notification-modal.error {
  border-left: 4px solid #ef4444;
}

.notification-title {
  font-weight: 700;
  font-size: 1.1rem;
  margin-bottom: 0.5rem;
}

.notification-message {
  margin-bottom: 1rem;
  color: #6b7280;
}

.notification-close {
  background: #1a5f4a;
  color: white;
  border: none;
  padding: 0.5rem 1.5rem;
  border-radius: 6px;
  cursor: pointer;
  width: 100%;
}

/* Confirmations */
.confirmation-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2000;
}

.confirmation-modal {
  background: white;
  border-radius: 12px;
  padding: 2rem;
  max-width: 400px;
  width: 90%;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
}

/* Product Form Styles */
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
  gap: 0.5rem;
}

.form-group label {
  font-weight: 600;
  color: #374151;
  font-size: 0.9rem;
}

.form-group input,
.form-group select {
  padding: 0.75rem;
  border: 1.5px solid #d1d5db;
  border-radius: 8px;
  font-size: 1rem;
  transition: border-color 0.2s;
}

.form-group input:focus,
.form-group select:focus {
  outline: none;
  border-color: #1a5f4a;
}

.form-group input[readonly] {
  background: #f3f4f6;
  cursor: not-allowed;
}

.form-hint {
  font-size: 0.75rem;
  color: #6b7280;
}

.form-section {
  margin: 1.5rem 0;
  padding: 1rem;
  background: #f9fafb;
  border-radius: 8px;
}

.section-title {
  font-size: 1rem;
  font-weight: 700;
  color: #1a5f4a;
  margin-bottom: 1rem;
}

.modal-actions {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid #e5e7eb;
}

.btn-cancel {
  padding: 0.75rem 1.5rem;
  border: 1.5px solid #6b7280;
  background: white;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
  color: #374151;
}

.btn-cancel:hover {
  background: #f3f4f6;
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

.btn-save:hover {
  background: #134e3a;
}

.btn-save:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* ...existing code... */

.confirmation-actions {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
}

/* Styles pour le modal de rapport */
.rapport-content {
  padding: 1.5rem;
}

.rapport-period {
  padding: 1rem;
  background: #f3f4f6;
  border-radius: 8px;
  margin-bottom: 1.5rem;
  font-size: 0.95rem;
  color: #374151;
}

.rapport-stats {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
  margin-bottom: 2rem;
}

.rapport-stat-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: #f9fafb;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
}

/* Styles pour la vue Agent */
.agent-entrepot-page {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  width: 100%;
}

.agent-header {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.agent-stats-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.agent-stat-card {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  display: flex;
  align-items: center;
  gap: 1rem;
}

.stat-icon {
  font-size: 2rem;
}

.stat-info {
  flex: 1;
}

.stat-label {
  font-size: 0.875rem;
  color: #64748b;
  margin-bottom: 0.25rem;
}

.stat-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1a5f4a;
}

.agent-tabs {
  display: flex;
  gap: 0.5rem;
  border-bottom: 2px solid #e5e7eb;
  margin-bottom: 1.5rem;
}

.agent-tab {
  background: transparent;
  border: none;
  padding: 1rem 1.5rem;
  font-size: 1rem;
  font-weight: 600;
  color: #64748b;
  cursor: pointer;
  border-bottom: 3px solid transparent;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.agent-tab:hover {
  color: #1a5f4a;
  background: #f0fdf4;
}

.agent-tab.active {
  color: #1a5f4a;
  border-bottom-color: #1a5f4a;
  background: #f0fdf4;
}

.tab-icon {
  font-size: 1.25rem;
}

.agent-tab-content {
  width: 100%;
}

.agent-tab-panel {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.panel-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.panel-header h3 {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1a5f4a;
  margin: 0;
}

.produits-table-container,
.sorties-table-container {
  background: white;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  overflow: hidden;
}

.produits-table,
.sorties-table {
  width: 100%;
  border-collapse: collapse;
}

.produits-table thead,
.sorties-table thead {
  background: #f9fafb;
  border-bottom: 2px solid #e5e7eb;
}

.produits-table th,
.sorties-table th {
  padding: 1rem;
  text-align: left;
  font-weight: 600;
  font-size: 0.875rem;
  color: #374151;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.produits-table tbody tr,
.sorties-table tbody tr {
  border-bottom: 1px solid #e5e7eb;
  transition: background-color 0.2s;
}

.produits-table tbody tr:hover,
.sorties-table tbody tr:hover {
  background-color: #f9fafb;
}

.produits-table tbody tr:last-child,
.sorties-table tbody tr:last-child {
  border-bottom: none;
}

.produits-table td,
.sorties-table td {
  padding: 1rem;
  font-size: 0.875rem;
  color: #111827;
}

.montant-cell {
  font-weight: 600;
  color: #059669;
}

.text-muted {
  color: #9ca3af;
  font-style: italic;
  font-size: 0.75rem;
}

.loading-state,
.empty-state {
  padding: 2rem;
  text-align: center;
  color: #6b7280;
}

.rapport-stat-icon {
  font-size: 2rem;
  flex-shrink: 0;
}

.rapport-stat-info {
  flex: 1;
}

.rapport-stat-label {
  font-size: 0.875rem;
  color: #6b7280;
  margin-bottom: 0.25rem;
}

.rapport-stat-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1a5f4a;
}

.rapport-section {
  margin-top: 2rem;
}

.rapport-section-title {
  font-size: 1.1rem;
  font-weight: 700;
  color: #1a5f4a;
  margin-bottom: 1rem;
}

.rapport-mouvements {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  max-height: 400px;
  overflow-y: auto;
}

.rapport-mouvement-item {
  padding: 1rem;
  background: #f9fafb;
  border-radius: 10px;
  border-left: 4px solid #10b981;
}

.rapport-mouvement-item.sortie {
  border-left-color: #ef4444;
}

.mouvement-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}

.mouvement-type-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 600;
}

.mouvement-type-badge.entree {
  background: #d1fae5;
  color: #065f46;
}

.mouvement-type-badge.sortie {
  background: #fee2e2;
  color: #991b1b;
}

.mouvement-date {
  font-size: 0.875rem;
  color: #6b7280;
}

.mouvement-details {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.mouvement-product {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.mouvement-product strong {
  font-size: 1rem;
  color: #1f2937;
}

.mouvement-code {
  font-size: 0.875rem;
  color: #6b7280;
  background: #f3f4f6;
  padding: 0.2rem 0.5rem;
  border-radius: 4px;
}

.mouvement-info {
  display: flex;
  gap: 1rem;
  font-size: 0.875rem;
  color: #6b7280;
  flex-wrap: wrap;
}

.mouvement-info strong {
  color: #1f2937;
}

.mouvement-notes {
  font-size: 0.875rem;
  color: #6b7280;
  font-style: italic;
  margin-top: 0.25rem;
  padding-top: 0.5rem;
  border-top: 1px solid #e5e7eb;
}
</style>








