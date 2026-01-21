// src/composables/api/apiCompta.js
import { apiService } from './apiService.js'

export default {
  async getEcritures(id_entreprise) {
    const res = await apiService.get(`/api_compta_ecritures.php?id_entreprise=${id_entreprise}`)
    return res.data
  },
  async addEcriture(ecriture) {
    const res = await apiService.post('/api_compta_ecritures.php', ecriture)
    return res.data
  }
  // Ajoute update/delete si besoin
}
