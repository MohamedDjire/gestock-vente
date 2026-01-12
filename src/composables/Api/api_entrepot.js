// src/composables/api/api_entrepot.js
// Composable pour gérer les entrepôts (fetch depuis l’API PHP)
import apiClient from './apiClient';

const ENDPOINT = '/api_entrepot.php';

export default {
  async getAll() {
    try {
      const response = await apiClient.get(ENDPOINT + '?action=all');
      return response.data;
    } catch (error) {
      console.error('Erreur lors du chargement des entrepôts :', error);
      return [];
    }
  },
  // Ajoute d’autres méthodes si besoin (create, update, delete)
};
