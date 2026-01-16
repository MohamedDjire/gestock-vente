<template>
  <div v-if="show" class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-content confirm-modal" @click.stop>
      <div class="modal-header">
        <h3>Confirmer la suppression</h3>
        <button class="modal-close" @click="$emit('close')">×</button>
      </div>
      <div class="modal-body">
        <p>Voulez-vous vraiment supprimer cette écriture ? Cette action est irréversible.</p>
      </div>
      <div class="modal-actions">
        <button class="btn-cancel" @click="$emit('close')">Annuler</button>
        <button class="btn-delete" @click="$emit('confirm')">Supprimer</button>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({ show: Boolean })
defineEmits(['close', 'confirm'])
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(0,0,0,0.3);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}
.confirm-modal {
  background: #fff;
  border-radius: 8px;
  padding: 2rem;
  min-width: 320px;
  box-shadow: 0 2px 16px rgba(0,0,0,0.15);
}
.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}
.btn-cancel {
  background: #eee;
  color: #333;
  border: none;
  padding: 0.5rem 1.2rem;
  border-radius: 4px;
  margin-right: 1rem;
  cursor: pointer;
}
.btn-delete {
  background: #e53e3e;
  color: #fff;
  border: none;
  padding: 0.5rem 1.2rem;
  border-radius: 4px;
  cursor: pointer;
}
</style>
