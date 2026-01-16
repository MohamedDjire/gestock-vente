<template>
  <div>
    <h2>Trésorerie</h2>
    <table class="compta-table">
      <thead>
        <tr>
          <th>Compte</th>
          <th>Entrées</th>
          <th>Sorties</th>
          <th>Solde</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="ligne in tresorerie" :key="ligne.compte">
          <td>{{ ligne.compte }}</td>
          <td>{{ ligne.entrees }}</td>
          <td>{{ ligne.sorties }}</td>
          <td>{{ ligne.solde }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
<script setup>
defineProps({ tresorerie: Array })
</script>
<style scoped>
.compta-table { width: 100%; border-collapse: collapse; background: #fff; }
.compta-table th, .compta-table td { padding: 0.7rem 1rem; border-bottom: 1px solid #e5e7eb; text-align: left; }
.compta-table th { background: #f3f4f6; color: #1a5f4a; font-weight: 700; }
</style>
