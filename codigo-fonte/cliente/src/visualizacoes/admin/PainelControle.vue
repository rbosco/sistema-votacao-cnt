<template>
  <div class="admin-container">
    <nav class="sidebar">
      <div class="logo">
        <h2>Admin CNT</h2>
      </div>
      <ul class="menu">
        <li><router-link to="/admin" class="active">Painel</router-link></li>
        <li><router-link to="/admin/propostas">Propostas</router-link></li>
        <li><router-link to="/admin/usuarios">Usuários</router-link></li>
        <li><router-link to="/admin/votos">Votos</router-link></li>
        <li><a href="#" @click.prevent="sair" class="sair">Sair</a></li>
      </ul>
    </nav>

    <main class="main-content">
      <header class="header">
        <h1>Painel de Controle</h1>
        <p>Bem-vindo ao sistema de votação CNT</p>
      </header>

      <div class="dashboard">
        <div class="stat-card">
          <div class="stat-icon">📊</div>
          <h3>Total de Propostas</h3>
          <p class="stat-number">{{ estatisticas.total_propostas }}</p>
        </div>

        <div class="stat-card">
          <div class="stat-icon">✅</div>
          <h3>Propostas Ativas</h3>
          <p class="stat-number">{{ estatisticas.propostas_ativas }}</p>
        </div>

        <div class="stat-card">
          <div class="stat-icon">🗳️</div>
          <h3>Total de Votos</h3>
          <p class="stat-number">{{ estatisticas.total_votos }}</p>
        </div>

        <div class="stat-card">
          <div class="stat-icon">👥</div>
          <h3>Usuários Cadastrados</h3>
          <p class="stat-number">{{ estatisticas.total_usuarios }}</p>
        </div>
      </div>

      <div class="atalhos">
        <h2>Ações Rápidas</h2>
        <div class="atalhos-grid">
          <router-link to="/admin/propostas" class="atalho-card">
            <span>➕</span>
            <p>Nova Proposta</p>
          </router-link>
          <router-link to="/admin/usuarios" class="atalho-card">
            <span>👤</span>
            <p>Novo Usuário</p>
          </router-link>
          <router-link to="/admin/votos" class="atalho-card">
            <span>📈</span>
            <p>Ver Relatórios</p>
          </router-link>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useArmazenamentoAutenticacao } from '@/armazenamentos/autenticacao'
import api from '@/servicos/api'

const router = useRouter()
const armazenamentoAuth = useArmazenamentoAutenticacao()

const estatisticas = ref({
  total_propostas: 0,
  propostas_ativas: 0,
  total_votos: 0,
  total_usuarios: 0
})

onMounted(async () => {
  try {
    const response = await api.get('/api/admin/estatisticas')
    estatisticas.value = response.data
  } catch (err) {
    console.error('Erro ao carregar estatísticas:', err)
  }
})

function sair() {
  armazenamentoAuth.sair()
  router.push('/login')
}
</script>

<style scoped>
.admin-container {
  display: flex;
  min-height: 100vh;
}

.sidebar {
  width: 250px;
  background: #1351b4;
  color: white;
  padding: 2rem 0;
}

.logo {
  padding: 0 2rem 2rem;
  border-bottom: 1px solid rgba(255,255,255,0.2);
}

.logo h2 {
  margin: 0;
}

.menu {
  list-style: none;
  padding: 1rem 0;
  margin: 0;
}

.menu li {
  margin: 0;
}

.menu a {
  display: block;
  padding: 1rem 2rem;
  color: white;
  text-decoration: none;
  transition: background 0.2s;
}

.menu a:hover,
.menu a.active {
  background: rgba(255,255,255,0.1);
}

.menu a.sair {
  color: #ffcccb;
}

.main-content {
  flex: 1;
  background: #f5f5f5;
  padding: 2rem;
}

.header {
  margin-bottom: 2rem;
}

.header h1 {
  color: #1351b4;
  margin: 0 0 0.5rem 0;
}

.header p {
  color: #666;
  margin: 0;
}

.dashboard {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 3rem;
}

.stat-card {
  background: white;
  padding: 2rem;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  text-align: center;
}

.stat-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
}

.stat-card h3 {
  margin: 0 0 1rem 0;
  color: #666;
  font-size: 1rem;
}

.stat-number {
  font-size: 2.5rem;
  font-weight: bold;
  color: #1351b4;
  margin: 0;
}

.atalhos {
  background: white;
  padding: 2rem;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.atalhos h2 {
  color: #1351b4;
  margin: 0 0 1.5rem 0;
}

.atalhos-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.atalho-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  padding: 2rem;
  border: 2px solid #e9ecef;
  border-radius: 8px;
  text-decoration: none;
  color: #1351b4;
  transition: all 0.2s;
}

.atalho-card:hover {
  border-color: #1351b4;
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(19, 81, 180, 0.1);
}

.atalho-card span {
  font-size: 2rem;
}

.atalho-card p {
  margin: 0;
  font-weight: 600;
}
</style>
