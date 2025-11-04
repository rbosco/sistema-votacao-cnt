<template>
  <div class="admin-container">
    <nav class="sidebar">
      <div class="logo">
        <h2>Admin CNT</h2>
      </div>
      <ul class="menu">
        <li><router-link to="/admin">Painel</router-link></li>
        <li><router-link to="/admin/propostas">Propostas</router-link></li>
        <li><router-link to="/admin/usuarios" class="active">Usuários</router-link></li>
        <li><router-link to="/admin/votos">Votos</router-link></li>
        <li><router-link to="/admin/configuracao">Configuração</router-link></li>
        <li><a href="#" @click.prevent="sair" class="sair">Sair</a></li>
      </ul>
    </nav>

    <main class="main-content">
      <header class="header">
        <h1>Gerenciar Usuários</h1>
        <div class="botoes-header">
          <button
            v-if="usuariosSelecionados.length > 0"
            @click="excluirSelecionados"
            class="btn-excluir-massa"
          >
            🗑️ Excluir Selecionados ({{ usuariosSelecionados.length }})
          </button>
          <button @click="mostrarFormulario = true" class="btn-novo">
            + Novo Usuário
          </button>
        </div>
      </header>

      <div class="filtros-container">
        <div class="campo-pesquisa">
          <input
            v-model="busca"
            type="text"
            placeholder="🔍 Pesquisar por nome ou CPF..."
            class="input-pesquisa"
          />
          <button v-if="busca" @click="limparPesquisa" class="btn-limpar-pesquisa">
            ✕
          </button>
        </div>
      </div>

      <div v-if="mostrarFormulario" class="modal">
        <div class="modal-content">
          <h2>{{ editando ? 'Editar' : 'Novo' }} Usuário</h2>

          <form @submit.prevent="salvarUsuario" class="form">
            <div class="form-group">
              <label>Nome:</label>
              <input v-model="formulario.nome" type="text" required />
            </div>

            <div class="form-group">
              <label>CPF:</label>
              <input
                v-model="formulario.cpf"
                @input="aplicarMascaraCPF"
                type="text"
                required
                placeholder="000.000.000-00"
                maxlength="14"
              />
            </div>

            <div class="form-group">
              <label>Senha:</label>
              <input v-model="formulario.senha" type="password" :required="!editando" />
              <small v-if="editando">Deixe em branco para manter a senha atual</small>
            </div>

            <div class="form-group-switch">
              <label>Administrador:</label>
              <Switch v-model="formulario.is_admin" />
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
              <th class="th-checkbox">
                <input
                  type="checkbox"
                  :checked="todosSelecionados"
                  @change="toggleSelecionarTodos"
                  title="Selecionar todos"
                />
              </th>
              <th>Nome</th>
              <th>CPF</th>
              <th>Tipo</th>
              <th>Ações</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="usuario in usuarios" :key="usuario.id">
              <td class="td-checkbox">
                <input
                  type="checkbox"
                  :checked="usuariosSelecionados.includes(usuario.id)"
                  @change="toggleSelecao(usuario.id)"
                />
              </td>
              <td>{{ usuario.nome }}</td>
              <td>{{ formatarCPF(usuario.cpf) }}</td>
              <td>
                <Switch
                  v-model="usuario.is_admin"
                  @update:modelValue="(valor) => alternarAdmin(usuario, valor)"
                />
              </td>
              <td>
                <button @click="editar(usuario)" class="btn-editar">Editar</button>
                <button @click="excluir(usuario.id)" class="btn-excluir">Excluir</button>
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
import { formatarCPF, aplicarMascaraCPF as aplicarMascara, removerFormatacaoCPF } from '@/utilidades/formatadores'
import Paginacao from '@/componentes/Paginacao.vue'
import Switch from '@/componentes/Switch.vue'

const router = useRouter()
const armazenamentoAuth = useArmazenamentoAutenticacao()

const todosUsuarios = ref<any[]>([])
const mostrarFormulario = ref(false)
const editando = ref(false)
const busca = ref('')
const paginaAtual = ref(1)
const itensPorPagina = 10
const usuariosSelecionados = ref<number[]>([])

const formulario = ref({
  id: null,
  nome: '',
  cpf: '',
  senha: '',
  is_admin: false
})

function aplicarMascaraCPF(event: Event) {
  aplicarMascara(event)
  // Atualiza o v-model com o valor formatado
  formulario.value.cpf = (event.target as HTMLInputElement).value
}

// Filtrar usuários localmente
const usuariosFiltrados = computed(() => {
  if (!busca.value) {
    return todosUsuarios.value
  }

  const termo = busca.value.toLowerCase()
  return todosUsuarios.value.filter(usuario => {
    const nome = (usuario.nome || '').toLowerCase()
    const cpf = (usuario.cpf || '').toString()
    return nome.includes(termo) || cpf.includes(termo)
  })
})

// Paginação local
const usuarios = computed(() => {
  const inicio = (paginaAtual.value - 1) * itensPorPagina
  const fim = inicio + itensPorPagina
  return usuariosFiltrados.value.slice(inicio, fim)
})

const paginacao = computed(() => {
  const total = usuariosFiltrados.value.length
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

// Verificar se todos da página atual estão selecionados
const todosSelecionados = computed(() => {
  if (usuarios.value.length === 0) return false
  return usuarios.value.every(u => usuariosSelecionados.value.includes(u.id))
})

onMounted(() => carregarUsuarios())

async function carregarUsuarios() {
  try {
    // Carregar TODOS os dados sem paginação
    const response = await api.get('/usuarios')
    todosUsuarios.value = response.data
    paginaAtual.value = 1
  } catch (err) {
    console.error('Erro ao carregar usuários:', err)
  }
}

function mudarPagina(pagina: number) {
  paginaAtual.value = pagina
}

function limparPesquisa() {
  busca.value = ''
  paginaAtual.value = 1
}

// Funções de seleção múltipla
function toggleSelecao(id: number) {
  const index = usuariosSelecionados.value.indexOf(id)
  if (index > -1) {
    usuariosSelecionados.value.splice(index, 1)
  } else {
    usuariosSelecionados.value.push(id)
  }
}

function toggleSelecionarTodos() {
  if (todosSelecionados.value) {
    // Desselecionar todos da página atual
    usuarios.value.forEach(u => {
      const index = usuariosSelecionados.value.indexOf(u.id)
      if (index > -1) {
        usuariosSelecionados.value.splice(index, 1)
      }
    })
  } else {
    // Selecionar todos da página atual
    usuarios.value.forEach(u => {
      if (!usuariosSelecionados.value.includes(u.id)) {
        usuariosSelecionados.value.push(u.id)
      }
    })
  }
}

async function excluirSelecionados() {
  if (usuariosSelecionados.value.length === 0) return

  const confirmacao = confirm(
    `Deseja realmente excluir ${usuariosSelecionados.value.length} usuário(s) selecionado(s)?`
  )

  if (!confirmacao) return

  try {
    // Excluir múltiplos usuários
    await Promise.all(
      usuariosSelecionados.value.map(id => api.delete(`/usuarios/${id}`))
    )

    usuariosSelecionados.value = []
    await carregarUsuarios()
    alert('Usuários excluídos com sucesso!')
  } catch (err) {
    console.error('Erro ao excluir usuários:', err)
    alert('Erro ao excluir um ou mais usuários')
  }
}

function editar(usuario: any) {
  formulario.value = {
    ...usuario,
    cpf: formatarCPF(usuario.cpf),
    senha: ''
  }
  editando.value = true
  mostrarFormulario.value = true
}

async function salvarUsuario() {
  try {
    const dados = {
      ...formulario.value,
      cpf: removerFormatacaoCPF(formulario.value.cpf)
    }
    if (editando.value && !dados.senha) {
      delete dados.senha
    }

    if (editando.value) {
      await api.put(`/usuarios/${dados.id}`, dados)
    } else {
      await api.post('/usuarios', dados)
    }

    await carregarUsuarios()
    fecharFormulario()
  } catch (err) {
    console.error('Erro ao salvar usuário:', err)
  }
}

async function excluir(id: number) {
  if (!confirm('Deseja realmente excluir este usuário?')) return

  try {
    await api.delete(`/usuarios/${id}`)
    await carregarUsuarios()
  } catch (err) {
    console.error('Erro ao excluir usuário:', err)
  }
}

async function alternarAdmin(usuario: any, novoStatus: boolean) {
  try {
    const dados = {
      nome: usuario.nome,
      cpf: removerFormatacaoCPF(usuario.cpf),
      is_admin: novoStatus
    }

    await api.put(`/usuarios/${usuario.id}`, dados)
    await carregarUsuarios()
  } catch (err) {
    console.error('Erro ao alterar status admin:', err)
    // Reverter o estado local em caso de erro
    usuario.is_admin = !novoStatus
    alert('Erro ao alterar status de administrador')
  }
}

function fecharFormulario() {
  mostrarFormulario.value = false
  editando.value = false
  formulario.value = {
    id: null,
    nome: '',
    cpf: '',
    senha: '',
    is_admin: false
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
  gap: 1rem;
  align-items: center;
}

.btn-novo {
  background: #1351b4;
  color: white;
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
  transition: all 0.2s;
}

.btn-novo:hover {
  background: #0d3a7f;
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.btn-excluir-massa {
  background: #c92a2a;
  color: white;
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
  font-size: 0.95rem;
  transition: all 0.2s;
}

.btn-excluir-massa:hover {
  background: #a61e1e;
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(201, 42, 42, 0.3);
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

.th-checkbox,
.td-checkbox {
  width: 40px;
  text-align: center;
  padding: 0.5rem;
}

.th-checkbox input[type="checkbox"],
.td-checkbox input[type="checkbox"] {
  width: 18px;
  height: 18px;
  cursor: pointer;
  accent-color: #1351b4;
}

.badge {
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.875rem;
  font-weight: 600;
}

.badge.admin {
  background: #d3f9d8;
  color: #2b8a3e;
}

.badge.normal {
  background: #e9ecef;
  color: #666;
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
.form-group input[type="password"] {
  padding: 0.75rem;
  border: 1px solid #ddd;
  border-radius: 4px;
}

.form-group small {
  color: #666;
  font-size: 0.875rem;
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
</style>
