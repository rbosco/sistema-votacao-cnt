<template>
  <div class="admin-layout">
    <AdminHeader />
    <div class="container">
      <div class="votes-page">
        <h1 class="page-title">Visualização de Votos</h1>

        <div v-if="error" class="alert alert-error">{{ error }}</div>

        <div class="filter-card card">
          <div class="form-group">
            <label class="form-label">Filtrar por Proposta</label>
            <select v-model="selectedProposal" @change="filterByProposal" class="form-control">
              <option :value="null">Todas as Propostas</option>
              <option v-for="proposal in proposals" :key="proposal.id" :value="proposal.id">
                {{ proposal.number ? `${proposal.number} - ` : '' }}{{ proposal.name }}
              </option>
            </select>
          </div>
        </div>

        <div class="card">
          <div class="card-header">
            <h2 class="card-title">Votos Registrados ({{ filteredVotes.length }})</h2>
          </div>

          <table class="table">
            <thead>
              <tr>
                <th>Data/Hora</th>
                <th>Proposta</th>
                <th>Delegado</th>
                <th>CPF</th>
                <th>Sindicato</th>
                <th>Voto</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="vote in filteredVotes" :key="vote.id">
                <td>{{ formatDate(vote.voted_at) }}</td>
                <td>
                  <div class="proposal-cell">
                    <span v-if="vote.proposal?.number" class="proposal-number">
                      {{ vote.proposal.number }}
                    </span>
                    <span>{{ vote.proposal?.name }}</span>
                  </div>
                </td>
                <td>{{ vote.voter_name }}</td>
                <td>{{ formatCPF(vote.voter_cpf) }}</td>
                <td>{{ vote.union_name }}</td>
                <td>
                  <span v-if="vote.vote" class="badge badge-success">SIM</span>
                  <span v-else class="badge badge-danger">NÃO</span>
                </td>
              </tr>
              <tr v-if="filteredVotes.length === 0">
                <td colspan="6" class="text-center">Nenhum voto registrado</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Estatísticas -->
        <div v-if="filteredVotes.length > 0" class="stats-section">
          <h2 class="section-title">Estatísticas</h2>
          <div class="stats-grid">
            <div class="stat-card card">
              <div class="stat-label">Total de Votos</div>
              <div class="stat-value">{{ filteredVotes.length }}</div>
            </div>
            <div class="stat-card card">
              <div class="stat-label">Votos SIM</div>
              <div class="stat-value text-success">{{ yesVotes }}</div>
              <div class="stat-percentage">{{ yesPercentage }}%</div>
            </div>
            <div class="stat-card card">
              <div class="stat-label">Votos NÃO</div>
              <div class="stat-value text-danger">{{ noVotes }}</div>
              <div class="stat-percentage">{{ noPercentage }}%</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import AdminHeader from '@/components/AdminHeader.vue';

const votes = ref([]);
const proposals = ref([]);
const selectedProposal = ref(null);
const error = ref('');

const filteredVotes = computed(() => {
  if (!selectedProposal.value) {
    return votes.value;
  }
  return votes.value.filter(vote => vote.proposal_id === selectedProposal.value);
});

const yesVotes = computed(() => {
  return filteredVotes.value.filter(vote => vote.vote === true).length;
});

const noVotes = computed(() => {
  return filteredVotes.value.filter(vote => vote.vote === false).length;
});

const yesPercentage = computed(() => {
  if (filteredVotes.value.length === 0) return 0;
  return ((yesVotes.value / filteredVotes.value.length) * 100).toFixed(1);
});

const noPercentage = computed(() => {
  if (filteredVotes.value.length === 0) return 0;
  return ((noVotes.value / filteredVotes.value.length) * 100).toFixed(1);
});

const loadVotes = async () => {
  try {
    const response = await axios.get('/votes');
    votes.value = response.data;
  } catch (err) {
    error.value = 'Erro ao carregar votos';
  }
};

const loadProposals = async () => {
  try {
    const response = await axios.get('/proposals');
    proposals.value = response.data;
  } catch (err) {
    error.value = 'Erro ao carregar propostas';
  }
};

const filterByProposal = () => {
  // O filtro é aplicado automaticamente pelo computed
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

const formatCPF = (cpf) => {
  return cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
};

onMounted(() => {
  loadVotes();
  loadProposals();

  // Atualiza a cada 30 segundos
  setInterval(() => {
    loadVotes();
  }, 30000);
});
</script>

<style scoped>
.votes-page {
  padding: var(--spacing-6) 0;
}

.page-title {
  font-size: 2rem;
  font-weight: 700;
  color: var(--blue-warm-vivid-60);
  margin-bottom: var(--spacing-6);
}

.filter-card {
  margin-bottom: var(--spacing-4);
}

.proposal-cell {
  display: flex;
  flex-direction: column;
}

.proposal-number {
  font-size: 0.75rem;
  color: var(--blue-warm-vivid-60);
  font-weight: 600;
}

.stats-section {
  margin-top: var(--spacing-6);
}

.section-title {
  font-size: 1.5rem;
  font-weight: 600;
  color: var(--blue-warm-vivid-60);
  margin-bottom: var(--spacing-4);
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
</style>
