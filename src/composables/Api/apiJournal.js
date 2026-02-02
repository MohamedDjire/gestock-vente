// src/composables/Api/apiJournal.js
import apiClient from './apiClient.js';

export default {
  async getJournal(enterpriseId) {
    const params = {}
    if (enterpriseId != null && enterpriseId !== '') {
      params.enterprise_id = enterpriseId
    }
    const response = await apiClient.get('/api_journal.php', { params })
    if (response.data && response.data.success) {
      return response.data.data || []
    }
    throw new Error(response.data?.message || 'Erreur lors du chargement du journal')
  },
  async addJournalEntry(entry) {
    const response = await apiClient.post('/api_journal.php', entry);
    if (response.data && response.data.success) {
      return response.data.data;
    }
    throw new Error(response.data?.message || 'Erreur lors de l\'ajout de l\'entrée');
  }
};
