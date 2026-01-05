// src/composables/useJournal.js

/**
 * Fonction utilitaire globale pour journaliser un mouvement dans l'application
 * @param {Object} param0
 * @param {string} param0.user - Nom ou ID de l'utilisateur
 * @param {string} param0.action - Action réalisée (ex: "Ajout produit")
 * @param {string} [param0.details] - Détails complémentaires
 * @returns {Promise<Response>} La réponse fetch
 */
export async function logJournal({ user, action, details = '' }) {
  return fetch('https://aliadjame.com/api-stock/api_journal.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ user, action, details })
  });
}

// Utilisation dans n'importe quel composant :
// import { logJournal } from '@/composables/useJournal';
// await logJournal({ user: 'admin', action: 'Suppression client', details: 'ID: 123' });
