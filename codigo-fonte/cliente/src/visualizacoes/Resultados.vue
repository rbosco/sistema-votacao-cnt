<template>
  <div class="resultados-container">
    <header class="header">
      <h1>Resultados da Votação</h1>
    </header>

    <main class="main-content">
      <div v-if="carregando" class="loading">
        <p>Carregando resultados...</p>
      </div>

      <div v-else-if="erro" class="error">
        <p>{{ erro }}</p>
      </div>

      <div v-else class="resultados-card">
        <h2>{{ proposta?.nome }}</h2>
        <p class="proposta-numero">Proposta #{{ proposta?.numero }}</p>

        <div class="estatisticas">
          <div class="stat-card a-favor">
            <h3>A Favor</h3>
            <p class="numero">{{ resultados.a_favor || 0 }}</p>
            <p class="percentual">{{ calcularPercentual('a_favor') }}%</p>
          </div>

          <div class="stat-card contra">
            <h3>Contra</h3>
            <p class="numero">{{ resultados.contra || 0 }}</p>
            <p class="percentual">{{ calcularPercentual('contra') }}%</p>
          </div>

          <div class="stat-card abstencao">
            <h3>Abstenção</h3>
            <p class="numero">{{ resultados.abstencao || 0 }}</p>
            <p class="percentual">{{ calcularPercentual('abstencao') }}%</p>
          </div>
        </div>

        <div class="total">
          <h3>Total de Votos: {{ total }}</h3>
        </div>

        <div class="voltar">
          <router-link to="/votacao">← Voltar para votação</router-link>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import api from '@/servicos/api'

const route = useRoute()

const proposta = ref<any>(null)
const resultados = ref<any>({})
const carregando = ref(true)
const erro = ref('')

const total = computed(() => {
  return (resultados.value.a_favor || 0) +
         (resultados.value.contra || 0) +
         (resultados.value.abstencao || 0)
})

function calcularPercentual(tipo: string) {
  if (total.value === 0) return '0'
  return ((resultados.value[tipo] || 0) / total.value * 100).toFixed(1)
}

onMounted(async () => {
  try {
    const idCriptografado = route.params.idCriptografado
    const response = await api.get(`/api/resultados/${idCriptografado}`)
    proposta.value = response.data.proposta
    resultados.value = response.data.resultados
  } catch (err: any) {
    erro.value = err.response?.data?.message || 'Erro ao carregar resultados'
  } finally {
    carregando.value = false
  }
})
</script>

<style scoped>
.resultados-container {
  min-height: 100vh;
  background: #f5f5f5;
}

.header {
  background: #1351b4;
  color: white;
  padding: 2rem;
  text-align: center;
}

.main-content {
  max-width: 1000px;
  margin: 2rem auto;
  padding: 0 1rem;
}

.resultados-card {
  background: white;
  border-radius: 8px;
  padding: 2rem;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.resultados-card h2 {
  color: #1351b4;
  margin-bottom: 0.5rem;
}

.proposta-numero {
  color: #666;
  margin-bottom: 2rem;
}

.estatisticas {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.stat-card {
  padding: 2rem;
  border-radius: 8px;
  text-align: center;
}

.stat-card.a-favor {
  background: #d3f9d8;
  border: 2px solid #2b8a3e;
}

.stat-card.contra {
  background: #ffe3e3;
  border: 2px solid #c92a2a;
}

.stat-card.abstencao {
  background: #fff3bf;
  border: 2px solid #f59f00;
}

.stat-card h3 {
  margin: 0 0 1rem 0;
  font-size: 1.2rem;
}

.stat-card .numero {
  font-size: 3rem;
  font-weight: bold;
  margin: 0;
}

.stat-card .percentual {
  font-size: 1.5rem;
  margin: 0.5rem 0 0 0;
  opacity: 0.8;
}

.total {
  text-align: center;
  padding: 1.5rem;
  background: #f8f9fa;
  border-radius: 4px;
  margin-bottom: 1.5rem;
}

.total h3 {
  margin: 0;
  color: #1351b4;
}

.loading, .error {
  text-align: center;
  padding: 2rem;
  background: white;
  border-radius: 8px;
}

.error {
  color: #c92a2a;
}

.voltar {
  text-align: center;
  margin-top: 1.5rem;
}

.voltar a {
  color: #1351b4;
  text-decoration: none;
  font-size: 1.1rem;
}

.voltar a:hover {
  text-decoration: underline;
}
</style>
