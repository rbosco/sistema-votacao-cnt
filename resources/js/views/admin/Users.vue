<template>
  <div class="admin-layout">
    <AdminHeader />
    <div class="container">
      <div class="users-page">
        <div class="page-header">
          <h1 class="page-title">Gerenciamento de Usuários</h1>
          <button @click="openCreateModal" class="btn btn-primary">
            Novo Usuário
          </button>
        </div>

        <div v-if="error" class="alert alert-error">{{ error }}</div>
        <div v-if="success" class="alert alert-success">{{ success }}</div>

        <div class="card">
          <table class="table">
            <thead>
              <tr>
                <th>Nome</th>
                <th>CPF</th>
                <th>Admin</th>
                <th>Ações</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="user in users" :key="user.id">
                <td>{{ user.name }}</td>
                <td>{{ formatCPF(user.cpf) }}</td>
                <td>
                  <span v-if="user.is_admin" class="badge badge-success">Sim</span>
                  <span v-else class="badge badge-info">Não</span>
                </td>
                <td>
                  <button @click="openEditModal(user)" class="btn btn-sm btn-secondary">Editar</button>
                  <button @click="confirmDelete(user)" class="btn btn-sm btn-danger">Excluir</button>
                </td>
              </tr>
              <tr v-if="users.length === 0">
                <td colspan="4" class="text-center">Nenhum usuário cadastrado</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Modal de Criar/Editar -->
    <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal">
        <div class="modal-header">
          <h3 class="modal-title">{{ modalMode === 'create' ? 'Novo Usuário' : 'Editar Usuário' }}</h3>
        </div>
        <form @submit.prevent="handleSubmit">
          <div class="form-group">
            <label class="form-label">Nome</label>
            <input v-model="form.name" type="text" class="form-control" required />
          </div>
          <div class="form-group">
            <label class="form-label">CPF</label>
            <input
              v-model="form.cpf"
              type="text"
              class="form-control"
              maxlength="11"
              @input="validateCPF"
              required
            />
          </div>
          <div class="form-group">
            <label class="form-label">Senha{{ modalMode === 'edit' ? ' (deixe em branco para manter)' : '' }}</label>
            <input v-model="form.password" type="password" class="form-control" :required="modalMode === 'create'" />
          </div>
          <div class="form-group">
            <label class="checkbox-label">
              <input v-model="form.is_admin" type="checkbox" />
              <span>Usuário Administrador</span>
            </label>
          </div>
          <div class="modal-footer">
            <button type="button" @click="closeModal" class="btn btn-outline">Cancelar</button>
            <button type="submit" class="btn btn-primary">Salvar</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal de Confirmação de Exclusão -->
    <div v-if="showDeleteModal" class="modal-overlay" @click.self="showDeleteModal = false">
      <div class="modal">
        <div class="modal-header">
          <h3 class="modal-title">Confirmar Exclusão</h3>
        </div>
        <p>Deseja realmente excluir o usuário <strong>{{ userToDelete?.name }}</strong>?</p>
        <div class="modal-footer">
          <button @click="showDeleteModal = false" class="btn btn-outline">Cancelar</button>
          <button @click="deleteUser" class="btn btn-danger">Excluir</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';
import AdminHeader from '@/components/AdminHeader.vue';

const users = ref([]);
const showModal = ref(false);
const showDeleteModal = ref(false);
const modalMode = ref('create');
const userToDelete = ref(null);
const error = ref('');
const success = ref('');

const form = reactive({
  id: null,
  name: '',
  cpf: '',
  password: '',
  is_admin: false,
});

const loadUsers = async () => {
  try {
    const response = await axios.get('/users');
    users.value = response.data;
  } catch (err) {
    error.value = 'Erro ao carregar usuários';
  }
};

const openCreateModal = () => {
  modalMode.value = 'create';
  form.id = null;
  form.name = '';
  form.cpf = '';
  form.password = '';
  form.is_admin = false;
  showModal.value = true;
};

const openEditModal = (user) => {
  modalMode.value = 'edit';
  form.id = user.id;
  form.name = user.name;
  form.cpf = user.cpf;
  form.password = '';
  form.is_admin = user.is_admin;
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
};

const validateCPF = () => {
  form.cpf = form.cpf.replace(/\D/g, '');
};

const handleSubmit = async () => {
  error.value = '';
  success.value = '';

  try {
    if (modalMode.value === 'create') {
      await axios.post('/users', form);
      success.value = 'Usuário criado com sucesso';
    } else {
      await axios.put(`/users/${form.id}`, form);
      success.value = 'Usuário atualizado com sucesso';
    }
    closeModal();
    loadUsers();

    setTimeout(() => {
      success.value = '';
    }, 3000);
  } catch (err) {
    error.value = err.response?.data?.message || 'Erro ao salvar usuário';
  }
};

const confirmDelete = (user) => {
  userToDelete.value = user;
  showDeleteModal.value = true;
};

const deleteUser = async () => {
  try {
    await axios.delete(`/users/${userToDelete.value.id}`);
    success.value = 'Usuário excluído com sucesso';
    showDeleteModal.value = false;
    loadUsers();

    setTimeout(() => {
      success.value = '';
    }, 3000);
  } catch (err) {
    error.value = 'Erro ao excluir usuário';
    showDeleteModal.value = false;
  }
};

const formatCPF = (cpf) => {
  return cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
};

onMounted(() => {
  loadUsers();
});
</script>

<style scoped>
.users-page {
  padding: var(--spacing-6) 0;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: var(--spacing-6);
}

.page-title {
  font-size: 2rem;
  font-weight: 700;
  color: var(--blue-warm-vivid-60);
  margin: 0;
}

.btn-sm {
  padding: var(--spacing-2) var(--spacing-3);
  font-size: 0.875rem;
  margin-right: var(--spacing-2);
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: var(--spacing-2);
  cursor: pointer;
}

.checkbox-label input[type="checkbox"] {
  width: 18px;
  height: 18px;
  cursor: pointer;
}
</style>
