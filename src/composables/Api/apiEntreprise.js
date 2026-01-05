import apiClient from '../Api/apiClient'

const ENDPOINT = '/api_entreprise.php'

export default {
  async getEntreprise(id) {
    const { data } = await apiClient.get(ENDPOINT + `?action=single&id=${id}`)
    if (data && data.success) return data.data
    throw new Error(data.message || 'Erreur API')
  },
  async updateEntreprise(id, payload) {
    const { data } = await apiClient.put(ENDPOINT + `?action=update&id=${id}`, payload)
    if (data && data.success) return data.data
    throw new Error(data.message || 'Erreur API')
  }
}
