<template>
  <div>
    <table class="compta-table">
      <thead>
        <tr>
          <th>Date</th>
          <th>Type</th>
          <th>Catégorie</th>
          <th>Montant</th>
          <th>Statut</th>
          <th>Description</th>
          <th>Référence</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="entry in entries" :key="entry.id_compta">
          <td>{{ entry.date_ecriture }}</td>
          <td>{{ entry.type_ecriture }}</td>
          <td>{{ entry.categorie }}</td>
          <td>{{ entry.montant }}</td>
          <td>{{ entry.statut }}</td>
          <td>{{ entry.details }}</td>
          <td>{{ entry.reference }}</td>
          <td>
            <button @click="$emit('edit', entry)">✏️</button>
            <button @click="$emit('delete', entry)">🗑️</button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
<script setup>
defineProps({ entries: Array }) 
</script>
<style scoped>
.compta-table { width: 100%; border-collapse: collapse; background: #fff; }
.compta-table th, .compta-table td { padding: 0.7rem 1rem; border-bottom: 1px solid #e5e7eb; text-align: left; }
.compta-table th { background: #f3f4f6; color: #1a5f4a; font-weight: 700; }
</style>
