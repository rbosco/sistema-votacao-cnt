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
        <li><router-link to="/admin/configuracao">Configuração</router-link></li>
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
          <div class="select-wrapper">
            <input
              type="text"
              v-model="propostaPesquisa"
              @input="filtrarPropostas"
              @focus="mostrarDropdown = true"
              @blur="ocultarDropdown"
              placeholder="Digite para pesquisar ou selecione..."
              class="input-select-pesquisa"
              autocomplete="off"
            />
            <div v-if="mostrarDropdown && propostasFiltradas.length > 0" class="dropdown-options">
              <div
                class="dropdown-option"
                @mousedown="selecionarPropostaDropdown(null)"
              >
                Todas as propostas
              </div>
              <div
                v-for="proposta in propostasFiltradas"
                :key="proposta.id"
                class="dropdown-option"
                @mousedown="selecionarPropostaDropdown(proposta)"
              >
                Proposta {{ proposta.numero }} - {{ proposta.nome }}
              </div>
            </div>
          </div>
        </div>

        <button @click="exportarCSV" class="btn-exportar">
          📥 Exportar CSV
        </button>
      </div>

      <div v-if="propostaSelecionada" class="link-resultado-filtro">
        <a
          :href="`/resultados/${propostaSelecionada.id_criptografado}`"
          target="_blank"
          class="btn-ver-resultado"
        >
          🔗 Ver Resultado da Proposta Selecionada
        </a>
      </div>

      <div class="estatisticas-grid">
        <div class="stat-card sim">
          <h3>Sim</h3>
          <p class="numero">{{ estatisticas.sim || 0 }}</p>
        </div>

        <div class="stat-card nao">
          <h3>Não</h3>
          <p class="numero">{{ estatisticas.nao || 0 }}</p>
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
              <th>Votante (CPF)</th>
              <th>Voto</th>
              <th>Resultado</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="voto in votos" :key="voto.id">
              <td>{{ formatarData(voto.votado_em) }}</td>
              <td>{{ voto.proposta?.numero }} - {{ voto.proposta?.nome }}</td>
              <td>{{ formatarCPF(voto.cpf_votante) }}</td>
              <td>
                <span :class="['badge-voto', obterClasseVoto(voto.voto)]">
                  {{ formatarVoto(voto.voto) }}
                </span>
              </td>
              <td>
                <a
                  v-if="voto.proposta_id_criptografado"
                  :href="`/resultados/${voto.proposta_id_criptografado}`"
                  target="_blank"
                  class="link-resultado"
                >
                  Ver Resultado
                </a>
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
const propostasFiltradas = ref<any[]>([])
const propostaFiltro = ref('')
const propostaPesquisa = ref('')
const mostrarDropdown = ref(false)

const propostaSelecionada = computed(() => {
  if (!propostaFiltro.value) return null
  return propostas.value.find(p => p.id === propostaFiltro.value)
})

const estatisticas = computed(() => {
  const stats = {
    sim: 0,
    nao: 0,
    total: 0
  }

  votos.value.forEach(voto => {
    if (voto.voto === 1) stats.sim++
    else if (voto.voto === 0) stats.nao++
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
    propostasFiltradas.value = response.data
  } catch (err) {
    console.error('Erro ao carregar propostas:', err)
  }
}

function filtrarPropostas() {
  const pesquisa = propostaPesquisa.value.toLowerCase()
  if (!pesquisa) {
    propostasFiltradas.value = propostas.value
  } else {
    propostasFiltradas.value = propostas.value.filter(p =>
      `${p.numero} - ${p.nome}`.toLowerCase().includes(pesquisa)
    )
  }
}

function selecionarPropostaDropdown(proposta: any) {
  if (!proposta) {
    propostaFiltro.value = ''
    propostaPesquisa.value = ''
  } else {
    propostaFiltro.value = proposta.id
    propostaPesquisa.value = `${proposta.numero} - ${proposta.nome}`
  }
  mostrarDropdown.value = false
  carregarVotos()
}

function ocultarDropdown() {
  setTimeout(() => {
    mostrarDropdown.value = false
  }, 200)
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

function formatarVoto(voto: any) {
  // Lidar com diferentes formatos de voto
  if (voto === 1 || voto === true || voto === '1') return 'Sim'
  if (voto === 0 || voto === false || voto === '0') return 'Não'
  return String(voto)
}

function obterClasseVoto(voto: any) {
  // Retorna '1' ou '0' para a classe CSS
  if (voto === 1 || voto === true || voto === '1') return '1'
  if (voto === 0 || voto === false || voto === '0') return '0'
  return String(voto)
}

function exportarCSV() {
  const headers = ['Data/Hora', 'Proposta', 'CPF', 'Voto']
  const rows = votos.value.map(voto => [
    formatarData(voto.votado_em),
    `${voto.proposta?.numero} - ${voto.proposta?.nome}`,
    formatarCPF(voto.cpf_votante),
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

.stat-card.sim {
  background: #d3f9d8;
  border: 2px solid #2b8a3e;
}

.stat-card.nao {
  background: #ffe3e3;
  border: 2px solid #c92a2a;
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

/* For vote = 1 (Sim) */
.badge-voto.1 {
  background: #d3f9d8;
  color: #2b8a3e;
}

/* For vote = 0 (Não) */
.badge-voto.0 {
  background: #ffe3e3;
  color: #c92a2a;
}

.sem-dados {
  text-align: center;
  padding: 3rem;
  color: #666;
}

.link-resultado {
  color: #1351b4;
  text-decoration: none;
  font-weight: 600;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  transition: all 0.2s;
}

.link-resultado:hover {
  background: #e7f5ff;
  text-decoration: underline;
}

.link-resultado-filtro {
  margin: 1rem 0;
  text-align: center;
}

.btn-ver-resultado {
  display: inline-block;
  background: #1351b4;
  color: white;
  padding: 0.75rem 1.5rem;
  border-radius: 4px;
  text-decoration: none;
  font-weight: 600;
  transition: all 0.2s;
}

.btn-ver-resultado:hover {
  background: #0d3a7f;
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.select-wrapper {
  position: relative;
  width: 100%;
}

.input-select-pesquisa {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 1rem;
  cursor: text;
}

.input-select-pesquisa:focus {
  outline: none;
  border-color: #1351b4;
  box-shadow: 0 0 0 3px rgba(19, 81, 180, 0.1);
}

.dropdown-options {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  max-height: 300px;
  overflow-y: auto;
  background: white;
  border: 1px solid #ddd;
  border-top: none;
  border-radius: 0 0 4px 4px;
  box-shadow: 0 4px 6px rgba(0,0,0,0.1);
  z-index: 1000;
  margin-top: 2px;
}

.dropdown-option {
  padding: 0.75rem 1rem;
  cursor: pointer;
  transition: background 0.2s;
}

.dropdown-option:hover {
  background: #f0f4f8;
}

.dropdown-option:first-child {
  font-weight: 600;
  color: #1351b4;
  border-bottom: 1px solid #e9ecef;
}
</style>
