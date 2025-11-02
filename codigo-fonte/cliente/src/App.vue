<template>
  <div id="app">
    <router-view />
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { useArmazenamentoAutenticacao } from '@/armazenamentos/autenticacao'

const armazenamentoAuth = useArmazenamentoAutenticacao()

onMounted(() => {
  armazenamentoAuth.carregarUsuario()
})
</script>

<style>
@import './estilos/app.css';
</style>
