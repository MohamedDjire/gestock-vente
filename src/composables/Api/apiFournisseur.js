// src/composables/api/apiFournisseur.js
import axios from 'axios';

const API_URL = 'https://aliadjame.com/api-stock/api_fournisseur.php';

export default {
  async getAll() {
    const { data } = await axios.get(API_URL);
    if (data.success) return data.data;
    throw new Error(data.message || 'Erreur API');
  },
  async create(fournisseur) {
    const { data } = await axios.post(API_URL, fournisseur);
    if (data.success) return data.data;
    throw new Error(data.message || 'Erreur API');
  },
  async update(id, fournisseur) {
    const { data } = await axios.put(`${API_URL}?id=${id}`, fournisseur);
    if (data.success) return data.data;
    throw new Error(data.message || 'Erreur API');
  },
  async delete(id) {
    const { data } = await axios.delete(`${API_URL}?id=${id}`);
    if (data.success) return true;
    throw new Error(data.message || 'Erreur API');
  }
};
