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
        <li><a href="#" @click.prevent="sair" class="sair">Sair</a></li>
      </ul>
    </nav>

    <main class="main-content">
      <header class="header">
        <h1>Gerenciar Propostas</h1>
        <button @click="mostrarFormulario = true" class="btn-novo">
          + Nova Proposta
        </button>
      </header>

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
              <label>
                <input v-model="formulario.esta_ativa" type="checkbox" />
                Proposta Ativa
              </label>
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
              <th>Status</th>
              <th>Ações</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="proposta in propostas" :key="proposta.id">
              <td>{{ proposta.numero }}</td>
              <td>{{ proposta.nome }}</td>
              <td>
                <span :class="['badge', proposta.esta_ativa ? 'ativa' : 'inativa']">
                  {{ proposta.esta_ativa ? 'Ativa' : 'Inativa' }}
                </span>
              </td>
              <td>
                <button @click="editar(proposta)" class="btn-editar">Editar</button>
                <button @click="excluir(proposta.id)" class="btn-excluir">Excluir</button>
              </td>
            </tr>
          </tbody>
        </table>
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

const propostas = ref<any[]>([])
const mostrarFormulario = ref(false)
const editando = ref(false)

const formulario = ref({
  id: null,
  numero: '',
  nome: '',
  esta_ativa: false
})

onMounted(carregarPropostas)

async function carregarPropostas() {
  try {
    const response = await api.get('/propostas')
    propostas.value = response.data
  } catch (err) {
    console.error('Erro ao carregar propostas:', err)
  }
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

function fecharFormulario() {
  mostrarFormulario.value = false
  editando.value = false
  formulario.value = {
    id: null,
    numero: '',
    nome: '',
    esta_ativa: false
  }
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

.badge.ativa {
  background: #d3f9d8;
  color: #2b8a3e;
}

.badge.inativa {
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
.form-group input[type="number"] {
  padding: 0.75rem;
  border: 1px solid #ddd;
  border-radius: 4px;
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
