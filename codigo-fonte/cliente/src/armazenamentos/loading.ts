import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useArmazenamentoLoading = defineStore('loading', () => {
  const isLoading = ref(false)
  const loadingCount = ref(0)

  function iniciar() {
    loadingCount.value++
    isLoading.value = true
  }

  function parar() {
    if (loadingCount.value > 0) {
      loadingCount.value--
    }
    if (loadingCount.value === 0) {
      isLoading.value = false
    }
  }

  function reset() {
    loadingCount.value = 0
    isLoading.value = false
  }

  return {
    isLoading,
    iniciar,
    parar,
    reset,
  }
})
