<template>
  <div class="point-vente-page">
    <!-- Vue Admin : Liste des points de vente -->
    <template v-if="isAdmin">
      <div class="products-header">
        <h2 class="dashboard-title">Points de Vente</h2>
        <button 
          @click="openCreateModal" 
          class="btn-primary"
        >
          <span>+</span> Nouveau Point de Vente
        </button>
      </div>

      <!-- Contenu masqué jusqu'à ce qu'un point de vente soit ajouté -->
      <div v-if="pointsVente.length > 0">
      <!-- Dashboard des Ventes - Métriques comme dans la capture -->
      <div class="ventes-dashboard">
        <div class="ventes-dashboard-header">
          <h3>Gestion des Ventes - Point de Vente</h3>
        </div>
        <div class="ventes-metrics-grid">
          <div class="vente-metric-card">
            <div class="vente-metric-label">VENTES (AUJOURD'HUI)</div>
            <div class="vente-metric-value">
              <span class="vente-value-badge">{{ statsGlobales.ventesAujourdhui }}</span>
            </div>
          </div>
          <div class="vente-metric-card">
            <div class="vente-metric-label">CA JOURNALIER</div>
            <div class="vente-metric-value">
              <span class="vente-value-text">{{ formatCurrency(statsGlobales.caJournalier) }}</span>
            </div>
          </div>
          <div class="vente-metric-card">
            <div class="vente-metric-label">RETOURS</div>
            <div class="vente-metric-value">
              <span class="vente-value-badge">{{ statsGlobales.retours }}</span>
            </div>
          </div>
          <div class="vente-metric-card">
            <div class="vente-metric-label">À LIVRER</div>
            <div class="vente-metric-value">
              <span class="vente-value-badge">{{ statsGlobales.aLivrer }}</span>
            </div>
          </div>
          <div class="vente-metric-card">
            <div class="vente-metric-label">À EXPÉDIER</div>
            <div class="vente-metric-value">
              <span class="vente-value-badge">{{ statsGlobales.aExpedier }}</span>
            </div>
          </div>
          <div class="vente-metric-card">
            <div class="vente-metric-label">COMMANDES</div>
            <div class="vente-metric-value">
              <span class="vente-value-badge">{{ statsGlobales.commandes }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Statistiques -->
      <div class="stats-row">
        <StatCard 
          title="Total Points de Vente" 
          :value="stats.totalPointsVente.toString()" 
          :variation="null" 
          icon="🏪" />
        <StatCard 
          title="Ventes Journalières" 
          :value="stats.ventesJournalieres.toString()" 
          :variation="null" 
          icon="📈" />
        <StatCard 
          title="Chiffre d'Affaires (Aujourd'hui)" 
          :value="formatCurrency(stats.chiffreAffairesJournalier)" 
          :variation="null" 
          icon="💰" />
        <StatCard 
          title="Commandes en Attente" 
          :value="stats.commandesEnAttente.toString()" 
          :variation="null" 
          icon="📦" />
      </div>

      <!-- Filtres et recherche -->
      <div class="products-filters">
        <div class="search-box">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Rechercher un point de vente..."
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
        </div>
      </div>

      <!-- Tableau des points de vente -->
      <div class="products-table-container">
        <table class="products-table">
          <thead>
            <tr>
              <th>Nom</th>
              <th>Entrepôt</th>
              <th>Ville</th>
              <th>Responsable</th>
              <th>Ventes (Aujourd'hui)</th>
              <th>CA Journalier</th>
              <th>Retours</th>
              <th>À Livrer</th>
              <th>À Expédier</th>
              <th>Commandes</th>
              <th>Statut</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="12" class="loading-cell">Chargement...</td>
            </tr>
            <tr v-else-if="filteredPointsVente.length === 0">
              <td colspan="12" class="empty-cell">Aucun point de vente trouvé</td>
            </tr>
            <tr v-else v-for="pointVente in filteredPointsVente" :key="pointVente.id_point_vente" @click="goToPointVenteDashboard(pointVente)" style="cursor: pointer;">
              <td>
                <strong>{{ pointVente.nom_point_vente }}</strong>
              </td>
              <td>{{ pointVente.nom_entrepot || 'Magasin' }}</td>
              <td>{{ pointVente.ville || '—' }}</td>
              <td>{{ pointVente.responsable || '—' }}</td>
              <td>
                <span class="stock-badge normal">{{ pointVente.ventes_journalieres || 0 }}</span>
              </td>
              <td class="valeur-stock-cell">
                {{ formatCurrency(pointVente.chiffre_affaires_journalier || 0) }}
              </td>
              <td>
                <span class="stock-badge" :class="pointVente.nombre_retours > 0 ? 'alerte' : 'normal'">
                  {{ pointVente.nombre_retours || 0 }}
                </span>
              </td>
              <td>
                <span class="stock-badge" :class="pointVente.a_livrer > 0 ? 'alerte' : 'normal'">
                  {{ pointVente.a_livrer || 0 }}
                </span>
              </td>
              <td>
                <span class="stock-badge" :class="pointVente.a_expedier > 0 ? 'alerte' : 'normal'">
                  {{ pointVente.a_expedier || 0 }}
                </span>
              </td>
              <td>
                <span class="stock-badge" :class="pointVente.commandes_en_attente > 0 ? 'alerte' : 'normal'">
                  {{ pointVente.commandes_en_attente || 0 }}
                </span>
              </td>
              <td>
                <span :class="['status-badge', pointVente.actif ? 'normal' : 'rupture']">
                  {{ pointVente.actif ? 'Actif' : 'Inactif' }}
                </span>
              </td>
              <td class="actions-cell" @click.stop>
                <button @click.stop="viewPointVente(pointVente)" class="btn-view" title="Voir détails">👁️</button>
                <button @click.stop="openRapportModal(pointVente)" class="btn-rapport" title="Rapport">📊</button>
                <button @click.stop="openEditModal(pointVente)" class="btn-edit" title="Modifier">✏️</button>
                <button @click.stop="confirmDelete(pointVente)" class="btn-delete" title="Supprimer">🗑️</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Création/Modification Point de Vente -->
    <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal-content large" @click.stop>
        <div class="modal-header">
          <h3>{{ isEditMode ? 'Modifier le Point de Vente' : 'Nouveau Point de Vente' }}</h3>
          <button @click="closeModal" class="modal-close">×</button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="savePointVente" class="modal-form">
            <div class="form-section">
              <h4 class="section-title">🏪 Point de vente</h4>
              <div class="form-group">
                <label>Nom du Point de Vente *</label>
                <input
                  v-model="formData.nom_point_vente"
                  type="text"
                  required
                  placeholder="Ex: Boutique Centre-Ville"
                />
              </div>
              <div class="form-group">
                <label>Entrepôt associé</label>
                <select v-model="formData.id_entrepot">
                  <option :value="null">Magasin (Par défaut)</option>
                  <option v-for="entrepot in entrepots" :key="entrepot.id_entrepot" :value="entrepot.id_entrepot">
                    {{ entrepot.nom_entrepot }}
                  </option>
                </select>
                <small class="form-hint">Sélectionnez l'entrepôt qui alimente ce point de vente</small>
              </div>
              <div class="form-group">
                <label>Adresse</label>
                <textarea v-model="formData.adresse" rows="2" placeholder="Adresse complète"></textarea>
              </div>
            </div>

            <div class="form-section">
              <h4 class="section-title">📍 Localisation</h4>
              <div class="form-row">
                <div class="form-group">
                  <label>Ville</label>
                  <input v-model="formData.ville" type="text" placeholder="Ville" />
                </div>
                <div class="form-group">
                  <label>Pays</label>
                  <input v-model="formData.pays" type="text" placeholder="Pays" />
                </div>
              </div>
            </div>

            <div class="form-section">
              <h4 class="section-title">📞 Contact</h4>
              <div class="form-row">
                <div class="form-group">
                  <label>Téléphone</label>
                  <input v-model="formData.telephone" type="text" placeholder="+225 XX XX XX XX XX" />
                </div>
                <div class="form-group">
                  <label>Email</label>
                  <input v-model="formData.email" type="email" placeholder="email@exemple.com" />
                </div>
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label>Responsable</label>
                  <input v-model="formData.responsable" type="text" placeholder="Nom du responsable" />
                </div>
                <div class="form-group">
                  <label>Statut</label>
                  <select v-model="formData.actif">
                    <option :value="1">Actif</option>
                    <option :value="0">Inactif</option>
                  </select>
                  <small class="form-hint">Un point inactif n'apparaît pas dans les listes</small>
                </div>
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" @click="closeModal" class="btn-cancel">Annuler</button>
          <button type="button" @click="savePointVente" class="btn-save" :disabled="saving">
            {{ saving ? 'Enregistrement...' : 'Enregistrer' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Détails Point de Vente -->
    <div v-if="showDetailsModal" class="modal-overlay" @click.self="closeDetailsModal">
      <div class="modal-content details-modal" @click.stop>
        <div class="modal-header">
          <h3>{{ selectedPointVente?.nom_point_vente }}</h3>
          <button @click="closeDetailsModal" class="modal-close">×</button>
        </div>
        <div class="modal-body">
          <div class="details-grid">
            <div class="detail-card">
              <div class="detail-icon">🏭</div>
              <div class="detail-content">
                <div class="detail-label">Entrepôt</div>
                <div class="detail-value">{{ selectedPointVente?.nom_entrepot || 'Magasin' }}</div>
              </div>
            </div>
            <div class="detail-card">
              <div class="detail-icon">📍</div>
              <div class="detail-content">
                <div class="detail-label">Ville</div>
                <div class="detail-value">{{ selectedPointVente?.ville || '—' }}</div>
              </div>
            </div>
            <div class="detail-card">
              <div class="detail-icon">👤</div>
              <div class="detail-content">
                <div class="detail-label">Responsable</div>
                <div class="detail-value">{{ selectedPointVente?.responsable || '—' }}</div>
              </div>
            </div>
            <div class="detail-card">
              <div class="detail-icon">📞</div>
              <div class="detail-content">
                <div class="detail-label">Téléphone</div>
                <div class="detail-value">{{ selectedPointVente?.telephone || '—' }}</div>
              </div>
            </div>
            <div class="detail-card">
              <div class="detail-icon">📧</div>
              <div class="detail-content">
                <div class="detail-label">Email</div>
                <div class="detail-value">{{ selectedPointVente?.email || '—' }}</div>
              </div>
            </div>
            <div class="detail-card" v-if="selectedPointVente?.adresse">
              <div class="detail-icon">🏠</div>
              <div class="detail-content">
                <div class="detail-label">Adresse</div>
                <div class="detail-value">{{ selectedPointVente.adresse }}</div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="goToPointVenteDashboard(selectedPointVente)" class="btn-primary">Accéder au Dashboard</button>
          <button @click="closeDetailsModal" class="btn-secondary">Fermer</button>
        </div>
      </div>
    </div>

    <!-- Modal Rapport Point de Vente -->
    <div v-if="showRapportModal" class="modal-overlay" @click.self="closeRapportModal">
      <div class="modal-content rapport-modal" @click.stop>
        <div class="modal-header">
          <h3>📊 Rapport - {{ rapportPointVente?.nom_point_vente }}</h3>
          <button @click="closeRapportModal" class="modal-close">×</button>
        </div>
        <div class="rapport-content">
          <div v-if="loadingRapport" class="loading-cell">Chargement du rapport...</div>
          <div v-else>
            <!-- Statistiques du rapport -->
            <div class="rapport-stats">
              <div class="rapport-stat-card">
                <div class="rapport-stat-icon">📈</div>
                <div class="rapport-stat-info">
                  <div class="rapport-stat-label">Ventes Journalières</div>
                  <div class="rapport-stat-value">{{ rapportData.ventes_journalieres?.length || 0 }}</div>
                </div>
              </div>
              <div class="rapport-stat-card">
                <div class="rapport-stat-icon">↩️</div>
                <div class="rapport-stat-info">
                  <div class="rapport-stat-label">Retours</div>
                  <div class="rapport-stat-value">{{ rapportData.retours || 0 }}</div>
                </div>
              </div>
              <div class="rapport-stat-card">
                <div class="rapport-stat-icon">🚚</div>
                <div class="rapport-stat-info">
                  <div class="rapport-stat-label">À Livrer</div>
                  <div class="rapport-stat-value">{{ rapportData.a_livrer || 0 }}</div>
                </div>
              </div>
              <div class="rapport-stat-card">
                <div class="rapport-stat-icon">📦</div>
                <div class="rapport-stat-info">
                  <div class="rapport-stat-label">À Expédier</div>
                  <div class="rapport-stat-value">{{ rapportData.a_expedier || 0 }}</div>
                </div>
              </div>
              <div class="rapport-stat-card">
                <div class="rapport-stat-icon">🛒</div>
                <div class="rapport-stat-info">
                  <div class="rapport-stat-label">Commandes</div>
                  <div class="rapport-stat-value">{{ rapportData.commandes_en_attente || 0 }}</div>
                </div>
              </div>
            </div>

            <!-- Ventes journalières (7 derniers jours) -->
            <div class="rapport-section" v-if="rapportData.ventes_journalieres && rapportData.ventes_journalieres.length > 0">
              <h4 class="rapport-section-title">📈 Ventes Journalières (7 derniers jours)</h4>
              <div class="rapport-ventes-journalieres">
                <div 
                  v-for="vente in rapportData.ventes_journalieres" 
                  :key="vente.date"
                  class="rapport-vente-item"
                >
                  <div class="vente-date">{{ formatDate(vente.date) }}</div>
                  <div class="vente-info">
                    <span>{{ vente.nombre_ventes }} ventes</span>
                    <strong>{{ formatCurrency(vente.chiffre_affaires) }}</strong>
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

    <!-- Notifications -->
    <div v-if="notification.show" class="notification-overlay" @click="closeNotification">
      <div :class="['notification-modal', notification.type]">
        <div class="notification-title">{{ notification.title }}</div>
        <div class="notification-message">{{ notification.message }}</div>
        <button @click="closeNotification" class="notification-close">OK</button>
      </div>
    </div>

    <!-- Confirmations -->
    <div v-if="confirmation.show" class="modal-overlay" @click.self="closeConfirmation">
      <div class="modal-content confirmation-modal" @click.stop>
        <div class="modal-header modal-header-with-icon">
          <div class="modal-header-start">
            <span class="modal-header-icon">{{ confirmation.icon || '⚠️' }}</span>
            <h3>{{ confirmation.title }}</h3>
          </div>
          <button @click="closeConfirmation" class="modal-close">×</button>
        </div>
        <div class="modal-body">
          <p>{{ confirmation.message }}</p>
        </div>
        <div class="modal-actions">
          <button @click="closeConfirmation" class="btn-cancel">Annuler</button>
          <button @click="confirmAction" class="btn-danger">Confirmer</button>
        </div>
      </div>
    </div>
    </template>

    <!-- Vue Agent : Gestion des ventes, livraisons, commandes (ou Historique pour admin via ?point_vente=) -->
    <template v-else>
      <div class="agent-point-vente-page">
        <div class="agent-header">
          <button v-if="isAdmin && $route.query.point_vente" @click="router.push('/point-vente')" class="btn-back-to-list">
            ← Retour à la liste
          </button>
          <h2 class="dashboard-title">{{ currentPointVente?.nom_point_vente || 'Chargement...' }}</h2>
          <div class="agent-stats-cards">
            <div class="agent-stat-card">
              <div class="stat-icon">💰</div>
              <div class="stat-info">
                <div class="stat-label">CA Aujourd'hui</div>
                <div class="stat-value">{{ formatCurrency(agentStats.caJournalier) }}</div>
              </div>
            </div>
            <div class="agent-stat-card">
              <div class="stat-icon">📦</div>
              <div class="stat-info">
                <div class="stat-label">Ventes Aujourd'hui</div>
                <div class="stat-value">{{ agentStats.ventesAujourdhui }}</div>
              </div>
            </div>
            <div class="agent-stat-card">
              <div class="stat-icon">🚚</div>
              <div class="stat-info">
                <div class="stat-label">À Livrer</div>
                <div class="stat-value">{{ agentStats.aLivrer }}</div>
              </div>
            </div>
            <div class="agent-stat-card">
              <div class="stat-icon">🛒</div>
              <div class="stat-info">
                <div class="stat-label">Commandes</div>
                <div class="stat-value">{{ agentStats.commandes }}</div>
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
          <!-- Onglet Ventes -->
          <div v-if="activeAgentTab === 'ventes'" class="agent-tab-panel">
            <div class="panel-header">
              <h3>Mes Ventes</h3>
              <div class="panel-header-actions">
                <button @click="openImportModal" class="btn-secondary btn-import-entrepot" title="Importer des produits depuis l'entrepôt">
                  📥 Importer depuis l'entrepôt
                </button>
                <button @click="openSaleModal" class="btn-primary">
                  <span>+</span> Nouvelle Vente
                </button>
              </div>
            </div>
            
            <!-- Filtres de recherche -->
            <div class="ventes-filters">
              <div class="filter-group">
                <label>Date de début:</label>
                <input 
                  type="date" 
                  v-model="venteFilterDateDebut" 
                  @change="loadAgentVentes"
                  class="filter-input"
                />
              </div>
              <div class="filter-group">
                <label>Date de fin:</label>
                <input 
                  type="date" 
                  v-model="venteFilterDateFin" 
                  @change="loadAgentVentes"
                  class="filter-input"
                />
              </div>
              <div class="filter-group">
                <button @click="clearVenteFilters" class="btn-secondary">Réinitialiser</button>
              </div>
            </div>
            
            <!-- Tableau des ventes -->
            <div class="ventes-table-container">
              <div v-if="loadingVentes" class="loading-state">Chargement des ventes...</div>
              <div v-else-if="agentVentes.length === 0" class="empty-state">Aucune vente trouvée</div>
              <table v-else class="ventes-table">
                <thead>
                  <tr>
                    <th>Date</th>
                    <th>Client</th>
                    <th>Produits</th>
                    <th>Quantité</th>
                    <th>Montant Total</th>
                    <th>Vendeur</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="vente in agentVentes" :key="vente.id_vente">
                    <td>{{ formatDate(vente.date_vente) }}</td>
                    <td>
                      <span v-if="vente.client_nom">
                        {{ vente.client_nom }} {{ vente.client_prenom || '' }}
                      </span>
                      <span v-else class="text-muted">Client anonyme</span>
                    </td>
                    <td>
                      <div class="produits-list">
                        <span 
                          v-for="(produit, index) in (vente.produits || [])" 
                          :key="index"
                          class="produit-badge"
                        >
                          {{ produit.produit_nom || produit.nom }} ({{ produit.quantite }})
                        </span>
                      </div>
                    </td>
                    <td>{{ vente.nombre_produits || (vente.produits?.length || 0) }}</td>
                    <td class="montant-cell">{{ formatCurrency(vente.montant_total) }}</td>
                    <td>
                      <span v-if="vente.user_nom">
                        {{ vente.user_nom }} {{ vente.user_prenom || '' }}
                      </span>
                      <span v-else class="text-muted">—</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Onglet Livraisons -->
          <div v-if="activeAgentTab === 'livraisons'" class="agent-tab-panel">
            <div class="panel-header">
              <h3>Livraisons à Effectuer</h3>
              <button @click="openLivraisonModal" class="btn-primary">
                <span>+</span> Nouvelle Livraison
              </button>
            </div>
            <div class="livraisons-list">
              <div v-if="loadingLivraisons" class="loading-state">Chargement des livraisons...</div>
              <div v-else-if="agentLivraisons.length === 0" class="empty-state">Aucune livraison en attente</div>
              <div v-else v-for="livraison in agentLivraisons" :key="livraison.id_vente" class="livraison-item">
                <div class="livraison-info">
                  <div class="livraison-client">{{ (livraison.client_nom ? `${livraison.client_nom} ${livraison.client_prenom || ''}`.trim() : 'Client') || 'Client' }}</div>
                  <div class="livraison-adresse">{{ livraison.adresse || livraison.adresse_livraison || 'Adresse non spécifiée' }}</div>
                  <div class="livraison-date">Date: {{ formatDate(livraison.date_vente || livraison.date_livraison) }}</div>
                </div>
                <div class="livraison-actions">
                  <button @click="markAsDelivered(livraison)" class="btn-primary">Marquer comme livré</button>
                </div>
              </div>
            </div>
          </div>

          <!-- Onglet Commandes -->
          <div v-if="activeAgentTab === 'commandes'" class="agent-tab-panel">
            <div class="panel-header">
              <h3>Commandes en Attente</h3>
              <button @click="openCommandeModal" class="btn-primary">
                <span>+</span> Nouvelle Commande
              </button>
            </div>
            <div class="commandes-list">
              <div v-if="loadingCommandes" class="loading-state">Chargement des commandes...</div>
              <div v-else-if="agentCommandes.length === 0" class="empty-state">Aucune commande en attente</div>
              <div v-else v-for="commande in agentCommandes" :key="commande.id_vente" class="commande-item">
                <div class="commande-info">
                  <div class="commande-client">{{ (commande.client_nom ? `${commande.client_nom} ${commande.client_prenom || ''}`.trim() : 'Client') || 'Client' }}</div>
                  <div class="commande-montant">{{ formatCurrency(commande.montant_total) }}</div>
                  <div class="commande-date">Date: {{ formatDate(commande.date_vente) }}</div>
                </div>
                <div class="commande-actions">
                  <button @click="processCommande(commande)" class="btn-primary">Traiter</button>
                </div>
              </div>
            </div>
          </div>

          <!-- Onglet Retours -->
          <!-- Onglet Stock -->
          <div v-if="activeAgentTab === 'stock'" class="agent-tab-panel">
            <div class="panel-header">
              <h3>Stock du Point de Vente</h3>
              <button @click="openImportModal" class="btn-primary">
                <span>📥</span> Importer depuis l'entrepôt
              </button>
            </div>
            
            <!-- Filtres de recherche -->
            <div class="stock-filters">
              <div class="search-box">
                <input
                  v-model="stockSearchQuery"
                  type="text"
                  placeholder="🔍 Rechercher un produit..."
                  class="search-input"
                />
              </div>
              <div class="filter-buttons">
                <select v-model="stockSelectedCategory" class="filter-select">
                  <option value="">Toutes les catégories</option>
                  <option v-for="cat in stockCategories" :key="cat" :value="cat">{{ cat }}</option>
                </select>
              </div>
            </div>
            
            <!-- Tableau des produits -->
            <div class="stock-table-container">
              <div v-if="loadingStock" class="loading-state">Chargement du stock...</div>
              <div v-else-if="stockFilteredProducts.length === 0" class="empty-state">
                <p>Aucun produit disponible dans ce point de vente.</p>
                <button @click="openImportModal" class="btn-primary">Importer des produits depuis l'entrepôt</button>
              </div>
              <table v-else class="stock-table">
                <thead>
                  <tr>
                    <th>Produit</th>
                    <th>Code</th>
                    <th>Catégorie</th>
                    <th>Stock Disponible</th>
                    <th>Prix de Vente</th>
                    <th>Valeur Stock</th>
                    <th>Statut</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr 
                    v-for="produit in stockFilteredProducts" 
                    :key="produit.id_produit"
                    :class="{ 'out-of-stock-row': produit.quantite_stock === 0 }"
                  >
                    <td>
                      <div class="produit-cell">
                        <span v-if="produit.image" class="produit-image-small">
                          <img :src="produit.image" :alt="produit.nom" />
                        </span>
                        <span v-else class="produit-icon-small">📦</span>
                        <strong>{{ produit.nom }}</strong>
                      </div>
                    </td>
                    <td>{{ produit.code_produit || '—' }}</td>
                    <td>{{ produit.categorie || '—' }}</td>
                    <td>
                      <span 
                        :class="['stock-badge', 
                          produit.quantite_stock === 0 ? 'rupture' : 
                          produit.quantite_stock <= (produit.seuil_minimum || 0) ? 'alerte' : 'normal'
                        ]"
                      >
                        {{ produit.quantite_stock || 0 }}
                      </span>
                    </td>
                    <td class="valeur-stock-cell">{{ formatCurrency(produit.prix_vente || 0) }}</td>
                    <td class="valeur-stock-cell">{{ formatCurrency((produit.prix_vente || 0) * (produit.quantite_stock || 0)) }}</td>
                    <td>
                      <span 
                        :class="['status-badge', 
                          produit.statut_stock === 'rupture' ? 'rupture' :
                          produit.statut_stock === 'alerte' ? 'alerte' : 'normal'
                        ]"
                      >
                        {{ produit.statut_stock === 'rupture' ? 'Rupture' : 
                           produit.statut_stock === 'alerte' ? 'Stock faible' : 'Normal' }}
                      </span>
                    </td>
                    <td>
                      <button 
                        @click="openAddQuantityModal(produit)" 
                        class="btn-add-quantity"
                        title="Ajouter des quantités depuis l'entrepôt"
                      >
                        ➕ Ajouter
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div v-if="activeAgentTab === 'retours'" class="agent-tab-panel">
            <div class="panel-header">
              <h3>Retours</h3>
              <button @click="openRetourModal" class="btn-primary">
                <span>+</span> Nouveau Retour
              </button>
            </div>
            <div class="retours-list">
              <div v-if="loadingRetours" class="loading-state">Chargement des retours...</div>
              <div v-else-if="agentRetours.length === 0" class="empty-state">Aucun retour</div>
              <div v-else v-for="retour in agentRetours" :key="retour.id_vente" class="retour-item">
                <div class="retour-info">
                  <div class="retour-client">{{ (retour.client_nom ? `${retour.client_nom} ${retour.client_prenom || ''}`.trim() : 'Client') || 'Client' }}</div>
                  <div class="retour-montant">{{ formatCurrency(retour.montant_total) }}</div>
                  <div class="retour-date">Date: {{ formatDate(retour.date_vente) }}</div>
                </div>
                <div class="retour-actions">
                  <button @click="viewRetourDetails(retour)" class="btn-view">Voir</button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>

    <!-- Modale de Vente -->
    <div v-if="showSaleModal" class="modal-overlay" @click.self="closeSaleModal">
      <div class="modal-content sale-modal" @click.stop>
        <div class="modal-header">
          <h3>Nouvelle Vente</h3>
          <button @click="closeSaleModal" class="modal-close">×</button>
        </div>
        <div class="modal-body sale-modal-body">
          <div class="sale-modal-container">
            <!-- Colonne gauche : Panier -->
            <div class="sale-cart-column">
              <div class="sale-cart-header">
                <h4>Panier</h4>
                <button @click="clearSaleCart" class="btn-clear-small" v-if="saleCart.length > 0">Vider</button>
              </div>
              <div class="sale-cart-content">
                <div v-if="saleCart.length === 0" class="empty-cart-small">
                  <p>Panier vide</p>
                </div>
                <div v-else class="sale-cart-items">
                  <div v-for="(item, index) in saleCart" :key="`${item.id_produit}-${index}`" class="sale-cart-item">
                    <div class="sale-cart-item-info">
                      <div class="sale-cart-item-name">{{ item.nom }}</div>
                      <div class="sale-cart-item-qty">
                        <button @click="decreaseSaleQuantity(index)" class="qty-btn-small">−</button>
                        <span>{{ item.quantite }}</span>
                        <button @click="increaseSaleQuantity(index)" class="qty-btn-small">+</button>
                      </div>
                    </div>
                    <div class="sale-cart-item-price">{{ formatCurrency(item.sous_total) }}</div>
                    <button @click="removeFromSaleCart(index)" class="btn-remove-small">×</button>
                  </div>
                </div>
              </div>
              <div class="sale-cart-footer" v-if="saleCart.length > 0">
                <div class="sale-summary">
                  <div class="sale-summary-row">
                    <span>Total:</span>
                    <strong>{{ formatCurrency(saleTotal) }}</strong>
                  </div>
                </div>
                <button 
                  @click="processSaleFromModal" 
                  class="btn-checkout" 
                  type="button"
                >
                  💳 Valider la vente
                </button>
              </div>
            </div>

            <!-- Colonne droite : Produits -->
            <div class="sale-products-column">
              <div class="sale-products-header">
                <input 
                  v-model="saleSearchQuery"
                  type="text"
                  placeholder="🔍 Rechercher un produit..."
                  class="sale-search-input"
                />
                <select v-model="saleSelectedCategory" class="sale-filter-select">
                  <option value="">Toutes les catégories</option>
                  <option v-for="cat in saleCategories" :key="cat" :value="cat">{{ cat }}</option>
                </select>
                <button 
                  @click="loadSaleProducts(true)" 
                  class="btn-refresh-products"
                  title="Actualiser les produits"
                  :disabled="loadingSaleProducts"
                >
                  {{ loadingSaleProducts ? '⏳' : '🔄' }}
                </button>
              </div>
              <div v-if="loadingSaleProducts" class="sale-loading-state">
                <div class="loading-spinner">⏳</div>
                <p>Chargement des produits...</p>
              </div>
              <div v-else-if="saleFilteredProducts.length === 0" class="sale-empty-state">
                <div class="empty-state-icon">📦</div>
                <p class="empty-state-title">Aucun produit disponible</p>
                <p class="empty-state-message">
                  Aucun produit n'est actuellement disponible dans ce point de vente.
                  <br />
                  Importez des produits depuis l'entrepôt pour pouvoir les vendre.
                </p>
                <button @click="closeSaleModal(); openImportModal()" class="btn-primary">
                  📥 Importer des produits depuis l'entrepôt
                </button>
              </div>
              <div v-else class="sale-products-grid">
                <div 
                  v-for="product in saleFilteredProducts" 
                  :key="product.id_produit"
                  class="sale-product-card"
                  :class="{ 'out-of-stock': product.quantite_stock === 0 }"
                  @click="addToSaleCart(product)"
                >
                  <div class="sale-product-image">
                    <img v-if="product.image" :src="product.image" :alt="product.nom" />
                    <span v-else class="sale-product-icon">📦</span>
                  </div>
                  <div class="sale-product-info">
                    <div class="sale-product-name">{{ product.nom }}</div>
                    <div class="sale-product-code">{{ product.code_produit }}</div>
                    <div class="sale-product-stock">Stock: {{ product.quantite_stock }}</div>
                  </div>
                  <div class="sale-product-price">{{ formatCurrency(product.prix_vente) }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modale Livraison -->
    <div v-if="showLivraisonModal" class="modal-overlay" @click.self="closeLivraisonModal">
      <div class="modal-content point-vente-modal" @click.stop>
        <div class="modal-header">
          <h3>Nouvelle Livraison</h3>
          <button @click="closeLivraisonModal" class="modal-close">×</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Client *</label>
            <input v-model="livraisonForm.client_nom" type="text" placeholder="Nom du client" required />
          </div>
          <div class="form-group">
            <label>Adresse de livraison *</label>
            <textarea v-model="livraisonForm.adresse" rows="3" placeholder="Adresse complète" required></textarea>
          </div>
          <div class="form-group">
            <label>Date de livraison prévue</label>
            <input v-model="livraisonForm.date_livraison" type="datetime-local" />
          </div>
          <div class="form-group">
            <label>Notes</label>
            <textarea v-model="livraisonForm.notes" rows="2" placeholder="Notes supplémentaires"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="closeLivraisonModal" class="btn-secondary">Annuler</button>
          <button @click="saveLivraison" class="btn-primary" :disabled="savingLivraison">
            {{ savingLivraison ? 'Enregistrement...' : 'Enregistrer' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Modale Commande -->
    <div v-if="showCommandeModal" class="modal-overlay" @click.self="closeCommandeModal">
      <div class="modal-content point-vente-modal" @click.stop>
        <div class="modal-header">
          <h3>Nouvelle Commande</h3>
          <button @click="closeCommandeModal" class="modal-close">×</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Client *</label>
            <input v-model="commandeForm.client_nom" type="text" placeholder="Nom du client" required />
          </div>
          <div class="form-group">
            <label>Produit *</label>
            <input v-model="commandeForm.produit_nom" type="text" placeholder="Nom du produit" required />
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Quantité *</label>
              <input v-model.number="commandeForm.quantite" type="number" min="1" required />
            </div>
            <div class="form-group">
              <label>Prix unitaire *</label>
              <input v-model.number="commandeForm.prix_unitaire" type="number" min="0" step="0.01" required />
            </div>
          </div>
          <div class="form-group">
            <label>Date de livraison prévue</label>
            <input v-model="commandeForm.date_livraison" type="datetime-local" />
          </div>
          <div class="form-group">
            <label>Notes</label>
            <textarea v-model="commandeForm.notes" rows="2" placeholder="Notes supplémentaires"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="closeCommandeModal" class="btn-secondary">Annuler</button>
          <button @click="saveCommande" class="btn-primary" :disabled="savingCommande">
            {{ savingCommande ? 'Enregistrement...' : 'Enregistrer' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Modale Retour -->
    <div v-if="showRetourModal" class="modal-overlay" @click.self="closeRetourModal">
      <div class="modal-content point-vente-modal" @click.stop>
        <div class="modal-header">
          <h3>Nouveau Retour</h3>
          <button @click="closeRetourModal" class="modal-close">×</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Client *</label>
            <input v-model="retourForm.client_nom" type="text" placeholder="Nom du client" required />
          </div>
          <div class="form-group">
            <label>Produit retourné *</label>
            <input v-model="retourForm.produit_nom" type="text" placeholder="Nom du produit" required />
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Quantité *</label>
              <input v-model.number="retourForm.quantite" type="number" min="1" required />
            </div>
            <div class="form-group">
              <label>Montant remboursé *</label>
              <input v-model.number="retourForm.montant_total" type="number" min="0" step="0.01" required />
            </div>
          </div>
          <div class="form-group">
            <label>Raison du retour *</label>
            <select v-model="retourForm.raison" required>
              <option value="">Sélectionner une raison</option>
              <option value="defaut">Défaut produit</option>
              <option value="non_conforme">Non conforme</option>
              <option value="client">Demande client</option>
              <option value="autre">Autre</option>
            </select>
          </div>
          <div class="form-group">
            <label>Notes</label>
            <textarea v-model="retourForm.notes" rows="2" placeholder="Notes supplémentaires"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="closeRetourModal" class="btn-secondary">Annuler</button>
          <button @click="saveRetour" class="btn-primary" :disabled="savingRetour">
            {{ savingRetour ? 'Enregistrement...' : 'Enregistrer' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Modale Importer depuis l'entrepôt -->
    <div v-if="showImportModal" class="modal-overlay" @click.self="closeImportModal">
      <div class="modal-content import-entrepot-modal" @click.stop>
        <div class="modal-header">
          <h3>📥 Importer des produits depuis l'entrepôt</h3>
          <button @click="closeImportModal" class="modal-close">×</button>
        </div>
        <div class="modal-body">
          <div class="form-group" v-if="!hasSingleEntrepot">
            <label>Entrepôt *</label>
            <select v-model="importEntrepotId" @change="loadImportProduits" class="form-select">
              <option :value="null">Choisir un entrepôt</option>
              <option v-for="e in entrepots" :key="e.id_entrepot" :value="e.id_entrepot">
                {{ e.nom_entrepot }}
              </option>
            </select>
          </div>
          <div v-else-if="selectedEntrepotName" class="form-group">
            <label>Entrepôt</label>
            <div class="selected-entrepot-display">
              <span class="entrepot-badge">📦 {{ selectedEntrepotName }}</span>
              <small class="form-hint">Votre entrepôt assigné (sélection automatique)</small>
            </div>
          </div>
          <div v-if="loadingImportProduits" class="import-loading">Chargement des produits...</div>
          <div v-else-if="importEntrepotId && importProduits.length === 0" class="import-empty">
            Aucun produit dans cet entrepôt.
          </div>
          <div v-else-if="importEntrepotId && importProduits.length > 0" class="import-products-section">
            <h4>Produits disponibles</h4>
            <div class="import-products-table-wrap">
              <table class="import-products-table">
                <thead>
                  <tr>
                    <th>Produit</th>
                    <th>Code</th>
                    <th>Stock</th>
                    <th>Quantité à importer</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="p in importProduits" :key="p.id_produit" :class="{ 'out-of-stock': (p.quantite_stock || 0) === 0 }">
                    <td>{{ p.nom }}</td>
                    <td>{{ p.code_produit || '—' }}</td>
                    <td>{{ p.quantite_stock || 0 }}</td>
                    <td>
                      <input
                        v-model.number="importQuantites[p.id_produit]"
                        type="number"
                        min="0"
                        :max="p.quantite_stock || 0"
                        class="import-qty-input"
                        placeholder="0"
                      />
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <p class="import-hint">Saisissez la quantité à transférer vers ce point de vente. Le stock de l'entrepôt sera diminué d'autant.</p>
          </div>
          <div v-if="importing" class="import-progress">
            <div class="progress-spinner">⏳</div>
            <p>Import en cours... Veuillez patienter.</p>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="closeImportModal" class="btn-secondary" :disabled="importing">Annuler</button>
          <button
            @click="processImport"
            class="btn-primary"
            :disabled="importing || !hasImportSelection"
          >
            {{ importing ? '⏳ Import en cours...' : '📥 Importer la sélection' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Modale Ajouter Quantité -->
    <div v-if="showAddQuantityModal && selectedProductForQuantity" class="modal-overlay" @click.self="closeAddQuantityModal">
      <div class="modal-content medium" @click.stop>
        <div class="modal-header">
          <h3>Ajouter des quantités</h3>
          <button @click="closeAddQuantityModal" class="modal-close">×</button>
        </div>
        <div class="modal-body">
          <div v-if="loadingEntrepotStock" class="loading-state">
            <p>Chargement du stock disponible dans l'entrepôt...</p>
          </div>
          <div v-else>
            <div class="product-info-section">
              <h4>{{ selectedProductForQuantity.nom }}</h4>
              <p class="product-code-hint">Code: {{ selectedProductForQuantity.code_produit || '—' }}</p>
              <div class="stock-info-grid">
                <div class="stock-info-item">
                  <span class="stock-label">Stock actuel (Point de vente):</span>
                  <span class="stock-value current">{{ selectedProductForQuantity.quantite_stock || 0 }}</span>
                </div>
                <div class="stock-info-item">
                  <span class="stock-label">Stock disponible (Entrepôt):</span>
                  <span class="stock-value available" :class="{ 'low-stock': entrepotStockAvailable <= 0 }">
                    {{ entrepotStockAvailable }}
                  </span>
                </div>
              </div>
            </div>
            
            <div class="form-group">
              <label for="add-quantity-input">Quantité à ajouter</label>
              <input
                id="add-quantity-input"
                v-model.number="addQuantityValue"
                type="number"
                :min="1"
                :max="entrepotStockAvailable"
                class="form-input"
                placeholder="Saisissez la quantité"
                @input="validateAddQuantity"
              />
              <p class="form-hint" v-if="entrepotStockAvailable > 0">
                Maximum disponible: <strong>{{ entrepotStockAvailable }}</strong>
              </p>
              <p class="form-hint error" v-else>
                ⚠️ Aucun stock disponible dans l'entrepôt
              </p>
            </div>
            
            <div v-if="addQuantityValue > 0 && entrepotStockAvailable > 0" class="quantity-preview">
              <div class="preview-row">
                <span>Stock actuel:</span>
                <span>{{ selectedProductForQuantity.quantite_stock || 0 }}</span>
              </div>
              <div class="preview-row">
                <span>Quantité à ajouter:</span>
                <span class="highlight">+{{ addQuantityValue }}</span>
              </div>
              <div class="preview-row total">
                <span>Nouveau stock:</span>
                <span class="highlight total">{{ (selectedProductForQuantity.quantite_stock || 0) + addQuantityValue }}</span>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="closeAddQuantityModal" class="btn-secondary" :disabled="addingQuantity">Annuler</button>
          <button
            @click="processAddQuantity"
            class="btn-primary"
            :disabled="addingQuantity || addQuantityValue <= 0 || addQuantityValue > entrepotStockAvailable || entrepotStockAvailable <= 0"
          >
            {{ addingQuantity ? '⏳ Ajout en cours...' : '✅ Ajouter la quantité' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Modale Détails de Vente -->
    <div v-if="showVenteDetailsModal && selectedVenteDetails" class="modal-overlay" @click.self="showVenteDetailsModal = false">
      <div class="modal-content medium">
        <div class="modal-header">
          <h3>Détails de la Vente</h3>
          <button @click="showVenteDetailsModal = false" class="modal-close">×</button>
        </div>
        <div class="modal-body">
          <div class="vente-details-info">
            <div class="info-row">
              <span class="info-label">Date:</span>
              <span class="info-value">{{ formatDate(selectedVenteDetails.date_vente) }}</span>
            </div>
            <div class="info-row" v-if="selectedVenteDetails.client_nom">
              <span class="info-label">Client:</span>
              <span class="info-value">{{ selectedVenteDetails.client_nom }} {{ selectedVenteDetails.client_prenom || '' }}</span>
            </div>
            <div class="info-row">
              <span class="info-label">Point de Vente:</span>
              <span class="info-value">{{ selectedVenteDetails.nom_point_vente }}</span>
            </div>
            <div class="info-row">
              <span class="info-label">Vendeur:</span>
              <span class="info-value">{{ selectedVenteDetails.user_nom }} {{ selectedVenteDetails.user_prenom || '' }}</span>
            </div>
          </div>
          <div class="vente-details-products">
            <h4>Produits vendus ({{ selectedVenteDetails.nombre_produits || selectedVenteDetails.produits?.length || 0 }})</h4>
            <div class="products-list">
              <div 
                v-for="(produit, index) in (selectedVenteDetails.produits || [])" 
                :key="index"
                class="product-item"
              >
                <div class="product-name">{{ produit.produit_nom || produit.nom }}</div>
                <div class="product-details">
                  <span>Code: {{ produit.code_produit || 'N/A' }}</span>
                  <span>Qté: {{ produit.quantite }}</span>
                  <span>Prix: {{ formatCurrency(produit.prix_unitaire) }}</span>
                  <span class="product-total">Total: {{ formatCurrency(produit.montant_total || produit.sous_total) }}</span>
                </div>
              </div>
            </div>
          </div>
          <div class="vente-details-total">
            <div class="total-row">
              <span class="total-label">Montant Total:</span>
              <span class="total-value">{{ formatCurrency(selectedVenteDetails.montant_total) }}</span>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="showVenteDetailsModal = false" class="btn-secondary">Fermer</button>
        </div>
      </div>
    </div>

    <!-- Modale Reçu de Vente -->
    <div v-if="showReceiptModal && lastSaleReceipt" class="modal-overlay receipt-overlay" @click.self="closeReceipt">
      <div class="receipt-modal" @click.stop>
        <div class="receipt-header">
          <h2>Reçu de Vente</h2>
          <div class="receipt-actions">
            <button @click="printReceipt" class="btn-print-receipt">🖨️ Imprimer</button>
            <button @click="closeReceipt" class="btn-close-receipt">×</button>
          </div>
        </div>
        <div class="receipt-body">
          <div class="receipt-company">
            <h3>PROSTOCK</h3>
            <p>Reçu de Vente</p>
          </div>
          <div class="receipt-info">
            <div class="receipt-info-row">
              <span class="receipt-label">Point de Vente:</span>
              <span class="receipt-value">{{ lastSaleReceipt.point_vente }}</span>
            </div>
            <div class="receipt-info-row">
              <span class="receipt-label">Date:</span>
              <span class="receipt-value">{{ lastSaleReceipt.date }}</span>
            </div>
            <div class="receipt-info-row">
              <span class="receipt-label">N° Vente:</span>
              <span class="receipt-value">#{{ lastSaleReceipt.id_vente }}</span>
            </div>
          </div>
          <div class="receipt-items">
            <div class="receipt-items-header">
              <span>Produit</span>
              <span>Qté</span>
              <span>Prix</span>
              <span>Total</span>
            </div>
            <div 
              v-for="(produit, index) in lastSaleReceipt.produits" 
              :key="index"
              class="receipt-item-row"
            >
              <span class="receipt-item-name">{{ produit.nom }}</span>
              <span class="receipt-item-qty">{{ produit.quantite }}</span>
              <span class="receipt-item-price">{{ formatCurrency(produit.prix_unitaire) }}</span>
              <span class="receipt-item-total">{{ formatCurrency(produit.sous_total) }}</span>
            </div>
          </div>
          <div class="receipt-summary">
            <div class="receipt-summary-row">
              <span>Nombre d'articles:</span>
              <span>{{ lastSaleReceipt.nombre_articles }}</span>
            </div>
            <div class="receipt-summary-row" v-if="lastSaleReceipt.remise > 0">
              <span>Sous-total:</span>
              <span>{{ formatCurrency(lastSaleReceipt.sous_total) }}</span>
            </div>
            <div class="receipt-summary-row" v-if="lastSaleReceipt.remise > 0">
              <span>Remise:</span>
              <span class="receipt-discount">-{{ formatCurrency(lastSaleReceipt.remise) }}</span>
            </div>
            <div class="receipt-summary-row receipt-total-row">
              <span>Total à payer:</span>
              <span class="receipt-total">{{ formatCurrency(lastSaleReceipt.total) }}</span>
            </div>
          </div>
          <div class="receipt-footer">
            <p>Merci de votre visite !</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onActivated, watch, inject } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import StatCard from '../components/StatCard.vue'
import { apiService } from '../composables/Api/apiService.js'
import apiCompta from '../composables/Api/apiCompta.js'
import { uploadToCloudinary } from '../config/cloudinary.js'
import jsPDF from 'jspdf'

import { useCurrency } from '../composables/useCurrency.js'
import { logJournal } from '../composables/useJournal'

import { useAuthStore } from '../stores/auth.js'
const authStore = useAuthStore()

const router = useRouter()
const route = useRoute()

const { formatPrice: formatCurrency } = useCurrency()

// Vérifier si l'utilisateur est admin
const isAdmin = computed(() => {
  const user = authStore.user
  if (!user) return false
  // Vérifier le rôle (peut être 'Admin', 'admin', 'superadmin', etc.)
  const role = String(user.role || user.user_role || '').toLowerCase()
  return role === 'admin' || role === 'superadmin'
})

const pointsVente = ref([])
const entrepots = ref([])
const loading = ref(false)
const searchQuery = ref('')
const filterActif = ref(null)
const showModal = ref(false)
const isEditMode = ref(false)
const saving = ref(false)
const showDetailsModal = ref(false)
const selectedPointVente = ref(null)
const showRapportModal = ref(false)
const rapportPointVente = ref(null)
const rapportData = ref({
  ventes_journalieres: [],
  retours: 0,
  a_livrer: 0,
  a_expedier: 0,
  commandes_en_attente: 0
})
const loadingRapport = ref(false)

// Vue Agent
const currentPointVente = ref(null)
const activeAgentTab = ref('ventes')
const agentTabs = [
  { id: 'ventes', label: 'Ventes', icon: '💰' },
  { id: 'stock', label: 'Stock', icon: '📦' },
  { id: 'livraisons', label: 'Livraisons', icon: '🚚' },
  { id: 'commandes', label: 'Commandes', icon: '🛒' },
  { id: 'retours', label: 'Retours', icon: '↩️' }
]
const agentStats = ref({
  caJournalier: 0,
  ventesAujourdhui: 0,
  aLivrer: 0,
  commandes: 0
})
const agentVentes = ref([])
const agentLivraisons = ref([])
const agentCommandes = ref([])
const agentRetours = ref([])
const agentStockProduits = ref([])
const loadingVentes = ref(false)
const loadingLivraisons = ref(false)
const loadingCommandes = ref(false)
const loadingRetours = ref(false)
const loadingStock = ref(false)
const stockSearchQuery = ref('')
const stockSelectedCategory = ref('')
const stockCategories = ref([])

// Filtres pour les ventes
const venteFilterDateDebut = ref('')
const venteFilterDateFin = ref('')

// Modales Agent
const showSaleModal = ref(false)
const showLivraisonModal = ref(false)
const showCommandeModal = ref(false)
const showRetourModal = ref(false)
const showVenteDetailsModal = ref(false)
const selectedVenteDetails = ref(null)

// Modale Vente
const saleCart = ref([])
const saleProducts = ref([])
const saleSearchQuery = ref('')
const saleSelectedCategory = ref('')
const saleCategories = ref([])
const processingSale = ref(false)
const loadingSaleProducts = ref(false)

// Formulaires
const livraisonForm = ref({
  client_nom: '',
  adresse: '',
  date_livraison: '',
  notes: ''
})
const commandeForm = ref({
  client_nom: '',
  produit_nom: '',
  quantite: 1,
  prix_unitaire: 0,
  date_livraison: '',
  notes: ''
})
const retourForm = ref({
  client_nom: '',
  produit_nom: '',
  quantite: 1,
  montant_total: 0,
  raison: '',
  notes: ''
})
const savingLivraison = ref(false)
const savingCommande = ref(false)
const savingRetour = ref(false)

// Modale Importer depuis l'entrepôt
const showImportModal = ref(false)
const importEntrepotId = ref(null)
const importProduits = ref([])
const importQuantites = ref({})
const loadingImportProduits = ref(false)
const importing = ref(false)

// Modale Ajouter Quantité
const showAddQuantityModal = ref(false)
const selectedProductForQuantity = ref(null)
const addQuantityValue = ref(0)
const entrepotStockAvailable = ref(0)
const loadingEntrepotStock = ref(false)
const addingQuantity = ref(false)

// Reçu de vente
const lastSaleReceipt = ref(null)
const showReceiptModal = ref(false)

const formData = ref({
  nom_point_vente: '',
  id_entrepot: null,
  adresse: '',
  ville: '',
  pays: '',
  telephone: '',
  email: '',
  responsable: '',
  actif: 1
})

const notification = ref({ show: false, type: 'success', title: '', message: '' })
const confirmation = ref({ show: false, title: '', message: '', action: null })

const statsGlobales = ref({
  ventesAujourdhui: 0,
  caJournalier: 0,
  retours: 0,
  aLivrer: 0,
  aExpedier: 0,
  commandes: 0
})

const stats = computed(() => {
  const totalPointsVente = pointsVente.value.length
  const ventesJournalieres = pointsVente.value.reduce((sum, pv) => sum + (parseInt(pv.ventes_journalieres) || 0), 0)
  const chiffreAffairesJournalier = pointsVente.value.reduce((sum, pv) => sum + (parseFloat(pv.chiffre_affaires_journalier) || 0), 0)
  const commandesEnAttente = pointsVente.value.reduce((sum, pv) => sum + (parseInt(pv.commandes_en_attente) || 0), 0)
  
  // Mettre à jour les statistiques globales pour le dashboard
  statsGlobales.value = {
    ventesAujourdhui: ventesJournalieres,
    caJournalier: chiffreAffairesJournalier,
    retours: pointsVente.value.reduce((sum, pv) => sum + (parseInt(pv.nombre_retours) || 0), 0),
    aLivrer: pointsVente.value.reduce((sum, pv) => sum + (parseInt(pv.a_livrer) || 0), 0),
    aExpedier: pointsVente.value.reduce((sum, pv) => sum + (parseInt(pv.a_expedier) || 0), 0),
    commandes: commandesEnAttente
  }
  
  return {
    totalPointsVente,
    ventesJournalieres,
    chiffreAffairesJournalier,
    commandesEnAttente
  }
})

const filteredPointsVente = computed(() => {

  let filtered = pointsVente.value

  // Filtrer selon les droits d'accès de l'utilisateur connecté, sauf pour admin/superadmin
  const user = authStore.user
  const isAdmin = user && ['admin', 'superadmin'].includes(String(user.user_role || user.role).toLowerCase());
  if (!isAdmin && user && Array.isArray(user.permissions_points_vente) && user.permissions_points_vente.length > 0) {
    filtered = filtered.filter(pv => user.permissions_points_vente.includes(pv.id_point_vente))
  }

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(pv => 
      pv.nom_point_vente?.toLowerCase().includes(query) ||
      pv.ville?.toLowerCase().includes(query) ||
      pv.responsable?.toLowerCase().includes(query) ||
      pv.nom_entrepot?.toLowerCase().includes(query)
    )
  }

  if (filterActif.value !== null) {
    filtered = filtered.filter(pv => pv.actif == filterActif.value)
  }

  return filtered
})

// formatCurrency est maintenant fourni par useCurrency() via formatPrice
// Les valeurs sont supposées être en XOF (F CFA) par défaut dans la base de données

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

const loadEntrepots = async () => {
  try {
    const response = await apiService.get('/api_entrepot.php?action=all')
    if (response && response.success) {
      entrepots.value = response.data || []
    }
  } catch (error) {
    console.error('Erreur lors du chargement des entrepôts:', error)
  }
}

const loadPointsVente = async () => {
  loading.value = true
  try {
    const response = await apiService.get('/api_point_vente.php?action=all')
    console.log('Réponse API points de vente:', response)
    if (response && response.success) {
      pointsVente.value = response.data || []
    } else {
      console.error('Erreur API:', response)
      showNotification('error', 'Erreur', response?.message || 'Erreur lors du chargement des points de vente')
    }
  } catch (error) {
    console.error('Erreur lors du chargement des points de vente:', error)
    showNotification('error', 'Erreur', error?.message || 'Erreur lors du chargement des points de vente')
  } finally {
    loading.value = false
  }
}

// Fonctions pour la vue Agent
const loadAgentPointVente = async () => {
  const user = authStore.user
  console.log('loadAgentPointVente - User:', user)
  
  if (!user || !user.id_utilisateur) {
    console.error('Utilisateur non connecté')
    return
  }
  
  try {
    // Récupérer les points de vente de l'utilisateur depuis l'API
    // L'API filtre automatiquement selon les permissions
    const pointsVenteResponse = await apiService.get('/api_point_vente.php?action=all')
    console.log('Réponse points de vente:', pointsVenteResponse)
    
    if (pointsVenteResponse && pointsVenteResponse.success && pointsVenteResponse.data && pointsVenteResponse.data.length > 0) {
      // Si ?point_vente= est dans l'URL (ex. depuis "Historique" dans Ventes), utiliser ce point s'il est dans la liste
      const idQuery = route.query.point_vente ? parseInt(route.query.point_vente) : null
      const pointVente = (idQuery && pointsVenteResponse.data.find(p => p.id_point_vente == idQuery)) || pointsVenteResponse.data[0]
      const pointVenteId = pointVente.id_point_vente
      
      console.log('Point de vente trouvé:', pointVente)
      
      // Charger les détails complets et les stats
      const [pointVenteResponse, statsResponse] = await Promise.all([
        apiService.get(`/api_point_vente.php?id_point_vente=${pointVenteId}`),
        apiService.get(`/api_point_vente.php?action=stats&id_point_vente=${pointVenteId}`)
      ])
      
      console.log('Réponse point de vente détaillé:', pointVenteResponse)
      console.log('Réponse stats:', statsResponse)
      
      if (pointVenteResponse && pointVenteResponse.success && pointVenteResponse.data) {
        currentPointVente.value = pointVenteResponse.data
        console.log('Point de vente chargé:', currentPointVente.value)
      } else {
        // Si l'API détaillée échoue, utiliser les données de base
        currentPointVente.value = pointVente
        console.log('Point de vente chargé (données de base):', currentPointVente.value)
      }
      
      if (statsResponse && statsResponse.success) {
        const data = statsResponse.data
        const ventesAujourdhui = data.ventes_journalieres?.find(v => {
          const date = new Date(v.date)
          const today = new Date()
          return date.toDateString() === today.toDateString()
        })
        
        agentStats.value = {
          caJournalier: ventesAujourdhui ? parseFloat(ventesAujourdhui.chiffre_affaires) : 0,
          ventesAujourdhui: ventesAujourdhui ? parseInt(ventesAujourdhui.nombre_ventes) : 0,
          aLivrer: parseInt(data.a_livrer || 0),
          commandes: parseInt(data.commandes_en_attente || 0)
        }
      }
    } else {
      console.error('Aucun point de vente trouvé pour cet utilisateur')
    }
  } catch (error) {
    console.error('Erreur lors du chargement du point de vente:', error)
  }
}

const loadAgentVentes = async () => {
  console.log('=== loadAgentVentes DÉBUT ===')
  console.log('currentPointVente:', currentPointVente.value)
  
  if (!currentPointVente.value) {
    console.log('Point de vente non défini, arrêt')
    return
  }
  
  loadingVentes.value = true
  try {
    // Construire l'URL avec les filtres de date
    let url = `/api_vente.php?action=all&id_point_vente=${currentPointVente.value.id_point_vente}`
    if (venteFilterDateDebut.value) {
      url += `&date_debut=${venteFilterDateDebut.value}`
    }
    if (venteFilterDateFin.value) {
      url += `&date_fin=${venteFilterDateFin.value}`
    }
    
    console.log('URL de l\'API:', url)
    
    const response = await apiService.get(url)
    console.log('Réponse API:', response)
    
    if (response && response.success) {
      // Filtrer uniquement les ventes (type_vente = 'vente' ou null)
      const allVentes = response.data || []
      console.log('Toutes les ventes reçues:', allVentes.length)
      console.log('Exemple de vente:', allVentes[0])
      
      // Filtrer les ventes : accepter toutes sauf les livraisons, commandes et retours
      const ventesFiltered = allVentes.filter(v => {
        const type = v.type_vente
        // Exclure explicitement les livraisons, commandes et retours
        if (type === 'livraison' || type === 'expedition' || type === 'commande' || type === 'retour') {
          return false
        }
        // Accepter tout le reste (vente, null, undefined, vide)
        return true
      })
      
      console.log('Ventes filtrées:', ventesFiltered.length)
      
      // Regrouper les ventes par transaction (même date_vente, même id_user, même id_point_vente)
      // Cela permet d'afficher une seule ligne par transaction même si plusieurs produits ont été vendus
      const ventesGrouped = {}
      
      ventesFiltered.forEach(vente => {
        // Créer une clé unique basée sur la date (arrondie à la seconde), l'utilisateur et le point de vente
        const dateKey = new Date(vente.date_vente).toISOString().slice(0, 19) // Format: YYYY-MM-DD HH:MM:SS
        const key = `${dateKey}_${vente.id_user}_${vente.id_point_vente}`
        
        if (!ventesGrouped[key]) {
          // Première vente de cette transaction
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
          // Ajouter ce produit à la transaction existante
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
      
      // Convertir l'objet en tableau et trier par date décroissante
      const ventesArray = Object.values(ventesGrouped).sort((a, b) => 
        new Date(b.date_vente) - new Date(a.date_vente)
      )
      
      console.log('Ventes groupées:', ventesArray.length)
      console.log('Ventes finales:', ventesArray)
      
      agentVentes.value = ventesArray
    } else {
      console.error('Réponse API non réussie:', response)
    }
  } catch (error) {
    console.error('Erreur lors du chargement des ventes:', error)
  } finally {
    loadingVentes.value = false
    console.log('=== loadAgentVentes FIN ===')
  }
}

const loadAgentLivraisons = async () => {
  if (!currentPointVente.value) return
  
  loadingLivraisons.value = true
  try {
    const response = await apiService.get(`/api_vente.php?action=all&id_point_vente=${currentPointVente.value.id_point_vente}`)
    if (response.success) {
      // Filtrer les livraisons en attente
      const allVentes = response.data || []
      agentLivraisons.value = allVentes.filter(v => 
        (v.type_vente === 'livraison' || v.type_vente === 'expedition') && 
        (v.statut === 'en_attente' || v.statut === 'en_cours')
      )
    }
  } catch (error) {
    console.error('Erreur lors du chargement des livraisons:', error)
  } finally {
    loadingLivraisons.value = false
  }
}

const loadAgentCommandes = async () => {
  if (!currentPointVente.value) return
  
  loadingCommandes.value = true
  try {
    const response = await apiService.get(`/api_vente.php?action=all&id_point_vente=${currentPointVente.value.id_point_vente}`)
    if (response.success) {
      // Filtrer les commandes en attente
      const allVentes = response.data || []
      agentCommandes.value = allVentes.filter(v => 
        v.type_vente === 'commande' && 
        (v.statut === 'en_attente' || v.statut === 'en_cours')
      )
    }
  } catch (error) {
    console.error('Erreur lors du chargement des commandes:', error)
  } finally {
    loadingCommandes.value = false
  }
}

const loadAgentRetours = async () => {
  if (!currentPointVente.value) return
  
  loadingRetours.value = true
  try {
    const response = await apiService.get(`/api_vente.php?action=all&id_point_vente=${currentPointVente.value.id_point_vente}`)
    if (response.success) {
      // Filtrer les retours
      const allVentes = response.data || []
      agentRetours.value = allVentes.filter(v => v.type_vente === 'retour')
    }
  } catch (error) {
    console.error('Erreur lors du chargement des retours:', error)
  } finally {
    loadingRetours.value = false
  }
}

const loadAgentStock = async () => {
  if (!currentPointVente.value?.id_point_vente) {
    agentStockProduits.value = []
    return
  }
  
  loadingStock.value = true
  try {
    const response = await apiService.get(`/api_produit.php?action=all&id_point_vente=${currentPointVente.value.id_point_vente}`)
    if (response && response.success && response.data) {
      let produits = Array.isArray(response.data) ? response.data : []
      produits = produits.filter(p => p.actif === 1 || p.actif === true || p.actif === '1')
      // Exclure les produits en rupture (stock = 0)
      produits = produits.filter(p => (p.quantite_stock || 0) > 0)
      // Mapper id_categorie vers categorie si nécessaire
      produits = produits.map(p => ({
        ...p,
        categorie: p.categorie || p.id_categorie || 'Non catégorisé'
      }))
      agentStockProduits.value = produits
      const cats = [...new Set(produits.map(p => p.categorie).filter(Boolean))]
      stockCategories.value = cats.sort()
      console.log('✅ [PointVente] Stock chargé:', produits.length, 'produits, catégories:', stockCategories.value)
      console.log('✅ [PointVente] Stock chargé:', produits.length, 'produits')
    } else {
      agentStockProduits.value = []
    }
  } catch (error) {
    console.error('❌ [PointVente] Erreur chargement stock:', error)
    agentStockProduits.value = []
  } finally {
    loadingStock.value = false
  }
}

const stockFilteredProducts = computed(() => {
  let filtered = agentStockProduits.value.filter(p => p.actif === 1)
  
  if (stockSearchQuery.value) {
    const query = stockSearchQuery.value.toLowerCase()
    filtered = filtered.filter(p => 
      p.nom?.toLowerCase().includes(query) ||
      p.code_produit?.toLowerCase().includes(query)
    )
  }
  
  if (stockSelectedCategory.value) {
    filtered = filtered.filter(p => p.categorie === stockSelectedCategory.value)
  }
  
  return filtered.sort((a, b) => {
    // Trier par statut (rupture en premier, puis alerte, puis normal)
    const statutOrder = { 'rupture': 0, 'alerte': 1, 'normal': 2 }
    const orderA = statutOrder[a.statut_stock] ?? 2
    const orderB = statutOrder[b.statut_stock] ?? 2
    if (orderA !== orderB) return orderA - orderB
    return a.nom.localeCompare(b.nom)
  })
})

const clearVenteFilters = () => {
  venteFilterDateDebut.value = ''
  venteFilterDateFin.value = ''
  loadAgentVentes()
}

const markAsDelivered = async (livraison) => {
  try {
    // Mettre à jour le statut de la livraison
    const response = await apiService.post(`/api_vente.php?action=update&id=${livraison.id_vente}`, { 
      statut: 'livre',
      type_vente: livraison.type_vente || 'livraison'
    })
    if (response.success) {
      showNotification('success', 'Succès', 'Livraison marquée comme effectuée')
      loadAgentLivraisons()
      loadAgentPointVente()
    } else {
      showNotification('error', 'Erreur', response.message || 'Erreur lors de la mise à jour')
    }
  } catch (error) {
    console.error('Erreur:', error)
    showNotification('error', 'Erreur', 'Erreur lors de la mise à jour')
  }
}

const processCommande = async (commande) => {
  try {
    // Traiter la commande (changer le statut)
    const response = await apiService.post(`/api_vente.php?action=update&id=${commande.id_vente}`, { 
      statut: 'traite',
      type_vente: 'commande'
    })
    if (response.success) {
      showNotification('success', 'Succès', 'Commande traitée')
      loadAgentCommandes()
      loadAgentPointVente()
    } else {
      showNotification('error', 'Erreur', response.message || 'Erreur lors du traitement')
    }
  } catch (error) {
    console.error('Erreur:', error)
    showNotification('error', 'Erreur', 'Erreur lors du traitement')
  }
}

const viewRetourDetails = (retour) => {
  // Afficher les détails du retour
  console.log('Voir détails retour:', retour)
}

// Fonctions pour les modales Agent
const openSaleModal = async () => {
  console.log('openSaleModal appelé')
  // S'assurer que le point de vente est chargé AVANT d'ouvrir la modale
  if (!currentPointVente.value) {
    console.log('Chargement du point de vente...')
    await loadAgentPointVente()
  }
  console.log('Point de vente chargé:', currentPointVente.value)
  
  showSaleModal.value = true
  saleCart.value = []
  saleSearchQuery.value = ''
  saleSelectedCategory.value = ''
  // Force refresh pour avoir les derniers produits importés
  await loadSaleProducts(true)
  console.log('Modale ouverte, produits chargés:', saleProducts.value.length)
}

const closeSaleModal = () => {
  showSaleModal.value = false
  saleCart.value = []
}

const openLivraisonModal = () => {
  showLivraisonModal.value = true
  livraisonForm.value = {
    client_nom: '',
    adresse: '',
    date_livraison: '',
    notes: ''
  }
}

const closeLivraisonModal = () => {
  showLivraisonModal.value = false
}

const openCommandeModal = () => {
  showCommandeModal.value = true
  commandeForm.value = {
    client_nom: '',
    produit_nom: '',
    quantite: 1,
    prix_unitaire: 0,
    date_livraison: '',
    notes: ''
  }
}

const closeCommandeModal = () => {
  showCommandeModal.value = false
}

const openRetourModal = () => {
  showRetourModal.value = true
  retourForm.value = {
    client_nom: '',
    produit_nom: '',
    quantite: 1,
    montant_total: 0,
    raison: '',
    notes: ''
  }
}

const closeRetourModal = () => {
  showRetourModal.value = false
}

// Modale Importer depuis l'entrepôt
const openImportModal = async () => {
  showImportModal.value = true
  importProduits.value = []
  importQuantites.value = {}
  
  await loadEntrepots()
  
  const user = authStore.user
  // Si agent avec un seul entrepôt autorisé : sélection automatique
  if (user && !isAdmin.value && user.permissions_entrepots && Array.isArray(user.permissions_entrepots) && user.permissions_entrepots.length === 1) {
    const seulEntrepotId = parseInt(user.permissions_entrepots[0])
    if (!isNaN(seulEntrepotId) && seulEntrepotId > 0) {
      // Vérifier que cet entrepôt existe dans la liste chargée
      const entrepotTrouve = entrepots.value.find(e => e.id_entrepot === seulEntrepotId)
      if (entrepotTrouve) {
        importEntrepotId.value = seulEntrepotId
        console.log('✅ [Import] Agent avec un seul entrepôt - sélection automatique:', entrepotTrouve.nom_entrepot)
        await loadImportProduits()
        return
      }
    }
  }
  
  // Sinon : utiliser l'entrepôt du point de vente ou laisser l'utilisateur choisir
  importEntrepotId.value = currentPointVente.value?.id_entrepot || null
  if (importEntrepotId.value) {
    await loadImportProduits()
  }
}

const closeImportModal = () => {
  showImportModal.value = false
  importEntrepotId.value = null
  importProduits.value = []
  importQuantites.value = {}
}

const loadImportProduits = async () => {
  if (!importEntrepotId.value) {
    importProduits.value = []
    return
  }
  loadingImportProduits.value = true
  importProduits.value = []
  importQuantites.value = {}
  try {
    const res = await apiService.get(`/api_entrepot.php?action=produits&id_entrepot=${importEntrepotId.value}`)
    if (res?.success && Array.isArray(res.data)) {
      importProduits.value = res.data
      importQuantites.value = Object.fromEntries((res.data || []).map(p => [String(p.id_produit), 0]))
    }
  } catch (e) {
    console.error('Erreur chargement produits entrepôt:', e)
    showNotification('error', 'Erreur', e?.message || 'Impossible de charger les produits de l\'entrepôt')
  } finally {
    loadingImportProduits.value = false
  }
}

// Fonctions pour ajouter des quantités
const openAddQuantityModal = async (produit) => {
  selectedProductForQuantity.value = produit
  addQuantityValue.value = 0
  entrepotStockAvailable.value = 0
  showAddQuantityModal.value = true
  
  // Charger le stock disponible dans l'entrepôt
  await loadEntrepotStockForProduct(produit.id_produit)
}

const closeAddQuantityModal = () => {
  showAddQuantityModal.value = false
  selectedProductForQuantity.value = null
  addQuantityValue.value = 0
  entrepotStockAvailable.value = 0
}

const loadEntrepotStockForProduct = async (idProduit) => {
  if (!idProduit || !currentPointVente.value) {
    entrepotStockAvailable.value = 0
    return
  }
  
  loadingEntrepotStock.value = true
  try {
    // Déterminer l'entrepôt à utiliser
    let entrepotId = null
    const user = authStore.user
    
    // Si agent avec un seul entrepôt autorisé
    if (user && !isAdmin.value && user.permissions_entrepots && Array.isArray(user.permissions_entrepots) && user.permissions_entrepots.length === 1) {
      entrepotId = parseInt(user.permissions_entrepots[0])
    } else if (currentPointVente.value.id_entrepot) {
      entrepotId = currentPointVente.value.id_entrepot
    } else {
      // Charger les entrepôts et prendre le premier disponible
      await loadEntrepots()
      if (entrepots.value.length > 0) {
        entrepotId = entrepots.value[0].id_entrepot
      }
    }
    
    if (!entrepotId) {
      console.warn('⚠️ [AddQuantity] Aucun entrepôt trouvé')
      entrepotStockAvailable.value = 0
      return
    }
    
    // Charger les produits de l'entrepôt
    console.log('🔍 [AddQuantity] Chargement stock pour produit', idProduit, 'depuis entrepôt', entrepotId)
    const res = await apiService.get(`/api_entrepot.php?action=produits&id_entrepot=${entrepotId}`)
    console.log('📦 [AddQuantity] Réponse API entrepôt:', res)
    
    if (res?.success && Array.isArray(res.data)) {
      console.log('📦 [AddQuantity] Produits reçus:', res.data.length)
      const produitEntrepot = res.data.find(p => p.id_produit === idProduit)
      if (produitEntrepot) {
        entrepotStockAvailable.value = parseInt(produitEntrepot.quantite_stock || 0, 10)
        console.log('✅ [AddQuantity] Stock entrepôt trouvé:', entrepotStockAvailable.value, 'pour produit', produitEntrepot.nom)
      } else {
        entrepotStockAvailable.value = 0
        console.warn('⚠️ [AddQuantity] Produit id=' + idProduit + ' non trouvé dans l\'entrepôt. Produits disponibles:', res.data.map(p => `${p.id_produit}(${p.nom})`).slice(0, 5))
      }
    } else {
      entrepotStockAvailable.value = 0
      console.error('❌ [AddQuantity] Réponse API invalide:', res)
    }
  } catch (e) {
    console.error('❌ [AddQuantity] Erreur chargement stock entrepôt:', e)
    entrepotStockAvailable.value = 0
    showNotification('error', 'Erreur', 'Impossible de charger le stock de l\'entrepôt')
  } finally {
    loadingEntrepotStock.value = false
  }
}

const validateAddQuantity = () => {
  if (addQuantityValue.value < 0) {
    addQuantityValue.value = 0
  }
  if (addQuantityValue.value > entrepotStockAvailable.value) {
    addQuantityValue.value = entrepotStockAvailable.value
  }
}

const processAddQuantity = async () => {
  if (!selectedProductForQuantity.value || !currentPointVente.value || addQuantityValue.value <= 0) {
    return
  }
  
  if (addQuantityValue.value > entrepotStockAvailable.value) {
    showNotification('error', 'Erreur', 'La quantité demandée dépasse le stock disponible dans l\'entrepôt')
    return
  }
  
  addingQuantity.value = true
  
  try {
    const importData = {
      id_produit: selectedProductForQuantity.value.id_produit,
      quantite: addQuantityValue.value,
      type_sortie: 'transfert',
      point_vente_destination: currentPointVente.value.id_point_vente,
      prix_unitaire: selectedProductForQuantity.value.prix_vente || selectedProductForQuantity.value.prix_achat || 0
    }
    
    console.log('📥 [AddQuantity] Transfert:', importData)
    
    const response = await apiService.post('/api_stock.php?type=sortie', importData)
    console.log('📥 [AddQuantity] Réponse API:', response)
    
    if (response && response.success) {
      showNotification('success', 'Succès', `${addQuantityValue.value} unité(s) ajoutée(s) avec succès`)
      closeAddQuantityModal()
      
      // Recharger le stock
      await new Promise(resolve => setTimeout(resolve, 300))
      await loadAgentStock()
      await loadSaleProducts(true)
    } else {
      const errorMsg = response?.message || response?.error || 'Erreur lors de l\'ajout'
      showNotification('error', 'Erreur', errorMsg)
    }
  } catch (e) {
    const errorMsg = e?.response?.data?.message || e?.response?.data?.error || e?.message || 'Erreur inconnue'
    console.error('❌ [AddQuantity] Exception:', errorMsg, e)
    showNotification('error', 'Erreur', errorMsg)
  } finally {
    addingQuantity.value = false
  }
}

const processImport = async () => {
  if (!currentPointVente.value?.id_point_vente) {
    showNotification('error', 'Erreur', 'Point de vente non défini')
    return
  }
  const q = importQuantites.value || {}
  const toImport = (importProduits.value || []).filter(p => (q[p.id_produit] || 0) > 0)
  if (toImport.length === 0) {
    showNotification('warning', 'Attention', 'Aucune quantité sélectionnée')
    return
  }
  
  console.log('📥 [Import] Début import:', toImport.length, 'produits')
  console.log('📥 [Import] Point de vente:', currentPointVente.value.id_point_vente)
  
  importing.value = true
  const errors = []
  const ok = []
  
  for (const p of toImport) {
    const quantite = Math.max(0, parseInt(q[p.id_produit], 10) || 0)
    if (quantite <= 0) continue
    
    const importData = {
      id_produit: p.id_produit,
      quantite,
      type_sortie: 'transfert',
      point_vente_destination: currentPointVente.value.id_point_vente,
      prix_unitaire: p.prix_vente || p.prix_achat || 0
    }
    
    console.log('📥 [Import] Produit:', p.nom, 'Qté:', quantite, 'Data:', importData)
    
    try {
      const response = await apiService.post('/api_stock.php?type=sortie', importData)
      console.log('📥 [Import] Réponse API pour', p.nom, ':', response)
      
      if (response && response.success) {
        ok.push({ nom: p.nom, quantite })
        console.log('✅ [Import] Succès:', p.nom, quantite)
      } else {
        const msg = response?.message || response?.error || 'Réponse API invalide'
        errors.push(`${p.nom}: ${msg}`)
        console.error('❌ [Import] Échec:', p.nom, msg)
      }
    } catch (e) {
      const errorMsg = e?.response?.data?.message || e?.response?.data?.error || e?.message || 'Erreur inconnue'
      errors.push(`${p.nom}: ${errorMsg}`)
      console.error('❌ [Import] Exception:', p.nom, errorMsg, e)
    }
  }
  
  importing.value = false
  
  console.log('📥 [Import] Résultat - OK:', ok.length, 'Erreurs:', errors.length)
  
  if (errors.length > 0 && ok.length === 0) {
    showNotification('error', 'Échec de l\'import', 'Aucun produit importé. ' + errors.join(' ; '))
  } else if (errors.length > 0) {
    showNotification('warning', 'Import partiel', `${ok.length} produit(s) importé(s). Erreurs: ${errors.join(' ; ')}`)
  }
  
  if (ok.length > 0) {
    showNotification('success', 'Import réussi', `${ok.length} produit(s) importé(s) vers le point de vente.`)
    closeImportModal()
    
    // Petit délai pour laisser l'API finaliser les mises à jour
    await new Promise(resolve => setTimeout(resolve, 500))
    
    // Recharger les produits de vente avec force refresh (pour la modale de vente si elle est ouverte)
    await loadSaleProducts(true) // forceRefresh = true pour éviter le cache
    console.log('✅ [Import] Produits de vente rechargés:', saleProducts.value.length, 'produits')
    
    // Recharger le stock si on est sur l'onglet Stock
    if (activeAgentTab.value === 'stock') {
      await loadAgentStock()
    } else {
      // Recharger quand même en arrière-plan pour que l'onglet Stock soit à jour
      loadAgentStock()
    }
    console.log('✅ [Import] Stock rechargé')
  }
}

// Fonctions pour la modale de vente
// Charge UNIQUEMENT les produits DISPONIBLES AU POINT DE VENTE (stock_produit_point_vente.quantite_disponible),
// c.-à-d. ceux importés depuis l'entrepôt. Aucun fallback vers l'entrepôt.
const loadSaleProducts = async (forceRefresh = false) => {
  loadingSaleProducts.value = true
  try {
    const pvId = currentPointVente.value?.id_point_vente
    
    // EXIGER un point de vente : sans point de vente, pas de produits vendables
    if (!pvId) {
      console.warn('⚠️ [PointVente] Aucun point de vente défini - aucun produit chargé')
      saleProducts.value = []
      saleCategories.value = []
      return
    }

    // Vider d'abord pour forcer le re-render
    if (forceRefresh) {
      saleProducts.value = []
      saleCategories.value = []
      console.log('🔄 [PointVente] Force refresh - vidage des produits')
      // Petit délai pour que Vue détecte le changement
      await new Promise(resolve => setTimeout(resolve, 50))
    }

    // UNIQUEMENT les produits du point de vente (stock_produit_point_vente)
    // Ajouter un timestamp pour éviter le cache
    const cacheBuster = forceRefresh ? `&_t=${Date.now()}` : ''
    const url = `/api_produit.php?action=all&id_point_vente=${pvId}${cacheBuster}`
    console.log('📦 [PointVente] Chargement produits du point de vente uniquement (importés): id_point_vente=', pvId, 'forceRefresh=', forceRefresh, 'url=', url)

    const response = await apiService.get(url)
    console.log('📦 [PointVente] Réponse API complète:', JSON.stringify(response, null, 2))

    if (response && response.success && response.data) {
      let allProducts = Array.isArray(response.data) ? response.data : []
      console.log('📦 [PointVente] Produits bruts reçus:', allProducts.length)
      
      // Filtrer par produits actifs
      allProducts = allProducts.filter(p => p.actif === 1 || p.actif === true || p.actif === '1')
      console.log('📦 [PointVente] Produits actifs après filtre:', allProducts.length)
      
      // Exclure les produits en rupture (stock = 0)
      allProducts = allProducts.filter(p => (p.quantite_stock || 0) > 0)
      console.log('📦 [PointVente] Produits avec stock > 0 après filtre rupture:', allProducts.length)
      
      // quantite_stock = quantite_disponible (point de vente) depuis stock_produit_point_vente
      // Forcer une nouvelle référence pour Vue en créant un nouveau tableau
      saleProducts.value = allProducts.map(p => ({ ...p }))
      const cats = [...new Set(saleProducts.value.map(p => p.categorie).filter(Boolean))]
      saleCategories.value = cats.sort()
      console.log('✅ [PointVente] Produits vendables chargés (point de vente uniquement):', saleProducts.value.length, 'produits')
      
      // Log des IDs des produits pour vérification
      if (saleProducts.value.length > 0) {
        const ids = saleProducts.value.map(p => `${p.id_produit}(${p.nom}, stock=${p.quantite_stock})`).slice(0, 5)
        console.log('📦 [PointVente] Exemples produits:', ids.join(', '), saleProducts.value.length > 5 ? '...' : '')
      } else {
        console.warn('⚠️ [PointVente] AUCUN PRODUIT RETOURNÉ - Vérifiez que les produits ont bien été importés dans stock_produit_point_vente')
      }
    } else {
      saleProducts.value = []
      saleCategories.value = []
      console.warn('⚠️ [PointVente] Aucun produit disponible dans ce point de vente. Réponse:', response)
    }
  } catch (error) {
    console.error('❌ [PointVente] Erreur chargement produits:', error)
    console.error('❌ [PointVente] Stack:', error.stack)
    saleProducts.value = []
    saleCategories.value = []
  } finally {
    loadingSaleProducts.value = false
  }
}

const saleFilteredProducts = computed(() => {
  let filtered = saleProducts.value.filter(p => p.actif === 1)
  
  if (saleSearchQuery.value) {
    const query = saleSearchQuery.value.toLowerCase()
    filtered = filtered.filter(p => 
      p.nom?.toLowerCase().includes(query) ||
      p.code_produit?.toLowerCase().includes(query)
    )
  }
  
  if (saleSelectedCategory.value) {
    filtered = filtered.filter(p => p.categorie === saleSelectedCategory.value)
  }
  
  return filtered.sort((a, b) => a.nom.localeCompare(b.nom))
})

const saleTotal = computed(() => {
  return saleCart.value.reduce((sum, item) => sum + (item.sous_total || 0), 0)
})

const hasImportSelection = computed(() => {
  const q = importQuantites.value || {}
  return (importProduits.value || []).some(p => (q[p.id_produit] || 0) > 0)
})

const addToSaleCart = (product) => {
  if (product.quantite_stock === 0) return
  
  const existingIndex = saleCart.value.findIndex(item => item.id_produit === product.id_produit)
  
  if (existingIndex >= 0) {
    increaseSaleQuantity(existingIndex)
  } else {
    const prixUnitaire = product.prix_vente || 0
    const quantite = 1
    saleCart.value.push({
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

const removeFromSaleCart = (index) => {
  saleCart.value.splice(index, 1)
}

const increaseSaleQuantity = (index) => {
  const item = saleCart.value[index]
  if (item.quantite < item.quantite_stock) {
    item.quantite++
    item.sous_total = item.prix_unitaire * item.quantite
  }
}

const decreaseSaleQuantity = (index) => {
  const item = saleCart.value[index]
  if (item.quantite > 1) {
    item.quantite--
    item.sous_total = item.prix_unitaire * item.quantite
  } else {
    removeFromSaleCart(index)
  }
}

const clearSaleCart = () => {
  saleCart.value = []
}

const processSaleFromModal = async () => {
  const authStore = useAuthStore();
  console.log('=== processSaleFromModal DÉBUT ===')
  console.log('Panier:', saleCart.value)
  console.log('Point de vente actuel:', currentPointVente.value)
  
  if (saleCart.value.length === 0) {
    console.log('Panier vide, arrêt')
    alert('Veuillez ajouter des produits au panier')
    return
  }
  
  // Si le point de vente n'est pas chargé, le charger maintenant
  if (!currentPointVente.value) {
    console.log('Point de vente non chargé, chargement...')
    await loadAgentPointVente()
    
    // Si toujours null après chargement, essayer de récupérer depuis l'API
    if (!currentPointVente.value) {
      try {
        const pointsVenteResponse = await apiService.get('/api_point_vente.php?action=all')
        if (pointsVenteResponse && pointsVenteResponse.success && pointsVenteResponse.data && pointsVenteResponse.data.length > 0) {
          currentPointVente.value = pointsVenteResponse.data[0]
          console.log('Point de vente récupéré depuis API:', currentPointVente.value)
        } else {
          alert('Aucun point de vente assigné à votre compte. Contactez l\'administrateur.')
          return
        }
      } catch (error) {
        console.error('Erreur lors de la récupération du point de vente:', error)
        alert('Erreur lors du chargement du point de vente')
        return
      }
    }
  }
  
  console.log('Point de vente final:', currentPointVente.value)
  console.log('Début de la vente...')
  
  try {
    const produits = saleCart.value.map(item => ({
      id_produit: item.id_produit,
      quantite: item.quantite,
      prix_unitaire: item.prix_unitaire
    }))
    
    const saleData = {
      id_point_vente: currentPointVente.value.id_point_vente,
      produits: produits,
      remise: 0,
      notes: null
    }
    
    console.log('Envoi des données:', saleData)
    const response = await apiService.post('/api_vente.php', saleData)
    console.log('Réponse reçue:', response)
    
    if (response && response.success) {
      console.log('Vente réussie!')
      // Récupérer l'ID de la vente depuis la réponse
      const idVente = response.data?.id_vente || response.data?.ventes?.[0]?.id_vente || Date.now()

      // Calculer le total avant de vider le panier
      const totalVente = saleCart.value.reduce((sum, item) => sum + (item.sous_total || 0), 0)
      const totalItems = saleCart.value.reduce((sum, item) => sum + (item.quantite || 0), 0)

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
        point_vente: currentPointVente.value.nom_point_vente,
        produits: saleCart.value.map(item => ({
          nom: item.nom,
          code: item.code_produit,
          quantite: item.quantite,
          prix_unitaire: item.prix_unitaire,
          sous_total: item.sous_total
        })),
        nombre_articles: totalItems,
        sous_total: totalVente,
        remise: 0,
        total: totalVente
      }


      // === GÉNÉRATION ET UPLOAD DU REÇU PDF ===
      let urlPieceJointe = ''
      try {
        // Générer le PDF du reçu en mémoire
        const doc = new jsPDF()
        doc.setFontSize(16)
        doc.text('Reçu de Vente', 105, 20, { align: 'center' })
        doc.setFontSize(12)
        doc.text(`Point de Vente : ${currentPointVente.value.nom_point_vente}`, 20, 35)
        doc.text(`Date : ${new Date().toLocaleString('fr-FR')}`, 20, 43)
        doc.text(`N° Vente : ${idVente}`, 20, 51)
        let y = 60
        doc.text('Produits :', 20, y)
        y += 7
        saleCart.value.forEach(item => {
          doc.text(`- ${item.nom} (x${item.quantite}) : ${formatCurrency(item.sous_total)}`, 25, y)
          y += 7
        })
        y += 5
        doc.text(`Total : ${formatCurrency(totalVente)}`, 20, y)
        y += 10
        doc.text('Merci de votre visite !', 20, y)

        // Convertir le PDF en blob
        const pdfBlob = doc.output('blob')
        // Créer un objet File pour Cloudinary
        const pdfFile = new File([pdfBlob], `recu_vente_${idVente}.pdf`, { type: 'application/pdf' })
        // Uploader sur Cloudinary
        urlPieceJointe = await uploadToCloudinary(pdfFile)
      } catch (err) {
        console.error('Erreur lors de la génération ou upload du reçu PDF:', err)
        urlPieceJointe = ''
      }

      // === AJOUT AUTOMATIQUE ÉCRITURE COMPTABLE ===
      try {
        const now = new Date();
        // Récupérer les infos de paiement et commentaire si disponibles (à adapter selon votre logique)
        const moyenPaiement = saleCart.value[0]?.moyen_paiement || 'espèces';
        const commentaire = saleCart.value[0]?.commentaire || '';
        const ecriture = {
          date_ecriture: now.toISOString().slice(0, 19).replace('T', ' '),
          type_ecriture: 'vente',
          montant: totalVente,
          debit: 0,
          credit: totalVente,
          user: authStore.user?.nom || authStore.user?.email || 'utilisateur inconnu',
          categorie: 'Vente',
          moyen_paiement: moyenPaiement,
          statut: 'valide',
          reference: idVente,
          commentaire: commentaire,
          details: JSON.stringify(saleCart.value.map(item => ({ nom: item.nom, quantite: item.quantite, prix_unitaire: item.prix_unitaire }))),
          id_entreprise: authStore.user?.id_entreprise || null,
          id_utilisateur: authStore.user?.id_utilisateur || authStore.user?.id || null,
          id_point_vente: currentPointVente.value.id_point_vente,
          nom_client: saleCart.value[0]?.client_nom || null,
          piece_jointe: urlPieceJointe
        }
        await apiCompta.addEcriture(ecriture)
        console.log('Écriture comptable créée avec succès')
      } catch (err) {
        console.error('Erreur lors de la création de l\'écriture comptable:', err)
      }

      // === FIN ÉCRITURE COMPTABLE ===
      // Déclencher le rechargement de la comptabilité si la page est ouverte
      if (typeof window !== 'undefined' && typeof window.reloadComptabilite === 'function') {
        window.reloadComptabilite();
      }

      // Vider le panier automatiquement après vente réussie (sans confirmation)
      saleCart.value = []

      // Fermer la modale de vente
      showSaleModal.value = false

      // Afficher le reçu (pas d'alerte de succès, le reçu suffit)
      showReceiptModal.value = true

      // Recharger les produits pour mettre à jour les stocks
      await loadSaleProducts()

      // Recharger les autres données en arrière-plan
      await loadAgentVentes()
      await loadAgentPointVente()
      // Recharger le stock si on est sur l'onglet Stock
      if (activeAgentTab.value === 'stock') {
        await loadAgentStock()
      }

      // Le reçu est automatiquement enregistré côté serveur dans api_vente.php
      console.log('=== processSaleFromModal SUCCÈS ===')
    } else {
      console.error('Erreur API:', response)
      alert('Erreur: ' + (response?.message || 'Erreur inconnue'))
    }
  } catch (error) {
    console.error('Erreur catch:', error)
    alert('Erreur: ' + (error?.message || 'Erreur lors de la vente'))
  }
  console.log('=== processSaleFromModal FIN ===')
}

const closeReceipt = () => {
  showReceiptModal.value = false
  lastSaleReceipt.value = null
}

const printReceipt = () => {
  if (!lastSaleReceipt.value) return
  
  // Créer une nouvelle fenêtre pour l'impression du reçu uniquement
  const printWindow = window.open('', '_blank')
  if (!printWindow) {
    showNotification('error', 'Erreur', 'Veuillez autoriser les popups pour imprimer')
    return
  }
  
  printWindow.document.write(`
    <!DOCTYPE html>
    <html>
    <head>
      <title>Reçu de Vente</title>
      <style>
        body {
          font-family: Arial, sans-serif;
          padding: 20px;
          max-width: 400px;
          margin: 0 auto;
        }
        .receipt-header {
          text-align: center;
          border-bottom: 2px solid #000;
          padding-bottom: 10px;
          margin-bottom: 20px;
        }
        .receipt-info {
          margin-bottom: 15px;
        }
        .receipt-items {
          border-top: 1px solid #ccc;
          border-bottom: 1px solid #ccc;
          padding: 10px 0;
          margin: 15px 0;
        }
        .receipt-item {
          display: flex;
          justify-content: space-between;
          margin-bottom: 8px;
        }
        .receipt-total {
          text-align: right;
          font-weight: bold;
          font-size: 1.2em;
          margin-top: 15px;
        }
        @media print {
          body { margin: 0; }
        }
      </style>
    </head>
    <body>
      <div class="receipt-header">
        <h2>PROSTOCK</h2>
        <p>Reçu de Vente</p>
      </div>
      <div class="receipt-info">
        <p><strong>Point de Vente:</strong> ${lastSaleReceipt.value.point_vente}</p>
        <p><strong>Date:</strong> ${lastSaleReceipt.value.date}</p>
        <p><strong>N° Vente:</strong> ${lastSaleReceipt.value.id_vente}</p>
      </div>
      <div class="receipt-items">
        ${lastSaleReceipt.value.produits.map(p => `
          <div class="receipt-item">
            <span>${p.nom} (x${p.quantite})</span>
            <span>${formatCurrency(p.sous_total)}</span>
          </div>
        `).join('')}
      </div>
      <div class="receipt-total">
        <p>Total: ${formatCurrency(lastSaleReceipt.value.total)}</p>
      </div>
      <div style="text-align: center; margin-top: 20px; font-size: 0.9em; color: #666;">
        <p>Merci de votre visite !</p>
      </div>
    </body>
    </html>
  `)
  
  printWindow.document.close()
  setTimeout(() => {
    printWindow.print()
  }, 250)
}

// Fonctions pour enregistrer livraison, commande, retour
const saveLivraison = async () => {
  if (!livraisonForm.value.client_nom || !livraisonForm.value.adresse) {
    showNotification('error', 'Erreur', 'Veuillez remplir tous les champs obligatoires')
    return
  }
  
  if (!currentPointVente.value) return
  
  savingLivraison.value = true
  try {
    // Créer une entrée de type livraison dans stock_vente
    const livraisonData = {
      id_point_vente: currentPointVente.value.id_point_vente,
      type_vente: 'livraison',
      statut: 'en_attente',
      notes: `Client: ${livraisonForm.value.client_nom}\nAdresse: ${livraisonForm.value.adresse}\nDate prévue: ${livraisonForm.value.date_livraison || 'Non spécifiée'}\n${livraisonForm.value.notes || ''}`
    }
    
    // Note: L'API actuelle nécessite des produits, donc on crée une entrée minimale
    // Vous devrez peut-être adapter l'API pour supporter les livraisons sans produits
    const response = await apiService.post('/api_vente.php', {
      id_point_vente: currentPointVente.value.id_point_vente,
      produits: [], // Vide pour l'instant
      notes: livraisonData.notes
    })
    
    if (response.success) {
      showNotification('success', 'Succès', 'Livraison enregistrée')
      closeLivraisonModal()
      loadAgentLivraisons()
      loadAgentPointVente()
    } else {
      showNotification('error', 'Erreur', response.message || 'Erreur lors de l\'enregistrement')
    }
  } catch (error) {
    console.error('Erreur:', error)
    showNotification('error', 'Erreur', 'Erreur lors de l\'enregistrement')
  } finally {
    savingLivraison.value = false
  }
}

const saveCommande = async () => {
  if (!commandeForm.value.client_nom || !commandeForm.value.produit_nom || !commandeForm.value.quantite || !commandeForm.value.prix_unitaire) {
    showNotification('error', 'Erreur', 'Veuillez remplir tous les champs obligatoires')
    return
  }
  
  if (!currentPointVente.value) return
  
  savingCommande.value = true
  try {
    const commandeData = {
      id_point_vente: currentPointVente.value.id_point_vente,
      type_vente: 'commande',
      statut: 'en_attente',
      notes: `Client: ${commandeForm.value.client_nom}\nProduit: ${commandeForm.value.produit_nom}\nQuantité: ${commandeForm.value.quantite}\nDate prévue: ${commandeForm.value.date_livraison || 'Non spécifiée'}\n${commandeForm.value.notes || ''}`
    }
    
    // Note: L'API actuelle nécessite des produits, donc on crée une entrée minimale
    const response = await apiService.post('/api_vente.php', {
      id_point_vente: currentPointVente.value.id_point_vente,
      produits: [], // Vide pour l'instant
      notes: commandeData.notes
    })
    
    if (response.success) {
      showNotification('success', 'Succès', 'Commande enregistrée')
      closeCommandeModal()
      loadAgentCommandes()
      loadAgentPointVente()
    } else {
      showNotification('error', 'Erreur', response.message || 'Erreur lors de l\'enregistrement')
    }
  } catch (error) {
    console.error('Erreur:', error)
    showNotification('error', 'Erreur', 'Erreur lors de l\'enregistrement')
  } finally {
    savingCommande.value = false
  }
}

const saveRetour = async () => {
  if (!retourForm.value.client_nom || !retourForm.value.produit_nom || !retourForm.value.quantite || !retourForm.value.montant_total || !retourForm.value.raison) {
    showNotification('error', 'Erreur', 'Veuillez remplir tous les champs obligatoires')
    return
  }
  
  if (!currentPointVente.value) return
  
  savingRetour.value = true
  try {
    const retourData = {
      id_point_vente: currentPointVente.value.id_point_vente,
      type_vente: 'retour',
      statut: 'traite',
      notes: `Client: ${retourForm.value.client_nom}\nProduit: ${retourForm.value.produit_nom}\nQuantité: ${retourForm.value.quantite}\nRaison: ${retourForm.value.raison}\n${retourForm.value.notes || ''}`
    }
    
    // Note: L'API actuelle nécessite des produits, donc on crée une entrée minimale
    const response = await apiService.post('/api_vente.php', {
      id_point_vente: currentPointVente.value.id_point_vente,
      produits: [], // Vide pour l'instant
      notes: retourData.notes
    })
    
    if (response.success) {
      showNotification('success', 'Succès', 'Retour enregistré')
      closeRetourModal()
      loadAgentRetours()
      loadAgentPointVente()
    } else {
      showNotification('error', 'Erreur', response.message || 'Erreur lors de l\'enregistrement')
    }
  } catch (error) {
    console.error('Erreur:', error)
    showNotification('error', 'Erreur', 'Erreur lors de l\'enregistrement')
  } finally {
    savingRetour.value = false
  }
}

const openCreateModal = () => {
  isEditMode.value = false
  formData.value = {
    nom_point_vente: '',
    id_entrepot: null,
    adresse: '',
    ville: '',
    pays: '',
    telephone: '',
    email: '',
    responsable: '',
    actif: 1
  }
  showModal.value = true
}

const openEditModal = (pointVente) => {
  isEditMode.value = true
  formData.value = {
    id_point_vente: pointVente.id_point_vente,
    nom_point_vente: pointVente.nom_point_vente,
    id_entrepot: pointVente.id_entrepot || null,
    adresse: pointVente.adresse || '',
    ville: pointVente.ville || '',
    pays: pointVente.pays || '',
    telephone: pointVente.telephone || '',
    email: pointVente.email || '',
    responsable: pointVente.responsable || '',
    actif: pointVente.actif ? 1 : 0
  }
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  formData.value = {
    nom_point_vente: '',
    id_entrepot: null,
    adresse: '',
    ville: '',
    pays: '',
    telephone: '',
    email: '',
    responsable: '',
    actif: 1
  }
}

const savePointVente = async () => {
  if (!formData.value.nom_point_vente?.trim()) {
    showNotification('error', 'Erreur de validation', 'Le nom du point de vente est requis')
    return
  }

  saving.value = true
  try {
    let response
    if (isEditMode.value) {
      response = await apiService.put('/api_point_vente.php', formData.value)
      logJournal({ user: getJournalUser(), action: 'Modifier Point de Vente', details: `Point de vente ${formData.value.nom_point_vente} modifié` })
    } else {
      response = await apiService.post('/api_point_vente.php', formData.value)
      logJournal({ user: getJournalUser(), action: 'Ajouter Point de Vente', details: `Point de vente ${formData.value.nom_point_vente} ajouté` })
    }

    if (response.success) {
      showNotification('success', 'Succès', isEditMode.value ? 'Point de vente mis à jour' : 'Point de vente créé')
      closeModal()
      await loadPointsVente()
    } else {
      showNotification('error', 'Erreur', response.message || 'Erreur lors de l\'enregistrement')
    }
  } catch (error) {
    showNotification('error', 'Erreur', 'Erreur lors de l\'enregistrement')
  } finally {
    saving.value = false
  }
}

const viewPointVente = (pointVente) => {
  selectedPointVente.value = pointVente
  showDetailsModal.value = true
}

const goToPointVenteDashboard = (pointVente) => {
  // Naviguer vers le dashboard avec l'ID du point de vente
  router.push(`/dashboard?point_vente=${pointVente.id_point_vente}`)
}

const closeDetailsModal = () => {
  showDetailsModal.value = false
  selectedPointVente.value = null
}

const openRapportModal = async (pointVente) => {
  rapportPointVente.value = pointVente
  showRapportModal.value = true
  loadingRapport.value = true
  
  try {
    const response = await apiService.get(`/api_point_vente.php?action=stats&id_point_vente=${pointVente.id_point_vente}`)
    if (response.success) {
      rapportData.value = response.data || {
        ventes_journalieres: [],
        retours: 0,
        a_livrer: 0,
        a_expedier: 0,
        commandes_en_attente: 0
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
  rapportPointVente.value = null
  rapportData.value = {
    ventes_journalieres: [],
    retours: 0,
    a_livrer: 0,
    a_expedier: 0,
    commandes_en_attente: 0
  }
}

const confirmDelete = (pointVente) => {
  confirmation.value = {
    show: true,
    title: 'Supprimer le point de vente',
    message: `Êtes-vous sûr de vouloir supprimer le point de vente "${pointVente.nom_point_vente}" ?`,
    action: async () => {
      try {
        const response = await apiService.delete(`/api_point_vente.php?id_point_vente=${pointVente.id_point_vente}`)
        logJournal({ user: getJournalUser(), action: 'Supprimer Point de Vente', details: `Point de vente ${pointVente.nom_point_vente} supprimé` })
        if (response.success) {
          showNotification('success', 'Succès', 'Point de vente supprimé')
          await loadPointsVente()
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

// Quand un admin arrive avec ?point_vente= (sans remontage, ex. depuis Historique dans Ventes)
watch(() => route.query.point_vente, async (id) => {
  if (!isAdmin.value || !id) return
  try {
    const r = await apiService.get(`/api_point_vente.php?id_point_vente=${id}`)
    if (r && r.success && r.data) {
      currentPointVente.value = r.data
    } else {
      currentPointVente.value = { id_point_vente: parseInt(id), nom_point_vente: 'Point de vente' }
    }
    loadAgentVentes()
  } catch {
    currentPointVente.value = { id_point_vente: parseInt(id), nom_point_vente: 'Point de vente' }
    loadAgentVentes()
  }
})

// Watcher pour recharger les données quand on change d'onglet (agent ou admin en mode historique)
watch(activeAgentTab, (newTab) => {
  if (newTab === 'ventes' && currentPointVente.value) loadAgentVentes()
  else if (newTab === 'stock' && currentPointVente.value) loadAgentStock()
  else if (newTab === 'livraisons' && currentPointVente.value) loadAgentLivraisons()
  else if (newTab === 'commandes' && currentPointVente.value) loadAgentCommandes()
  else if (newTab === 'retours' && currentPointVente.value) loadAgentRetours()
})

// Recharger les produits de vente quand la modale s'ouvre (avec force refresh)
watch(showSaleModal, async (isOpen) => {
  if (isOpen && currentPointVente.value) {
    console.log('🔄 [PointVente] Modale de vente ouverte - rechargement des produits (force refresh)')
    await loadSaleProducts(true) // Force refresh pour avoir les derniers produits importés
  }
})

onMounted(async () => {
  if (isAdmin.value && route.query.point_vente) {
    // Admin : afficher l'historique du point via ?point_vente=ID (depuis "Historique" dans Ventes)
    try {
      const r = await apiService.get(`/api_point_vente.php?id_point_vente=${route.query.point_vente}`)
      if (r && r.success && r.data) {
        currentPointVente.value = r.data
      } else {
        currentPointVente.value = { id_point_vente: parseInt(route.query.point_vente), nom_point_vente: 'Point de vente' }
      }
    } catch {
      currentPointVente.value = { id_point_vente: parseInt(route.query.point_vente), nom_point_vente: 'Point de vente' }
    }
    loadAgentVentes()
  } else if (isAdmin.value) {
    loadEntrepots()
    loadPointsVente()
  } else {
    // Pour les agents, charger leur point de vente et les données
    console.log('onMounted - Agent, chargement du point de vente...')
    await loadAgentPointVente()
    console.log('onMounted - Point de vente après chargement:', currentPointVente.value)
    if (currentPointVente.value) {
      // Charger les données selon l'onglet actif
      if (activeAgentTab.value === 'ventes') {
        loadAgentVentes()
      } else if (activeAgentTab.value === 'stock') {
        loadAgentStock()
      } else if (activeAgentTab.value === 'livraisons') {
        loadAgentLivraisons()
      } else if (activeAgentTab.value === 'commandes') {
        loadAgentCommandes()
      } else if (activeAgentTab.value === 'retours') {
        loadAgentRetours()
      }
    }
  }
})

// Recharger les données quand on revient sur la page selon l'onglet actif
onActivated(() => {
  if (!currentPointVente.value) return
  if (activeAgentTab.value === 'ventes') {
    loadAgentVentes()
  } else if (activeAgentTab.value === 'stock') {
    loadAgentStock()
  } else if (activeAgentTab.value === 'livraisons') {
    loadAgentLivraisons()
  } else if (activeAgentTab.value === 'commandes') {
    loadAgentCommandes()
  } else if (activeAgentTab.value === 'retours') {
    loadAgentRetours()
  }
})
</script>

<style scoped>
/* Styles similaires à Entrepot.vue */
.point-vente-page {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  width: 100%;
}

.products-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.dashboard-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1a5f4a;
  margin: 0;
  letter-spacing: 0.01em;
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
  gap: 1.5rem;
  margin-bottom: 1rem;
  flex-wrap: wrap;
}

.products-filters {
  display: flex;
  gap: 1rem;
  margin-bottom: 0.5rem;
  align-items: center;
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

.stock-badge.alerte {
  background: #fef3c7;
  color: #92400e;
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

/* Styles spécifiques pour les modales de la page PointVente */
/* Les styles de base (.modal-overlay, .modal-content, etc.) sont définis dans style.css */

.details-modal, .rapport-modal {
  max-width: 900px;
}

.form-group {
  margin-bottom: 1.25rem;
}

.form-group label {
  display: block;
  font-size: 0.875rem;
  font-weight: 600;
  color: #374151;
  margin-bottom: 0.5rem;
}

.form-group input,
.form-group textarea,
.form-group select {
  width: 100%;
  padding: 0.75rem;
  border: 1.5px solid #10b981;
  border-radius: 8px;
  font-size: 1rem;
  font-family: inherit;
  transition: border-color 0.2s;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
  outline: none;
  border-color: #059669;
}

.form-hint {
  display: block;
  font-size: 0.75rem;
  color: #6b7280;
  margin-top: 0.25rem;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding: 1.5rem;
  border-top: 1px solid #e5e7eb;
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

.details-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1rem;
}

.detail-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1.25rem;
  background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
  border-radius: 12px;
  border: 1px solid #e5e7eb;
  transition: all 0.2s;
}

.detail-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  border-color: #1a5f4a;
}

.detail-icon {
  font-size: 2rem;
  flex-shrink: 0;
}

.detail-content {
  flex: 1;
}

.detail-label {
  font-size: 0.75rem;
  font-weight: 600;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: 0.25rem;
}

.detail-value {
  font-size: 1rem;
  font-weight: 600;
  color: #1f2937;
}

/* Styles pour le rapport */
.rapport-content {
  padding: 1.5rem;
}

.rapport-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
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

.rapport-ventes-journalieres {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  max-height: 300px;
  overflow-y: auto;
}

.rapport-vente-item {
  padding: 1rem;
  background: #f9fafb;
  border-radius: 10px;
  border-left: 4px solid #10b981;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.vente-date {
  font-weight: 600;
  color: #1f2937;
}

.vente-info {
  display: flex;
  gap: 1rem;
  align-items: center;
  font-size: 0.875rem;
  color: #6b7280;
}

.vente-info strong {
  color: #1a5f4a;
  font-size: 1rem;
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
  border-radius: 16px;
  padding: 2rem;
  min-width: 320px;
  max-width: 90vw;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.18);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
}

.notification-modal.success {
  border-top: 4px solid #10b981;
}

.notification-modal.error {
  border-top: 4px solid #ef4444;
}

.notification-title {
  font-size: 1.2rem;
  font-weight: 700;
  color: #1f2937;
}

.notification-message {
  text-align: center;
  color: #6b7280;
}

.notification-close {
  background: #1a5f4a;
  color: white;
  border: none;
  padding: 0.5rem 1.5rem;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
}

/* Dashboard des Ventes */
.ventes-dashboard {
  background: #ffffff;
  border-radius: 16px;
  padding: 1.5rem;
  margin-bottom: 1.5rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.ventes-dashboard-header {
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid #e5e7eb;
}

.ventes-dashboard-header h3 {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 700;
  color: #1a5f4a;
}

.ventes-metrics-grid {
  display: grid;
  grid-template-columns: repeat(6, 1fr);
  gap: 1rem;
}

.vente-metric-card {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  padding: 1rem;
  background: #f9fafb;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
}

.vente-metric-label {
  font-size: 0.875rem;
  font-weight: 600;
  color: #6b7280;
  text-align: center;
  line-height: 1.3;
}

.vente-metric-value {
  text-align: center;
  display: flex;
  justify-content: center;
  align-items: center;
}

.vente-value-badge {
  display: inline-block;
  padding: 0.5rem 1rem;
  background: #d1fae5;
  color: #065f46;
  border-radius: 20px;
  font-size: 1.25rem;
  font-weight: 700;
  min-width: 60px;
}

.vente-value-text {
  font-size: 1rem;
  font-weight: 600;
  color: #374151;
}

@media (max-width: 1200px) {
  .ventes-metrics-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}

@media (max-width: 768px) {
  .ventes-metrics-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 480px) {
  .ventes-metrics-grid {
    grid-template-columns: 1fr;
  }
}

/* Styles pour la vue Agent */
.agent-point-vente-page {
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

.btn-back-to-list {
  align-self: flex-start;
  background: none;
  border: 1px solid #10b981;
  color: #1a5f4a;
  padding: 0.5rem 1rem;
  border-radius: 8px;
  cursor: pointer;
  font-size: 0.9rem;
  font-weight: 500;
}

.btn-back-to-list:hover {
  background: #f0fdf4;
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

.panel-header-actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

/* Modale Importer depuis l'entrepôt */
.import-entrepot-modal .modal-body {
  max-height: 70vh;
  overflow-y: auto;
}

.import-loading, .import-empty {
  padding: 1.5rem;
  text-align: center;
  color: #6b7280;
}

.import-products-section {
  margin-top: 1rem;
}

.import-products-section h4 {
  margin: 0 0 0.75rem 0;
  font-size: 1rem;
  color: #374151;
}

.import-products-table-wrap {
  max-height: 280px;
  overflow: auto;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
}

.import-products-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.9rem;
}

.import-products-table th {
  background: #f3f4f6;
  padding: 0.6rem 0.75rem;
  text-align: left;
  font-weight: 600;
  color: #374151;
}

.import-products-table td {
  padding: 0.5rem 0.75rem;
  border-bottom: 1px solid #e5e7eb;
}

.import-products-table tr.out-of-stock {
  background: #fef2f2;
  color: #9ca3af;
}

.import-qty-input {
  width: 5rem;
  padding: 0.35rem 0.5rem;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 0.9rem;
}

.import-hint {
  margin: 0.75rem 0 0;
  font-size: 0.85rem;
  color: #6b7280;
}

.import-progress {
  margin-top: 1rem;
  padding: 1rem;
  background: #eff6ff;
  border-radius: 8px;
  text-align: center;
  border: 1px solid #bfdbfe;
}

.progress-spinner {
  font-size: 2rem;
  margin-bottom: 0.5rem;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.import-progress p {
  margin: 0;
  color: #1e40af;
  font-weight: 500;
}

.selected-entrepot-display {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.entrepot-badge {
  display: inline-block;
  padding: 0.75rem 1rem;
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  border-radius: 8px;
  color: #1e40af;
  font-weight: 600;
  font-size: 1rem;
}

/* Styles pour la modale de détails de vente */
.vente-details-info {
  margin-bottom: 1.5rem;
}

.info-row {
  display: flex;
  justify-content: space-between;
  padding: 0.75rem 0;
  border-bottom: 1px solid #e5e7eb;
}

.info-row:last-child {
  border-bottom: none;
}

.info-label {
  font-weight: 600;
  color: #6b7280;
}

.info-value {
  color: #111827;
  font-weight: 500;
}

.vente-details-products {
  margin-top: 1.5rem;
}

.vente-details-products h4 {
  margin-bottom: 1rem;
  color: #111827;
  font-size: 1.1rem;
}

.products-list {
  max-height: 300px;
  overflow-y: auto;
}

.product-item {
  padding: 1rem;
  background: #f9fafb;
  border-radius: 8px;
  margin-bottom: 0.75rem;
}

.product-name {
  font-weight: 600;
  color: #111827;
  margin-bottom: 0.5rem;
}

.product-details {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  font-size: 0.9rem;
  color: #6b7280;
}

.product-total {
  font-weight: 600;
  color: #111827;
  margin-left: auto;
}

.vente-details-total {
  margin-top: 1.5rem;
  padding-top: 1rem;
  border-top: 2px solid #e5e7eb;
}

.total-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.total-label {
  font-size: 1.1rem;
  font-weight: 600;
  color: #111827;
}

.total-value {
  font-size: 1.3rem;
  font-weight: 700;
  color: #059669;
}

/* Filtres de recherche pour les ventes */
.ventes-filters {
  display: flex;
  gap: 1rem;
  margin-bottom: 1.5rem;
  padding: 1rem;
  background: white;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  align-items: flex-end;
  flex-wrap: wrap;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.filter-group label {
  font-size: 0.875rem;
  font-weight: 500;
  color: #374151;
}

.filter-input {
  padding: 0.5rem;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 0.875rem;
  min-width: 150px;
}

.filter-input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Tableau des ventes */
.ventes-table-container {
  background: white;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  overflow: hidden;
}

.ventes-table {
  width: 100%;
  border-collapse: collapse;
}

.ventes-table thead {
  background: #f9fafb;
  border-bottom: 2px solid #e5e7eb;
}

.ventes-table th {
  padding: 1rem;
  text-align: left;
  font-weight: 600;
  font-size: 0.875rem;
  color: #374151;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.ventes-table tbody tr {
  border-bottom: 1px solid #e5e7eb;
  transition: background-color 0.2s;
}

.ventes-table tbody tr:hover {
  background-color: #f9fafb;
}

.ventes-table tbody tr:last-child {
  border-bottom: none;
}

.ventes-table td {
  padding: 1rem;
  font-size: 0.875rem;
  color: #111827;
}

.montant-cell {
  font-weight: 600;
  color: #059669;
}

/* Styles pour la section Stock */
.stock-filters {
  display: flex;
  gap: 1rem;
  align-items: center;
  margin-bottom: 1rem;
}

.stock-filters .search-box {
  flex: 1;
  max-width: 400px;
}

.stock-filters .search-input {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 0.9rem;
}

.stock-filters .filter-select {
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 0.9rem;
  background: white;
  min-width: 200px;
}

.stock-table-container {
  background: white;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  overflow: hidden;
}

.stock-table {
  width: 100%;
  border-collapse: collapse;
}

.stock-table thead {
  background: #f9fafb;
  border-bottom: 2px solid #e5e7eb;
}

.stock-table th {
  padding: 1rem;
  text-align: left;
  font-weight: 600;
  color: #374151;
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.stock-table tbody tr {
  border-bottom: 1px solid #e5e7eb;
  transition: background-color 0.2s;
}

.stock-table tbody tr:hover {
  background-color: #f9fafb;
}

.stock-table tbody tr.out-of-stock-row {
  background-color: #fef2f2;
  opacity: 0.7;
}

.stock-table tbody tr:last-child {
  border-bottom: none;
}

.stock-table td {
  padding: 1rem;
  font-size: 0.875rem;
  color: #111827;
}

.btn-add-quantity {
  background: #10b981;
  color: white;
  border: none;
  border-radius: 6px;
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  white-space: nowrap;
}

.btn-add-quantity:hover {
  background: #059669;
  transform: translateY(-1px);
  box-shadow: 0 2px 4px rgba(16, 185, 129, 0.3);
}

.btn-add-quantity:active {
  transform: translateY(0);
}

/* Styles pour la modale Ajouter Quantité */
.product-info-section {
  margin-bottom: 1.5rem;
  padding: 1rem;
  background: #f9fafb;
  border-radius: 8px;
}

.product-info-section h4 {
  margin: 0 0 0.5rem 0;
  font-size: 1.25rem;
  color: #111827;
}

.product-code-hint {
  margin: 0 0 1rem 0;
  color: #6b7280;
  font-size: 0.9rem;
}

.stock-info-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
  margin-top: 1rem;
}

.stock-info-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.stock-label {
  font-size: 0.875rem;
  color: #6b7280;
  font-weight: 500;
}

.stock-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: #111827;
}

.stock-value.current {
  color: #1a5f4a;
}

.stock-value.available {
  color: #059669;
}

.stock-value.available.low-stock {
  color: #dc2626;
}

.quantity-preview {
  margin-top: 1.5rem;
  padding: 1rem;
  background: #f0fdf4;
  border-radius: 8px;
  border: 1px solid #86efac;
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
  border-top: 2px solid #86efac;
  font-weight: 700;
  font-size: 1.1rem;
}

.preview-row .highlight {
  color: #059669;
  font-weight: 600;
}

.preview-row.total .highlight {
  color: #1a5f4a;
  font-size: 1.3rem;
}

.form-hint {
  margin-top: 0.5rem;
  font-size: 0.875rem;
  color: #6b7280;
}

.form-hint.error {
  color: #dc2626;
  font-weight: 500;
}

.form-hint strong {
  color: #059669;
  font-weight: 700;
}

.produit-cell {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.produit-image-small {
  width: 40px;
  height: 40px;
  border-radius: 6px;
  object-fit: cover;
  border: 1px solid #e5e7eb;
}

.produit-image-small img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 6px;
}

.produit-icon-small {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f3f4f6;
  border-radius: 6px;
  font-size: 1.25rem;
}

.produits-list {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.produit-badge {
  display: inline-block;
  padding: 0.25rem 0.5rem;
  background: #eff6ff;
  color: #1e40af;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 500;
}

.text-muted {
  color: #9ca3af;
  font-style: italic;
}

.livraisons-list,
.commandes-list,
.retours-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.livraison-item,
.commande-item,
.retour-item {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  display: flex;
  justify-content: space-between;
  align-items: center;
  transition: box-shadow 0.2s;
}

.livraison-item:hover,
.commande-item:hover,
.retour-item:hover {
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.vente-info,
.livraison-info,
.commande-info,
.retour-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.vente-date,
.livraison-date,
.commande-date,
.retour-date {
  font-size: 0.875rem;
  color: #64748b;
}

.vente-client,
.livraison-client,
.commande-client,
.retour-client {
  font-size: 1.125rem;
  font-weight: 600;
  color: #1f2937;
}

.vente-montant,
.commande-montant,
.retour-montant {
  font-size: 1.25rem;
  font-weight: 700;
  color: #1a5f4a;
}

.livraison-adresse {
  font-size: 0.95rem;
  color: #64748b;
}

.vente-actions,
.livraison-actions,
.commande-actions,
.retour-actions {
  display: flex;
  gap: 0.5rem;
}

.loading-state,
.sale-loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem;
  text-align: center;
  color: #64748b;
}

.sale-loading-state .loading-spinner {
  font-size: 3rem;
  margin-bottom: 1rem;
  animation: spin 1s linear infinite;
}

.sale-loading-state p {
  margin: 0;
  font-size: 1rem;
  color: #6b7280;
}

.sale-empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem;
  text-align: center;
}

.empty-state-icon {
  font-size: 4rem;
  margin-bottom: 1rem;
}

.empty-state-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 0.5rem;
}

.empty-state-message {
  color: #6b7280;
  margin-bottom: 1.5rem;
  line-height: 1.6;
}

.empty-state {
  text-align: center;
  padding: 3rem;
  color: #64748b;
  font-size: 1rem;
}

@media (max-width: 768px) {
  .agent-stats-cards {
    grid-template-columns: 1fr;
  }
  
  .agent-tabs {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }
  
  .agent-tab {
    white-space: nowrap;
    padding: 0.75rem 1rem;
  }
  
  .vente-item,
  .livraison-item,
  .commande-item,
  .retour-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
}

/* Styles pour la modale de vente */
.sale-modal {
  max-width: 1100px;
  width: calc(100% - 2rem);
  max-height: 85vh;
  height: 85vh;
  position: relative;
  margin-left: calc(280px + max(2rem, (100vw - 280px - 1100px) / 2));
  margin-right: auto;
}

@media (max-width: 1600px) {
  .sale-modal {
    margin-left: calc(280px + 2rem);
    max-width: calc(100vw - 280px - 4rem);
    width: calc(100vw - 280px - 4rem);
  }
}

@media (max-width: 1200px) {
  .sale-modal {
    margin-left: 280px;
    max-width: calc(100vw - 280px - 2rem);
    width: calc(100vw - 280px - 2rem);
  }
}

.sale-modal-body {
  padding: 0;
  height: calc(85vh - 120px);
  overflow: hidden;
}

.sale-modal-container {
  display: flex;
  height: 100%;
  gap: 1rem;
}

.sale-cart-column {
  flex: 0 0 280px;
  display: flex;
  flex-direction: column;
  background: #f9fafb;
  border-radius: 8px;
  padding: 0.75rem;
  overflow: hidden;
}

.sale-cart-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.sale-cart-header h4 {
  margin: 0;
  font-size: 1.25rem;
  color: #1a5f4a;
}

.btn-clear-small {
  background: #ef4444;
  color: white;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 6px;
  cursor: pointer;
  font-size: 0.875rem;
}

.sale-cart-content {
  flex: 1;
  overflow-y: auto;
  margin-bottom: 1rem;
}

.empty-cart-small {
  text-align: center;
  padding: 2rem;
  color: #64748b;
}

.sale-cart-items {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.sale-cart-item {
  background: white;
  border-radius: 8px;
  padding: 1rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.sale-cart-item-info {
  flex: 1;
}

.sale-cart-item-name {
  font-weight: 600;
  margin-bottom: 0.5rem;
  color: #1f2937;
}

.sale-cart-item-qty {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.qty-btn-small {
  background: #1a5f4a;
  color: white;
  border: none;
  width: 24px;
  height: 24px;
  border-radius: 4px;
  cursor: pointer;
  font-size: 0.875rem;
  display: flex;
  align-items: center;
  justify-content: center;
}

.sale-cart-item-price {
  font-weight: 700;
  color: #1a5f4a;
  font-size: 1.125rem;
}

.btn-remove-small {
  background: #ef4444;
  color: white;
  border: none;
  width: 28px;
  height: 28px;
  border-radius: 4px;
  cursor: pointer;
  font-size: 1.25rem;
  display: flex;
  align-items: center;
  justify-content: center;
}

.sale-cart-footer {
  border-top: 2px solid #e5e7eb;
  padding-top: 1rem;
}

.sale-summary {
  margin-bottom: 1rem;
}

.sale-summary-row {
  display: flex;
  justify-content: space-between;
  font-size: 1.125rem;
  margin-bottom: 0.5rem;
}

.sale-summary-row strong {
  color: #1a5f4a;
  font-size: 1.25rem;
}

.btn-checkout {
  width: 100%;
  background: #1a5f4a;
  color: white;
  border: none;
  padding: 1rem;
  border-radius: 8px;
  font-weight: 700;
  font-size: 1.125rem;
  cursor: pointer;
  transition: background 0.2s;
}

.btn-checkout:hover:not(:disabled) {
  background: #134e3a;
}

.btn-checkout:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.sale-products-column {
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.sale-products-header {
  display: flex;
  gap: 1rem;
  margin-bottom: 1rem;
}

.sale-search-input {
  flex: 1;
  padding: 0.75rem;
  border: 1.5px solid #10b981;
  border-radius: 8px;
  font-size: 1rem;
}

.sale-filter-select {
  padding: 0.75rem;
  border: 1.5px solid #10b981;
  border-radius: 8px;
  font-size: 1rem;
  min-width: 200px;
}

.btn-refresh-products {
  padding: 0.75rem 1rem;
  background: #f3f4f6;
  border: 1.5px solid #d1d5db;
  border-radius: 8px;
  font-size: 1.25rem;
  cursor: pointer;
  transition: all 0.2s;
  min-width: 50px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-refresh-products:hover:not(:disabled) {
  background: #e5e7eb;
  border-color: #9ca3af;
  transform: rotate(90deg);
}

.btn-refresh-products:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.sale-products-grid {
  flex: 1;
  overflow-y: auto;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
  gap: 0.75rem;
  padding-right: 0.5rem;
  align-content: start;
  min-height: 0;
}

.sale-product-card {
  background: white;
  border-radius: 8px;
  padding: 0.75rem;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  min-height: 200px;
  height: fit-content;
}

.sale-product-card:hover:not(.out-of-stock) {
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  transform: translateY(-2px);
}

.sale-product-card.out-of-stock {
  opacity: 0.6;
  cursor: not-allowed;
}

.sale-product-image {
  width: 100%;
  height: 120px;
  min-height: 120px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f3f4f6;
  border-radius: 6px;
  overflow: hidden;
  flex-shrink: 0;
}

.sale-product-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.sale-product-icon {
  font-size: 2.5rem;
}

.sale-product-info {
  flex: 1;
}

.sale-product-name {
  font-weight: 600;
  margin-bottom: 0.25rem;
  color: #1f2937;
  font-size: 0.875rem;
  line-height: 1.3;
}

.sale-product-code {
  font-size: 0.75rem;
  color: #64748b;
  margin-bottom: 0.25rem;
}

.sale-product-stock {
  font-size: 0.875rem;
  color: #64748b;
}

.sale-product-price {
  font-weight: 700;
  color: #1a5f4a;
  font-size: 1rem;
  text-align: center;
  padding-top: 0.5rem;
  border-top: 1px solid #e5e7eb;
}

@media (max-width: 1200px) {
  .sale-modal-container {
    flex-direction: column;
  }
  
  .sale-cart-column {
    flex: 0 0 auto;
    max-height: 300px;
  }
  
  .sale-products-grid {
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  }
}

/* Styles pour la modale de reçu */
.receipt-overlay {
  z-index: 10000;
}

.receipt-modal {
  background: white;
  max-width: 600px;
  width: 100%;
  max-height: 90vh;
  border-radius: 16px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.receipt-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem 2rem;
  border-bottom: 2px solid #e5e7eb;
  background: #1a5f4a;
  color: white;
}

.receipt-header h2 {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 700;
}

.receipt-actions {
  display: flex;
  gap: 0.75rem;
  align-items: center;
}

.btn-print-receipt {
  background: white;
  color: #1a5f4a;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  font-size: 0.9rem;
  transition: background 0.2s;
}

.btn-print-receipt:hover {
  background: #f0fdf4;
}

.btn-close-receipt {
  background: transparent;
  color: white;
  border: none;
  font-size: 1.5rem;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.2s;
}

.btn-close-receipt:hover {
  background: rgba(255, 255, 255, 0.2);
}

.receipt-body {
  padding: 2rem;
  overflow-y: auto;
  flex: 1;
}

.receipt-company {
  text-align: center;
  margin-bottom: 2rem;
  padding-bottom: 1.5rem;
  border-bottom: 2px solid #e5e7eb;
}

.receipt-company h3 {
  margin: 0 0 0.5rem 0;
  font-size: 1.75rem;
  color: #1a5f4a;
  font-weight: 700;
}

.receipt-company p {
  margin: 0;
  color: #6b7280;
  font-size: 1rem;
}

.receipt-info {
  margin-bottom: 2rem;
}

.receipt-info-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.75rem;
  font-size: 0.95rem;
}

.receipt-label {
  color: #6b7280;
  font-weight: 600;
}

.receipt-value {
  color: #111827;
  font-weight: 600;
}

.receipt-items {
  margin-bottom: 2rem;
}

.receipt-items-header {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr 1fr;
  gap: 1rem;
  padding: 0.75rem 0;
  border-bottom: 2px solid #e5e7eb;
  font-weight: 700;
  color: #1a5f4a;
  font-size: 0.9rem;
}

.receipt-item-row {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr 1fr;
  gap: 1rem;
  padding: 0.75rem 0;
  border-bottom: 1px solid #e5e7eb;
  font-size: 0.9rem;
}

.receipt-item-name {
  color: #111827;
  font-weight: 600;
}

.receipt-item-qty,
.receipt-item-price,
.receipt-item-total {
  color: #374151;
  text-align: right;
}

.receipt-item-total {
  font-weight: 600;
  color: #1a5f4a;
}

.receipt-summary {
  background: #f9fafb;
  padding: 1.5rem;
  border-radius: 8px;
  margin-bottom: 2rem;
}

.receipt-summary-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.75rem;
  font-size: 1rem;
}

.receipt-summary-row:last-child {
  margin-bottom: 0;
}

.receipt-total-row {
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 2px solid #e5e7eb;
  font-size: 1.3rem;
}

.receipt-total {
  font-weight: 700;
  color: #1a5f4a;
  font-size: 1.5rem;
}

.receipt-discount {
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

@media print {
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
}
</style>







