<template>
  <div class="sales-table">
    <h2>Top Sales Representative</h2>
    <table>
      <thead>
        <tr>
          <th>Name</th>
          <th>Total Sales</th>
          <th>Products</th>
          <th>Premium</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td class="user-cell">
            <img src="https://randomuser.me/api/portraits/men/32.jpg" alt="" />
            Nicholas Patrick
          </td>
          <td>$2540.58</td>
          <td>150 Products</td>
          <td>105 Premium</td>
          <td><span class="badge gold">Gold</span></td>
        </tr>
        <tr>
          <td class="user-cell">
            <img src="https://randomuser.me/api/portraits/men/33.jpg" alt="" />
            Cordell Edwards
          </td>
          <td>$1567.80</td>
          <td>95 Products</td>
          <td>60 Premium</td>
          <td><span class="badge silver">Silver</span></td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
// Statique pour l'instant
</script>

<style scoped>
.sales-table {
  background: #fff;
  border-radius: 18px;
  box-shadow: 0 2px 12px #0001;
  padding: 2rem;
  flex: 2;
  margin-right: 2rem;
}
h2 {
  color: #1a5f4a;
  margin-bottom: 1.2rem;
}
table {
  width: 100%;
  border-collapse: collapse;
}
th, td {
  padding: 0.8rem 0.5rem;
  text-align: left;
}
th {
  color: #a3a3a3;
  font-weight: 600;
  font-size: 1rem;
}
td {
  font-size: 1.05rem;
}
.user-cell {
  display: flex;
  align-items: center;
  gap: 0.7rem;
}
.user-cell img {
  width: 32px;
  height: 32px;
  border-radius: 50%;
}
.badge {
  padding: 0.3rem 0.9rem;
  border-radius: 12px;
  font-weight: 600;
  font-size: 0.95rem;
}
.gold {
  background: #ffe9a7;
  color: #bfa100;
}
.silver {
  background: #e6e6e6;
  color: #7d7d7d;
}
</style>
