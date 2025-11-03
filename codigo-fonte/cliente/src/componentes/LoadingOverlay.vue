<template>
  <Teleport to="body">
    <Transition name="fade">
      <div v-if="loadingStore.isLoading" class="loading-overlay">
        <div class="loading-spinner">
          <div class="spinner"></div>
          <p>Carregando...</p>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { useArmazenamentoLoading } from '@/armazenamentos/loading'

const loadingStore = useArmazenamentoLoading()
</script>

<style scoped>
.loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}

.loading-spinner {
  background: white;
  padding: 2rem;
  border-radius: 8px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
}

.spinner {
  width: 50px;
  height: 50px;
  border: 4px solid #f3f3f3;
  border-top: 4px solid #1351b4;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.loading-spinner p {
  margin: 0;
  color: #1351b4;
  font-weight: 600;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
