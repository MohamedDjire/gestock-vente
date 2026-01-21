// src/composables/Api/apiFournisseur.js
import { apiService } from './apiService.js';

export default {
  async getAll() {
    const response = await apiService.get('/api_fournisseur.php?action=all');
    if (Array.isArray(response)) return response;
    if (response && response.success) {
      return Array.isArray(response.data) ? response.data : (response.data || []);
    }
    throw new Error(response?.message || 'Erreur lors du chargement des fournisseurs');
  },
  async create(fournisseur) {
    const response = await apiService.post('/api_fournisseur.php?action=create', fournisseur);
    if (response && response.success) {
      return response.data;
    }
    throw new Error(response?.message || 'Erreur lors de la création du fournisseur');
  },
  async update(id, fournisseur) {
    const response = await apiService.put(`/api_fournisseur.php?action=update&id=${id}`, fournisseur);
    if (response && response.success) {
      return response.data;
    }
    throw new Error(response?.message || 'Erreur lors de la mise à jour du fournisseur');
  },
  async delete(id) {
    const response = await apiService.delete(`/api_fournisseur.php?action=delete&id=${id}`);
    if (response && response.success) {
      return true;
    }
    throw new Error(response?.message || 'Erreur lors de la suppression du fournisseur');
  }
};
