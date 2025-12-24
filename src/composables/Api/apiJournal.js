// src/composables/api/apiJournal.js
import apiClient from './apiClient';

export default {
  async getJournal() {
    const res = await apiClient.get('/api_journal.php');
    return res.data.data;
  },
  async addJournalEntry(entry) {
    return apiClient.post('/api_journal.php', entry);
  }
};
