<template>
  <div class="resultados-container">
    <header class="header" v-if="!carregando">
      <img :src="bannerUrl" alt="Sistema de Votação CNT" class="banner" />
    </header>

    <main class="main-content">
      <div v-if="carregando" class="loading">
        <p>Carregando resultados...</p>
      </div>

      <div v-else-if="erro" class="error">
        <p>{{ erro }}</p>
      </div>

      <div v-else class="resultados-card">
        <h2>Resultado de votação da proposta: {{ proposta?.nome }}</h2>

        <!-- Mostrar mensagem se votação encerrada -->
        <div v-if="proposta?.status === 'encerrada'" class="status-mensagem encerrada">
          <div class="status-icone">🔒</div>
          <div class="status-texto">Votação Encerrada</div>
        </div>

        <!-- Classificação da votação -->
        <div class="classificacao-card" :class="classificacaoClass">
          <div class="classificacao-icone">{{ classificacaoIcone }}</div>
          <div class="classificacao-titulo">{{ resultados.classificacao }}</div>
          <div class="classificacao-percentual">{{ resultados.percentual }}%</div>
        </div>

        <div class="estatisticas">
          <div class="stat-card aptos">
            <h3>Total de Aptos a Votar</h3>
            <p class="numero">{{ resultados.total_aptos || 0 }}</p>
          </div>

          <div class="stat-card sim">
            <h3>Votos Sim</h3>
            <p class="numero">{{ resultados.votos_sim || 0 }}</p>
          </div>

          <div class="stat-card total-votos">
            <h3>Total de Votos Registrados</h3>
            <p class="numero">{{ totalVotos || 0 }}</p>
          </div>
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
const totalVotos = ref<number>(0)
const configuracoes = ref<any>({})
const carregando = ref(true)
const erro = ref('')

const bannerUrl = computed(() => {
  return configuracoes.value.banner_votacao || '/images/banner-cnt.png'
})

const classificacaoClass = computed(() => {
  const classificacao = resultados.value.classificacao
  if (classificacao === 'Ampla Maioria') return 'ampla-maioria'
  if (classificacao === 'Maioria') return 'maioria'
  if (classificacao === 'Minoria') return 'minoria'
  return ''
})

const classificacaoIcone = computed(() => {
  const classificacao = resultados.value.classificacao
  if (classificacao === 'Ampla Maioria') return '🏆'
  if (classificacao === 'Maioria') return '👍'
  if (classificacao === 'Minoria') return '📊'
  return '📊'
})

onMounted(async () => {
  try {
    const idCriptografado = route.params.idCriptografado

    // Carregar configurações e resultados em paralelo
    const [resultadosResponse, configResponse] = await Promise.all([
      api.get(`/propostas/${idCriptografado}/resultados`),
      api.get('/configuracoes', { skipLoading: true })
    ])

    proposta.value = resultadosResponse.data.proposta
    resultados.value = resultadosResponse.data.resultados
    totalVotos.value = resultadosResponse.data.total_votos || 0
    configuracoes.value = configResponse.data
  } catch (err: any) {
    erro.value = err.response?.data?.mensagem || 'Erro ao carregar resultados'
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
  padding: 0;
  text-align: center;
}

.header .banner {
  width: 100%;
  height: auto;
  display: block;
}

.main-content {
  max-width: 800px;
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
  margin-bottom: 2rem;
  text-align: center;
  font-size: 1.8rem;
}

.classificacao-card {
  text-align: center;
  padding: 2.5rem;
  margin-bottom: 2rem;
  border-radius: 12px;
  border: 3px solid;
}

.classificacao-icone {
  font-size: 4rem;
  margin-bottom: 0.5rem;
}

.classificacao-titulo {
  font-size: 2rem;
  font-weight: bold;
  margin-bottom: 0.5rem;
}

.classificacao-percentual {
  font-size: 2.5rem;
  font-weight: bold;
  opacity: 0.9;
}

.classificacao-card.ampla-maioria {
  background: #d3f9d8;
  border-color: #2b8a3e;
  color: #2b8a3e;
}

.classificacao-card.maioria {
  background: #e7f5ff;
  border-color: #1351b4;
  color: #1351b4;
}

.classificacao-card.minoria {
  background: #fff4e6;
  border-color: #f59f00;
  color: #f59f00;
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
  border: 2px solid;
}

.stat-card.aptos {
  background: #e7f5ff;
  border-color: #1351b4;
}

.stat-card.sim {
  background: #d3f9d8;
  border-color: #2b8a3e;
}

.stat-card.total-votos {
  background: #f8f9fa;
  border-color: #666;
}

.stat-card h3 {
  margin: 0 0 1rem 0;
  font-size: 1rem;
  color: #333;
  font-weight: 600;
}

.stat-card .numero {
  font-size: 3rem;
  font-weight: bold;
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

.status-mensagem {
  text-align: center;
  padding: 2rem;
  margin-bottom: 2rem;
  border-radius: 12px;
  background: #f8f9fa;
}

.status-icone {
  font-size: 4rem;
  margin-bottom: 0.5rem;
}

.status-texto {
  font-size: 1.5rem;
  font-weight: bold;
  margin: 0;
}

.status-mensagem.encerrada {
  background: #e9ecef;
  border: 2px solid #666;
}

.status-mensagem.encerrada .status-texto {
  color: #666;
}
</style>
