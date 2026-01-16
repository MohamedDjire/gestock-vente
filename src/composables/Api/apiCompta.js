// src/composables/api/apiCompta.js
import { apiService } from '../Api/apiService.js'

// --- Écritures comptables ---
export function getEcritures(id_entreprise) {
  return apiService.get(`/api_compta_ecritures.php?action=all&id_entreprise=${id_entreprise}`)
}
export function getEcriture(id, id_entreprise) {
  return apiService.get(`/api_compta_ecritures.php?action=get&id=${id}&id_entreprise=${id_entreprise}`)
}
export function createEcriture(data) {
  return apiService.post('/api_compta_ecritures.php?action=create', data)
}
export function updateEcriture(id, data) {
  return apiService.put(`/api_compta_ecritures.php?action=update&id=${id}`, data)
}
export function deleteEcriture(id, id_entreprise) {
  return apiService.delete(`/api_compta_ecritures.php?action=delete&id=${id}&id_entreprise=${id_entreprise}`)
}

// --- Factures clients ---
export function getFacturesClients(id_entreprise) {
  return apiService.get(`/api_compta_factures_clients.php?action=all&id_entreprise=${id_entreprise}`)
}
export function createFactureClient(data) {
  return apiService.post('/api_compta_factures_clients.php?action=create', data)
}
export function updateFactureClient(id, data) {
  return apiService.put(`/api_compta_factures_clients.php?action=update&id=${id}`, data)
}
export function deleteFactureClient(id, id_entreprise) {
  return apiService.delete(`/api_compta_factures_clients.php?action=delete&id=${id}&id_entreprise=${id_entreprise}`)
}

// --- Factures fournisseurs ---
export function getFacturesFournisseurs(id_entreprise) {
  return apiService.get(`/api_compta_factures_fournisseurs.php?action=all&id_entreprise=${id_entreprise}`)
}
export function createFactureFournisseur(data) {
  return apiService.post('/api_compta_factures_fournisseurs.php?action=create', data)
}
export function updateFactureFournisseur(id, data) {
  return apiService.put(`/api_compta_factures_fournisseurs.php?action=update&id=${id}`, data)
}
export function deleteFactureFournisseur(id, id_entreprise) {
  return apiService.delete(`/api_compta_factures_fournisseurs.php?action=delete&id=${id}&id_entreprise=${id_entreprise}`)
}

// --- Trésorerie ---
export function getTresorerie(id_entreprise) {
  return apiService.get(`/api_compta_tresorerie.php?action=all&id_entreprise=${id_entreprise}`)
}
export function createTresorerie(data) {
  return apiService.post('/api_compta_tresorerie.php?action=create', data)
}
export function updateTresorerie(id, data) {
  return apiService.put(`/api_compta_tresorerie.php?action=update&id=${id}`, data)
}
export function deleteTresorerie(id, id_entreprise) {
  return apiService.delete(`/api_compta_tresorerie.php?action=delete&id=${id}&id_entreprise=${id_entreprise}`)
}

// --- Rapports ---
export function getRapports(id_entreprise) {
  return apiService.get(`/api_compta_rapports.php?action=all&id_entreprise=${id_entreprise}`)
}
export function createRapport(data) {
  return apiService.post('/api_compta_rapports.php?action=create', data)
}
export function deleteRapport(id, id_entreprise) {
  return apiService.delete(`/api_compta_rapports.php?action=delete&id=${id}&id_entreprise=${id_entreprise}`)
}

// --- Audit ---
export function getAuditTrail(id_entreprise) {
  return apiService.get(`/api_compta_audit.php?action=all&id_entreprise=${id_entreprise}`)
}
export function createAudit(data) {
  return apiService.post('/api_compta_audit.php?action=create', data)
}
