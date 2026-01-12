// src/composables/api/api_point_vente.js
// Composable pour gérer les points de vente (fetch depuis l’API PHP)
import apiClient from './apiClient';

const ENDPOINT = '/api_point_vente.php';

export default {
  async getAll() {
    try {
      const response = await apiClient.get(ENDPOINT + '?action=all');
      if (response.data && Array.isArray(response.data.data)) {
        return response.data.data;
      } else if (Array.isArray(response.data)) {
        return response.data;
      } else {
        return [];
      }
    } catch (error) {
      console.error('Erreur lors du chargement des points de vente :', error);
      return [];
    }
  },
  // Ajoute d’autres méthodes si besoin (create, update, delete)
};
