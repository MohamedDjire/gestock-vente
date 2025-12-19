<template>
  <div class="sales-table">
    <h3 class="table-title">Top commerciaux</h3>
    <table>
      <thead>
        <tr>
          <th>Nom</th>
          <th>Ventes</th>
          <th>Produits</th>
          <th>Premium</th>
          <th>Statut</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td class="user-cell">
            <img class="avatar" src="https://randomuser.me/api/portraits/men/32.jpg" alt="" />
            Nicholas Patrick
          </td>
          <td>$2540.58</td>
          <td>150</td>
          <td>105</td>
          <td><span class="badge gold">Gold</span></td>
        </tr>
       
      
        <tr>
          <td class="user-cell">
            <img class="avatar" src="https://randomuser.me/api/portraits/women/35.jpg" alt="" />
            Larissa Burton
          </td>
          <td>$2340.58</td>
          <td>120</td>
          <td>99</td>
          <td><span class="badge gold">Gold</span></td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
// Statique pour l'instant
</script>

<style scoped>
/* Tableau moderne, fond blanc, bordures douces, largeur responsive */
.sales-table {
  background: #fff;
  border-radius: 20px;
  box-shadow: 0 6px 24px 0 rgba(26,95,74,0.10);
  padding: 2.2rem 2.2rem 2rem 2.2rem;
  flex: 2;
  font-weight: 600;
  font-size: 0.95rem;
}
.user-cell {
  display: flex;
  align-items: center;
  gap: 0.7em;
}
.avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid #1a5f4a22;
}
.badge.gold {
  background: linear-gradient(90deg, #ffd700 60%, #fffbe6 100%);
  color: #1a5f4a;
  border: 1px solid #ffe066;
}
.badge.silver {
  background: linear-gradient(90deg, #c0c0c0 60%, #f8f8f8 100%);
  color: #1a5f4a;
  border: 1px solid #e0e0e0;
}
.table-title {
  color: #1a5f4a;
  font-weight: 600;
  margin-bottom: 1.2rem;
  font-size: 1.2rem;
}
.sales-table table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  background: transparent;
}
.sales-table th, .sales-table td {
  padding: 0.8rem 1rem;
  text-align: left;
  font-size: 1rem;
  background: transparent;
}
.sales-table th {
  color: #1a5f4a;
  font-weight: 600;
  background: #f6faf9;
  border-bottom: 2px solid #e3e3e3;
}
.sales-table tr {
  border-bottom: 1px solid #e3e3e3;
  transition: background 0.2s;
}
.sales-table tr:hover {
  background: #f6faf9;
}
.badge {
  padding: 0.3em 0.8em;
  border-radius: 12px;
  font-size: 0.95em;
  font-weight: 500;
  color: #fff;
  box-shadow: 0 2px 8px 0 rgba(26,95,74,0.10);
}
.badge-success {
  background: #1a5f4a;
}
.badge-warning {
  background: #f7b731;
  color: #1a5f4a;
}
.badge-danger {
  background: #eb3b5a;
}
.avatar-cell {
  display: flex;
  align-items: center;
  gap: 0.7em;
}
.avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid #1a5f4a22;
}
</style>
