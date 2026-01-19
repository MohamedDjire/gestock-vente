<template>
  <div v-if="show" class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-content confirmation-modal" @click.stop>
      <div class="modal-header modal-header-with-icon">
        <div class="modal-header-start">
          <span class="modal-header-icon">⚠️</span>
          <h3>Confirmer la suppression</h3>
        </div>
        <button class="modal-close" @click="$emit('close')">×</button>
      </div>
      <div class="modal-body">
        <p>Voulez-vous vraiment supprimer cette écriture ? Cette action est irréversible.</p>
      </div>
      <div class="modal-actions">
        <button class="btn-cancel" @click="$emit('close')">Annuler</button>
        <button class="btn-danger" @click="$emit('confirm')">Supprimer</button>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({ show: Boolean })
defineEmits(['close', 'confirm'])
</script>
