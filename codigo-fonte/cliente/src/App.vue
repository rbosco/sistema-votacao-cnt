<template>
  <div id="app">
    <router-view />
    <LoadingOverlay />
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { useArmazenamentoAutenticacao } from '@/armazenamentos/autenticacao'
import LoadingOverlay from '@/componentes/LoadingOverlay.vue'

const armazenamentoAuth = useArmazenamentoAutenticacao()

onMounted(() => {
  armazenamentoAuth.carregarUsuario()
})
</script>

<style>
@import './estilos/app.css';
</style>
