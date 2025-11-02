<template>
  <div class="admin-container">
    <nav class="sidebar">
      <div class="logo">
        <h2>Admin CNT</h2>
      </div>
      <ul class="menu">
        <li><router-link to="/admin">Painel</router-link></li>
        <li><router-link to="/admin/propostas">Propostas</router-link></li>
        <li><router-link to="/admin/usuarios">Usuários</router-link></li>
        <li><router-link to="/admin/votos" class="active">Votos</router-link></li>
        <li><a href="#" @click.prevent="sair" class="sair">Sair</a></li>
      </ul>
    </nav>

    <main class="main-content">
      <header class="header">
        <h1>Relatório de Votos</h1>
      </header>

      <div class="filtros">
        <div class="form-group">
          <label>Filtrar por Proposta:</label>
          <select v-model="propostaFiltro" @change="carregarVotos">
            <option value="">Todas as propostas</option>
            <option v-for="proposta in propostas" :key="proposta.id" :value="proposta.id">
              Proposta {{ proposta.numero }} - {{ proposta.nome }}
            </option>
          </select>
        </div>

        <button @click="exportarCSV" class="btn-exportar">
          📥 Exportar CSV
        </button>
      </div>

      <div class="estatisticas-grid">
        <div class="stat-card a-favor">
          <h3>A Favor</h3>
          <p class="numero">{{ estatisticas.a_favor || 0 }}</p>
        </div>

        <div class="stat-card contra">
          <h3>Contra</h3>
          <p class="numero">{{ estatisticas.contra || 0 }}</p>
        </div>

        <div class="stat-card abstencao">
          <h3>Abstenção</h3>
          <p class="numero">{{ estatisticas.abstencao || 0 }}</p>
        </div>

        <div class="stat-card total">
          <h3>Total</h3>
          <p class="numero">{{ estatisticas.total || 0 }}</p>
        </div>
      </div>

      <div class="tabela-container">
        <table>
          <thead>
            <tr>
              <th>Data/Hora</th>
              <th>Proposta</th>
              <th>Votante</th>
              <th>Sindicato</th>
              <th>Voto</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="voto in votos" :key="voto.id">
              <td>{{ formatarData(voto.votado_em) }}</td>
              <td>{{ voto.proposta?.numero }} - {{ voto.proposta?.nome }}</td>
              <td>{{ voto.nome_votante }}</td>
              <td>{{ voto.nome_sindicato }}</td>
              <td>
                <span :class="['badge-voto', voto.voto]">
                  {{ formatarVoto(voto.voto) }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>

        <div v-if="votos.length === 0" class="sem-dados">
          Nenhum voto registrado
        </div>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useArmazenamentoAutenticacao } from '@/armazenamentos/autenticacao'
import api from '@/servicos/api'
import { formatarCPF } from '@/utilidades/formatadores'

const router = useRouter()
const armazenamentoAuth = useArmazenamentoAutenticacao()

const votos = ref<any[]>([])
const propostas = ref<any[]>([])
const propostaFiltro = ref('')

const estatisticas = computed(() => {
  const stats = {
    a_favor: 0,
    contra: 0,
    abstencao: 0,
    total: 0
  }

  votos.value.forEach(voto => {
    if (voto.voto === 'a_favor') stats.a_favor++
    else if (voto.voto === 'contra') stats.contra++
    else if (voto.voto === 'abstencao') stats.abstencao++
    stats.total++
  })

  return stats
})

onMounted(async () => {
  await carregarPropostas()
  await carregarVotos()
})

async function carregarPropostas() {
  try {
    const response = await api.get('/propostas')
    propostas.value = response.data
  } catch (err) {
    console.error('Erro ao carregar propostas:', err)
  }
}

async function carregarVotos() {
  try {
    const params = propostaFiltro.value
      ? { proposta_id: propostaFiltro.value }
      : {}

    const response = await api.get('/votos', { params })
    votos.value = response.data
  } catch (err) {
    console.error('Erro ao carregar votos:', err)
  }
}

function formatarData(data: string) {
  if (!data) return '-'
  return new Date(data).toLocaleString('pt-BR')
}

function formatarVoto(voto: string) {
  const votos: Record<string, string> = {
    a_favor: 'A Favor',
    contra: 'Contra',
    abstencao: 'Abstenção'
  }
  return votos[voto] || voto
}

function exportarCSV() {
  const headers = ['Data/Hora', 'Proposta', 'Votante', 'CPF', 'Sindicato', 'Voto']
  const rows = votos.value.map(voto => [
    formatarData(voto.votado_em),
    `${voto.proposta?.numero} - ${voto.proposta?.nome}`,
    voto.nome_votante,
    formatarCPF(voto.cpf_votante),
    voto.nome_sindicato,
    formatarVoto(voto.voto)
  ])

  const csv = [headers, ...rows]
    .map(row => row.map(cell => `"${cell}"`).join(','))
    .join('\n')

  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' })
  const link = document.createElement('a')
  link.href = URL.createObjectURL(blob)
  link.download = `votos_${new Date().toISOString().split('T')[0]}.csv`
  link.click()
}

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
  margin: 0;
}

.filtros {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  background: white;
  padding: 1.5rem;
  border-radius: 8px;
  margin-bottom: 2rem;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.form-group {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-group label {
  font-weight: 600;
}

.form-group select {
  padding: 0.75rem;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 1rem;
}

.btn-exportar {
  background: #2b8a3e;
  color: white;
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  white-space: nowrap;
  margin-left: 1rem;
}

.estatisticas-grid {
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

.stat-card.total {
  background: #e7f5ff;
  border: 2px solid #1351b4;
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

.tabela-container {
  background: white;
  border-radius: 8px;
  padding: 2rem;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

table {
  width: 100%;
  border-collapse: collapse;
}

th, td {
  padding: 1rem;
  text-align: left;
  border-bottom: 1px solid #e9ecef;
}

th {
  background: #f8f9fa;
  font-weight: 600;
  color: #1351b4;
}

.badge-voto {
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.875rem;
  font-weight: 600;
}

.badge-voto.a_favor {
  background: #d3f9d8;
  color: #2b8a3e;
}

.badge-voto.contra {
  background: #ffe3e3;
  color: #c92a2a;
}

.badge-voto.abstencao {
  background: #fff3bf;
  color: #f59f00;
}

.sem-dados {
  text-align: center;
  padding: 3rem;
  color: #666;
}
</style>
