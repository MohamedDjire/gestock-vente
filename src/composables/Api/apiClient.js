// src/composables/api/apiClient.js
import axios from 'axios';

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'https://aliadjame.com/api-stock';

const apiClient = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
  },
});


// Ajout automatique du token JWT dans l'en-tête Authorization
apiClient.interceptors.request.use((config) => {
  const token = localStorage.getItem('prostock_token');
  if (token) {
    config.headers['Authorization'] = `Bearer ${token}`;
  }
  return config;
});

export default apiClient;
