<template>
  <div class="results-page">
    <div class="govbr-header">
      <div class="container">
        <div class="logo">
          <img src="/images/logo-cnt.png" alt="Logo CNT" class="logo-cnt" />
          <h1>Sistema de Votação CNT</h1>
        </div>
      </div>
    </div>

    <div class="container">
      <div class="results-container">
        <div v-if="loading" class="card text-center">
          <p>Carregando resultados...</p>
        </div>

        <div v-else-if="error" class="card">
          <div class="alert alert-error">{{ error }}</div>
        </div>

        <div v-else-if="results" class="results-content">
          <div class="card">
            <div class="card-header">
              <h2 class="card-title">Resultados da Votação</h2>
            </div>

            <div class="proposal-info">
              <div v-if="results.proposal.number" class="proposal-number">
                Proposta nº {{ results.proposal.number }}
              </div>
              <h3 class="proposal-name">{{ results.proposal.name }}</h3>
              <div class="proposal-status">
                <span v-if="results.proposal.is_active" class="badge badge-success">Ativa</span>
                <span v-else class="badge badge-danger">Encerrada</span>
              </div>
            </div>
          </div>

          <div class="stats-grid">
            <div class="stat-card card">
              <div class="stat-label">Total de Votos</div>
              <div class="stat-value">{{ results.total_votes }}</div>
            </div>
            <div class="stat-card card">
              <div class="stat-label">Votos SIM</div>
              <div class="stat-value text-success">{{ results.yes_votes }}</div>
              <div class="stat-percentage">{{ yesPercentage }}%</div>
            </div>
            <div class="stat-card card">
              <div class="stat-label">Votos NÃO</div>
              <div class="stat-value text-danger">{{ results.no_votes }}</div>
              <div class="stat-percentage">{{ noPercentage }}%</div>
            </div>
          </div>

          <!-- Gráfico Visual -->
          <div class="card">
            <div class="card-header">
              <h3 class="card-title">Distribuição de Votos</h3>
            </div>
            <div class="vote-chart">
              <div class="chart-bar">
                <div class="bar-segment bar-yes" :style="{ width: yesPercentage + '%' }">
                  <span v-if="yesPercentage > 10">{{ yesPercentage }}%</span>
                </div>
                <div class="bar-segment bar-no" :style="{ width: noPercentage + '%' }">
                  <span v-if="noPercentage > 10">{{ noPercentage }}%</span>
                </div>
              </div>
              <div class="chart-legend">
                <div class="legend-item">
                  <span class="legend-color bg-success"></span>
                  <span>SIM ({{ results.yes_votes }})</span>
                </div>
                <div class="legend-item">
                  <span class="legend-color bg-danger"></span>
                  <span>NÃO ({{ results.no_votes }})</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Lista de Votos -->
          <div class="card">
            <div class="card-header">
              <h3 class="card-title">Detalhamento dos Votos</h3>
            </div>
            <table class="table">
              <thead>
                <tr>
                  <th>Data/Hora</th>
                  <th>Delegado</th>
                  <th>Sindicato</th>
                  <th>Voto</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="vote in results.votes" :key="vote.id">
                  <td>{{ formatDate(vote.voted_at) }}</td>
                  <td>{{ vote.voter_name }}</td>
                  <td>{{ vote.union_name }}</td>
                  <td>
                    <span v-if="vote.vote" class="badge badge-success">SIM</span>
                    <span v-else class="badge badge-danger">NÃO</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="text-center mt-4">
          <a href="/" class="btn btn-primary">Voltar para Votação</a>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const results = ref(null);
const loading = ref(true);
const error = ref('');

const yesPercentage = computed(() => {
  if (!results.value || results.value.total_votes === 0) return 0;
  return ((results.value.yes_votes / results.value.total_votes) * 100).toFixed(1);
});

const noPercentage = computed(() => {
  if (!results.value || results.value.total_votes === 0) return 0;
  return ((results.value.no_votes / results.value.total_votes) * 100).toFixed(1);
});

const loadResults = async () => {
  loading.value = true;
  error.value = '';

  try {
    const encryptedId = route.params.encryptedId;
    const response = await axios.get(`/proposals/${encryptedId}/results`);
    results.value = response.data;
  } catch (err) {
    error.value = 'Erro ao carregar resultados. Verifique se o link está correto.';
  } finally {
    loading.value = false;
  }
};

const formatDate = (dateString) => {
  const date = new Date(dateString);
  return date.toLocaleString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

onMounted(() => {
  loadResults();
});
</script>

<style scoped>
.results-page {
  min-height: 100vh;
  background-color: var(--gray-5);
}

.results-container {
  max-width: 1000px;
  margin: 4rem auto;
  padding: 0 var(--spacing-4);
}

.logo-cnt {
  max-height: 60px;
  height: auto;
}

.results-content {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-4);
}

.proposal-info {
  padding: var(--spacing-4);
  background-color: var(--yellow-vivid-10);
  border-radius: var(--border-radius-md);
}

.proposal-number {
  color: var(--blue-warm-vivid-60);
  font-weight: 600;
  font-size: 0.875rem;
  text-transform: uppercase;
  margin-bottom: var(--spacing-2);
}

.proposal-name {
  color: var(--blue-warm-vivid-50);
  font-size: 1.5rem;
  font-weight: 600;
  margin: 0 0 var(--spacing-3) 0;
}

.proposal-status {
  margin-top: var(--spacing-2);
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: var(--spacing-4);
}

.stat-card {
  text-align: center;
  padding: var(--spacing-5);
}

.stat-label {
  font-size: 0.875rem;
  color: var(--gray-60);
  margin-bottom: var(--spacing-2);
}

.stat-value {
  font-size: 2.5rem;
  font-weight: 700;
  color: var(--gray-80);
}

.stat-percentage {
  font-size: 1rem;
  color: var(--gray-60);
  margin-top: var(--spacing-1);
}

.text-success {
  color: var(--green-cool-vivid-50);
}

.text-danger {
  color: var(--red-vivid-50);
}

.vote-chart {
  padding: var(--spacing-4);
}

.chart-bar {
  display: flex;
  height: 60px;
  border-radius: var(--border-radius-md);
  overflow: hidden;
  margin-bottom: var(--spacing-4);
}

.bar-segment {
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 600;
  font-size: 1.1rem;
  transition: width 0.5s ease;
}

.bar-yes {
  background-color: var(--green-cool-vivid-50);
}

.bar-no {
  background-color: var(--red-vivid-50);
}

.chart-legend {
  display: flex;
  gap: var(--spacing-6);
  justify-content: center;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: var(--spacing-2);
}

.legend-color {
  width: 20px;
  height: 20px;
  border-radius: 4px;
}

.bg-success {
  background-color: var(--green-cool-vivid-50);
}

.bg-danger {
  background-color: var(--red-vivid-50);
}

a {
  text-decoration: none;
}
</style>
