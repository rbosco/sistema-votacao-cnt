<template>
  <div class="admin-container">
    <nav class="sidebar">
      <div class="logo">
        <h2>Admin CNT</h2>
      </div>
      <ul class="menu">
        <li><router-link to="/admin">Painel</router-link></li>
        <li><router-link to="/admin/propostas" class="active">Propostas</router-link></li>
        <li><router-link to="/admin/usuarios">Usuários</router-link></li>
        <li><router-link to="/admin/votos">Votos</router-link></li>
        <li><router-link to="/admin/configuracao">Configuração</router-link></li>
        <li><a href="#" @click.prevent="sair" class="sair">Sair</a></li>
      </ul>
    </nav>

    <main class="main-content">
      <header class="header">
        <h1>Gerenciar Propostas</h1>
        <div class="botoes-header">
          <button @click="downloadModelo" class="btn-download-modelo">
            📥 Baixar Modelo
          </button>
          <label for="arquivo-importacao" class="btn-importar">
            📤 Importar Excel
            <input
              id="arquivo-importacao"
              type="file"
              accept=".xlsx,.xls"
              @change="importarExcel"
              style="display: none"
            />
          </label>
          <button @click="mostrarFormulario = true" class="btn-novo">
            + Nova Proposta
          </button>
        </div>
      </header>

      <div class="filtros-container">
        <div class="campo-pesquisa">
          <input
            v-model="busca"
            type="text"
            placeholder="🔍 Pesquisar por número ou nome..."
            class="input-pesquisa"
          />
          <button v-if="busca" @click="limparPesquisa" class="btn-limpar-pesquisa">
            ✕
          </button>
        </div>
      </div>

      <div v-if="mostrarFormulario" class="modal">
        <div class="modal-content">
          <h2>{{ editando ? 'Editar' : 'Nova' }} Proposta</h2>

          <form @submit.prevent="salvarProposta" class="form">
            <div class="form-group">
              <label>Número:</label>
              <input v-model="formulario.numero" type="text" />
            </div>

            <div class="form-group">
              <label>Nome*:</label>
              <input v-model="formulario.nome" type="text" required />
            </div>

            <div class="form-group">
              <label>Status:</label>
              <select v-model="formulario.status" class="select-status">
                <option value="nao_iniciada">Não Iniciada</option>
                <option value="em_votacao">Em Votação</option>
                <option value="encerrada">Encerrada</option>
              </select>
            </div>

            <div class="form-group-switch">
              <label>Proposta Ativa:</label>
              <Switch v-model="formulario.esta_ativa" />
            </div>

            <div class="form-actions">
              <button type="submit" class="btn-salvar">Salvar</button>
              <button type="button" @click="fecharFormulario" class="btn-cancelar">
                Cancelar
              </button>
            </div>
          </form>
        </div>
      </div>

      <div class="tabela-container">
        <table>
          <thead>
            <tr>
              <th>Número</th>
              <th>Nome</th>
              <th>Status da Votação</th>
              <th>Ativa</th>
              <th>Data de Cadastro</th>
              <th>Ações</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="proposta in propostas" :key="proposta.id">
              <td>{{ proposta.numero }}</td>
              <td>{{ proposta.nome }}</td>
              <td>
                <select
                  :value="proposta.status"
                  @change="(e) => alterarStatusVotacao(proposta, (e.target as HTMLSelectElement).value)"
                  class="select-status-grid"
                  :class="proposta.status"
                >
                  <option value="nao_iniciada">Não Iniciada</option>
                  <option value="em_votacao">Em Votação</option>
                  <option value="encerrada">Encerrada</option>
                </select>
              </td>
              <td>
                <Switch
                  v-model="proposta.esta_ativa"
                  @update:modelValue="(valor) => alternarStatus(proposta, valor)"
                />
              </td>
              <td>{{ formatarData(proposta.created_at) }}</td>
              <td>
                <button @click="editar(proposta)" class="btn-editar">Editar</button>
                <button @click="excluir(proposta.id)" class="btn-excluir">Excluir</button>
              </td>
            </tr>
          </tbody>
        </table>

        <Paginacao :paginacao="paginacao" @mudar-pagina="mudarPagina" />
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useArmazenamentoAutenticacao } from '@/armazenamentos/autenticacao'
import api from '@/servicos/api'
import Paginacao from '@/componentes/Paginacao.vue'
import Switch from '@/componentes/Switch.vue'

const router = useRouter()
const armazenamentoAuth = useArmazenamentoAutenticacao()

const todasPropostas = ref<any[]>([])
const mostrarFormulario = ref(false)
const editando = ref(false)
const busca = ref('')
const paginaAtual = ref(1)
const itensPorPagina = 10

const formulario = ref({
  id: null,
  numero: '',
  nome: '',
  esta_ativa: false,
  status: 'nao_iniciada'
})

// Filtrar propostas localmente
const propostasFiltradas = computed(() => {
  if (!busca.value) {
    return todasPropostas.value
  }

  const termo = busca.value.toLowerCase()
  return todasPropostas.value.filter(proposta => {
    const numero = (proposta.numero || '').toString().toLowerCase()
    const nome = (proposta.nome || '').toLowerCase()
    return numero.includes(termo) || nome.includes(termo)
  })
})

// Paginação local
const propostas = computed(() => {
  const inicio = (paginaAtual.value - 1) * itensPorPagina
  const fim = inicio + itensPorPagina
  return propostasFiltradas.value.slice(inicio, fim)
})

const paginacao = computed(() => {
  const total = propostasFiltradas.value.length
  const totalPaginas = Math.ceil(total / itensPorPagina)
  const inicio = total > 0 ? (paginaAtual.value - 1) * itensPorPagina + 1 : 0
  const fim = Math.min(paginaAtual.value * itensPorPagina, total)

  return {
    current_page: paginaAtual.value,
    last_page: totalPaginas,
    from: inicio,
    to: fim,
    total: total
  }
})

onMounted(() => carregarPropostas())

async function carregarPropostas() {
  try {
    // Carregar TODOS os dados sem paginação
    const response = await api.get('/propostas')
    todasPropostas.value = response.data
    paginaAtual.value = 1
  } catch (err) {
    console.error('Erro ao carregar propostas:', err)
  }
}

function mudarPagina(pagina: number) {
  paginaAtual.value = pagina
}

function limparPesquisa() {
  busca.value = ''
  paginaAtual.value = 1
}

function editar(proposta: any) {
  formulario.value = { ...proposta }
  editando.value = true
  mostrarFormulario.value = true
}

async function salvarProposta() {
  try {
    if (editando.value) {
      await api.put(`/propostas/${formulario.value.id}`, formulario.value)
    } else {
      await api.post('/propostas', formulario.value)
    }
    await carregarPropostas()
    fecharFormulario()
  } catch (err) {
    console.error('Erro ao salvar proposta:', err)
  }
}

async function excluir(id: number) {
  if (!confirm('Deseja realmente excluir esta proposta?')) return

  try {
    await api.delete(`/propostas/${id}`)
    await carregarPropostas()
  } catch (err) {
    console.error('Erro ao excluir proposta:', err)
  }
}

async function alternarStatus(proposta: any, novoStatus: boolean) {
  try {
    await api.put(`/propostas/${proposta.id}`, {
      numero: proposta.numero,
      nome: proposta.nome,
      esta_ativa: novoStatus,
      status: proposta.status
    })
    await carregarPropostas()
  } catch (err) {
    console.error('Erro ao alterar status:', err)
    // Reverter o estado local em caso de erro
    proposta.esta_ativa = !novoStatus
    alert('Erro ao alterar status da proposta')
  }
}

function formatarStatus(status: string) {
  if (status === 'nao_iniciada') return 'Não Iniciada'
  if (status === 'em_votacao') return 'Em Votação'
  if (status === 'encerrada') return 'Encerrada'
  return status
}

function formatarData(data: string) {
  if (!data) return '-'

  const dataObj = new Date(data)

  // Formatar como DD/MM/YYYY HH:MM
  const dia = String(dataObj.getDate()).padStart(2, '0')
  const mes = String(dataObj.getMonth() + 1).padStart(2, '0')
  const ano = dataObj.getFullYear()
  const horas = String(dataObj.getHours()).padStart(2, '0')
  const minutos = String(dataObj.getMinutes()).padStart(2, '0')

  return `${dia}/${mes}/${ano} ${horas}:${minutos}`
}

async function alterarStatusVotacao(proposta: any, novoStatus: string) {
  const statusAnterior = proposta.status

  try {
    await api.patch(`/propostas/${proposta.id}/status`, {
      status: novoStatus
    })
    await carregarPropostas()
  } catch (err: any) {
    console.error('Erro ao alterar status:', err)
    // Reverter o estado local em caso de erro
    proposta.status = statusAnterior
    const mensagem = err.response?.data?.message || 'Erro ao alterar status da proposta'
    alert(mensagem)
  }
}

function fecharFormulario() {
  mostrarFormulario.value = false
  editando.value = false
  formulario.value = {
    id: null,
    numero: '',
    nome: '',
    esta_ativa: false,
    status: 'nao_iniciada'
  }
}

async function downloadModelo() {
  try {
    const response = await api.get('/propostas/modelo-excel/download', {
      responseType: 'blob'
    })

    const blob = new Blob([response.data], {
      type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    })
    const link = document.createElement('a')
    link.href = URL.createObjectURL(blob)
    link.download = 'modelo_importacao_propostas.xlsx'
    link.click()
  } catch (err) {
    console.error('Erro ao baixar modelo:', err)
    alert('Erro ao baixar modelo Excel')
  }
}

async function importarExcel(event: Event) {
  const input = event.target as HTMLInputElement
  const arquivo = input.files?.[0]

  if (!arquivo) return

  try {
    const formData = new FormData()
    formData.append('arquivo', arquivo)

    const response = await api.post('/propostas/importar-excel', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })

    alert(response.data.mensagem)

    if (response.data.erros && response.data.erros.length > 0) {
      console.warn('Erros na importação:', response.data.erros)
    }

    await carregarPropostas()

    // Limpar o input
    input.value = ''
  } catch (err: any) {
    console.error('Erro ao importar Excel:', err)
    alert(err.response?.data?.mensagem || 'Erro ao importar arquivo Excel')
    input.value = ''
  }
}

async function sair() {
  await armazenamentoAuth.sair()
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
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

.header h1 {
  color: #1351b4;
  margin: 0;
}

.botoes-header {
  display: flex;
  gap: 0.5rem;
}

.btn-novo,
.btn-download-modelo,
.btn-importar {
  background: #1351b4;
  color: white;
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 1rem;
  transition: all 0.2s;
}

.btn-novo:hover,
.btn-download-modelo:hover,
.btn-importar:hover {
  background: #0d3a7f;
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.btn-download-modelo {
  background: #2b8a3e;
}

.btn-download-modelo:hover {
  background: #1f6629;
}

.btn-importar {
  background: #f59f00;
  display: inline-block;
}

.btn-importar:hover {
  background: #d68400;
}

.filtros-container {
  margin-bottom: 1.5rem;
}

.campo-pesquisa {
  position: relative;
  max-width: 500px;
}

.input-pesquisa {
  width: 100%;
  padding: 0.75rem 2.5rem 0.75rem 1rem;
  border: 2px solid #e9ecef;
  border-radius: 8px;
  font-size: 1rem;
  transition: all 0.2s;
}

.input-pesquisa:focus {
  outline: none;
  border-color: #1351b4;
  box-shadow: 0 0 0 3px rgba(19, 81, 180, 0.1);
}

.input-pesquisa::placeholder {
  color: #999;
}

.btn-limpar-pesquisa {
  position: absolute;
  right: 0.5rem;
  top: 50%;
  transform: translateY(-50%);
  background: #e9ecef;
  border: none;
  border-radius: 50%;
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #666;
  font-size: 1rem;
  transition: all 0.2s;
}

.btn-limpar-pesquisa:hover {
  background: #dee2e6;
  color: #333;
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

.badge {
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.875rem;
  font-weight: 600;
}

.badge.ativa {
  background: #d3f9d8;
  color: #2b8a3e;
}

.badge.inativa {
  background: #e9ecef;
  color: #666;
}

.badge-status {
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.875rem;
  font-weight: 600;
}

.badge-status.em_votacao {
  background: #e7f5ff;
  color: #1351b4;
}

.badge-status.encerrada {
  background: #e9ecef;
  color: #666;
}

.badge-status.nao_iniciada {
  background: #fff4e6;
  color: #f59f00;
}

.select-status {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 1rem;
}

.select-status-grid {
  padding: 0.5rem;
  border: 2px solid #ddd;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.select-status-grid:hover {
  border-color: #1351b4;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.select-status-grid.nao_iniciada {
  background: #fff4e6;
  color: #f59f00;
  border-color: #f59f00;
}

.select-status-grid.em_votacao {
  background: #e7f5ff;
  color: #1351b4;
  border-color: #1351b4;
}

.select-status-grid.encerrada {
  background: #e9ecef;
  color: #666;
  border-color: #666;
}

.btn-editar, .btn-excluir {
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  margin-right: 0.5rem;
}

.btn-editar {
  background: #f59f00;
  color: white;
}

.btn-excluir {
  background: #c92a2a;
  color: white;
}

.modal {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0,0,0,0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  padding: 2rem;
  border-radius: 8px;
  width: 90%;
  max-width: 500px;
}

.modal-content h2 {
  margin-top: 0;
  color: #1351b4;
}

.form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-group label {
  font-weight: 600;
}

.form-group input[type="text"],
.form-group input[type="number"] {
  padding: 0.75rem;
  border: 1px solid #ddd;
  border-radius: 4px;
}

.form-group-switch {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.form-group-switch label {
  font-weight: 600;
  margin: 0;
}

.form-actions {
  display: flex;
  gap: 1rem;
  margin-top: 1rem;
}

.btn-salvar, .btn-cancelar {
  flex: 1;
  padding: 0.75rem;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

.btn-salvar {
  background: #1351b4;
  color: white;
}

.btn-cancelar {
  background: #e9ecef;
  color: #333;
}
</style>
