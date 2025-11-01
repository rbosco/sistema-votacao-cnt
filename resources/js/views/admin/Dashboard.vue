<template>
  <div class="admin-layout">
    <AdminHeader />
    <div class="container">
      <div class="dashboard">
        <h1 class="page-title">Dashboard Administrativo</h1>

        <div class="stats-grid">
          <div class="stat-card card">
            <div class="stat-icon" style="background-color: var(--blue-warm-vivid-60);">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </div>
            <div class="stat-content">
              <div class="stat-value">{{ stats.totalProposals }}</div>
              <div class="stat-label">Total de Propostas</div>
            </div>
          </div>

          <div class="stat-card card">
            <div class="stat-icon" style="background-color: var(--green-cool-vivid-50);">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div class="stat-content">
              <div class="stat-value">{{ stats.totalVotes }}</div>
              <div class="stat-label">Total de Votos</div>
            </div>
          </div>

          <div class="stat-card card">
            <div class="stat-icon" style="background-color: var(--yellow-vivid-20); color: var(--blue-warm-vivid-50);">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
              </svg>
            </div>
            <div class="stat-content">
              <div class="stat-value">{{ stats.totalUsers }}</div>
              <div class="stat-label">Usuários Cadastrados</div>
            </div>
          </div>
        </div>

        <div class="card mt-4">
          <div class="card-header">
            <h2 class="card-title">Acesso Rápido</h2>
          </div>
          <div class="quick-actions">
            <router-link to="/admin/proposals" class="btn btn-primary">
              Gerenciar Propostas
            </router-link>
            <router-link to="/admin/votes" class="btn btn-secondary">
              Visualizar Votos
            </router-link>
            <router-link to="/admin/users" class="btn btn-outline">
              Gerenciar Usuários
            </router-link>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import AdminHeader from '@/components/AdminHeader.vue';

const stats = ref({
  totalProposals: 0,
  totalVotes: 0,
  totalUsers: 0,
});

const loadStats = async () => {
  try {
    const [proposalsRes, votesRes, usersRes] = await Promise.all([
      axios.get('/proposals'),
      axios.get('/votes'),
      axios.get('/users'),
    ]);

    stats.value.totalProposals = proposalsRes.data.length;
    stats.value.totalVotes = votesRes.data.length;
    stats.value.totalUsers = usersRes.data.length;
  } catch (error) {
    console.error('Erro ao carregar estatísticas:', error);
  }
};

onMounted(() => {
  loadStats();
});
</script>

<style scoped>
.dashboard {
  padding: var(--spacing-6) 0;
}

.page-title {
  font-size: 2rem;
  font-weight: 700;
  color: var(--blue-warm-vivid-60);
  margin-bottom: var(--spacing-6);
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: var(--spacing-4);
  margin-bottom: var(--spacing-6);
}

.stat-card {
  display: flex;
  align-items: center;
  gap: var(--spacing-4);
  padding: var(--spacing-5);
}

.stat-icon {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
}

.stat-icon svg {
  width: 32px;
  height: 32px;
}

.stat-content {
  flex: 1;
}

.stat-value {
  font-size: 2rem;
  font-weight: 700;
  color: var(--gray-80);
}

.stat-label {
  font-size: 0.875rem;
  color: var(--gray-60);
}

.quick-actions {
  display: flex;
  gap: var(--spacing-3);
  flex-wrap: wrap;
}

a {
  text-decoration: none;
}
</style>
