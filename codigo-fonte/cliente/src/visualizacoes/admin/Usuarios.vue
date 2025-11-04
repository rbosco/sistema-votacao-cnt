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
        <button @click="mostrarFormulario = true" class="btn-novo">
          + Novo Usuário
        </button>
      </header>

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
              <th>Nome</th>
              <th>CPF</th>
              <th>Tipo</th>
              <th>Ações</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="usuario in usuarios" :key="usuario.id">
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
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useArmazenamentoAutenticacao } from '@/armazenamentos/autenticacao'
import api from '@/servicos/api'
import { formatarCPF, aplicarMascaraCPF as aplicarMascara, removerFormatacaoCPF } from '@/utilidades/formatadores'
import Paginacao from '@/componentes/Paginacao.vue'
import Switch from '@/componentes/Switch.vue'

const router = useRouter()
const armazenamentoAuth = useArmazenamentoAutenticacao()

const usuarios = ref<any[]>([])
const mostrarFormulario = ref(false)
const editando = ref(false)
const paginacao = ref({
  current_page: 1,
  last_page: 1,
  from: 0,
  to: 0,
  total: 0
})

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

onMounted(() => carregarUsuarios())

async function carregarUsuarios(pagina = 1) {
  try {
    const response = await api.get('/usuarios', {
      params: {
        page: pagina,
        per_page: 10
      }
    })
    usuarios.value = response.data.data
    paginacao.value = {
      current_page: response.data.current_page,
      last_page: response.data.last_page,
      from: response.data.from,
      to: response.data.to,
      total: response.data.total
    }
  } catch (err) {
    console.error('Erro ao carregar usuários:', err)
  }
}

function mudarPagina(pagina: number) {
  carregarUsuarios(pagina)
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

.btn-novo {
  background: #1351b4;
  color: white;
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 4px;
  cursor: pointer;
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
</style>
