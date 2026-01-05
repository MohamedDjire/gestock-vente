import apiClient from './apiClient'

const ENDPOINT = '/index.php'

export default {
  async getAll() {
    const { data } = await apiClient.get(ENDPOINT + '?action=all')
    if (data && data.success) return data.data
    throw new Error(data.message || 'Erreur API')
  },
  async create(payload) {
    const { data } = await apiClient.post(ENDPOINT + '?action=create', payload)
    if (data && data.success) return data.data
    throw new Error(data.message || 'Erreur API')
  },
  async update(id, payload) {
    const { data } = await apiClient.put(ENDPOINT + `?action=update&id=${id}`, payload)
    if (data && data.success) return data.data
    throw new Error(data.message || 'Erreur API')
  },
  async delete(id) {
    const { data } = await apiClient.delete(ENDPOINT + `?action=delete&id=${id}`)
    if (data && data.success) return data.data
    throw new Error(data.message || 'Erreur API')
  }
}
