<template>
  <div>
    <h2>Comptabilité des achats et dépenses</h2>
    <table class="compta-table">
      <thead>
        <tr>
          <th>Facture</th>
          <th>Fournisseur</th>
          <th>Montant</th>
          <th>Date</th>
          <th>Justificatif</th>
          <th>Mode de paiement</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="facture in factures" :key="facture.id">
          <td>{{ facture.numero }}</td>
          <td>{{ facture.fournisseur }}</td>
          <td>{{ facture.montant }}</td>
          <td>{{ facture.date }}</td>
          <td>{{ facture.justificatif }}</td>
          <td>{{ facture.paiement }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
<script setup>
defineProps({ factures: Array })
</script>
<style scoped>
.compta-table { width: 100%; border-collapse: collapse; background: #fff; }
.compta-table th, .compta-table td { padding: 0.7rem 1rem; border-bottom: 1px solid #e5e7eb; text-align: left; }
.compta-table th { background: #f3f4f6; color: #1a5f4a; font-weight: 700; }
</style>
