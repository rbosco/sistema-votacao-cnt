<template>
  <div class="admin-layout">
    <AdminHeader />
    <div class="container">
      <div class="proposals-page">
        <div class="page-header">
          <h1 class="page-title">Gerenciamento de Propostas</h1>
          <button @click="openCreateModal" class="btn btn-primary">
            Nova Proposta
          </button>
        </div>

        <div v-if="error" class="alert alert-error">{{ error }}</div>
        <div v-if="success" class="alert alert-success">{{ success }}</div>

        <div class="card">
          <table class="table">
            <thead>
              <tr>
                <th>Número</th>
                <th>Nome</th>
                <th>Status</th>
                <th>Votos</th>
                <th>Ações</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="proposal in proposals" :key="proposal.id">
                <td>{{ proposal.number || '-' }}</td>
                <td>{{ proposal.name }}</td>
                <td>
                  <span v-if="proposal.is_active" class="badge badge-success">Ativa</span>
                  <span v-else class="badge badge-danger">Inativa</span>
                </td>
                <td>
                  <div class="vote-count">
                    <span class="vote-total">{{ proposal.votes_count }}</span>
                    <span class="vote-detail">
                      (Sim: {{ proposal.yes_votes_count }} | Não: {{ proposal.no_votes_count }})
                    </span>
                  </div>
                </td>
                <td>
                  <div class="action-buttons">
                    <button
                      v-if="!proposal.is_active"
                      @click="activateProposal(proposal)"
                      class="btn btn-sm btn-success"
                    >
                      Ativar
                    </button>
                    <button
                      v-if="proposal.is_active"
                      @click="deactivateProposal(proposal)"
                      class="btn btn-sm btn-danger"
                    >
                      Desativar
                    </button>
                    <button @click="openEditModal(proposal)" class="btn btn-sm btn-secondary">
                      Editar
                    </button>
                    <button @click="viewResults(proposal)" class="btn btn-sm btn-outline">
                      Ver Resultados
                    </button>
                    <button @click="confirmDelete(proposal)" class="btn btn-sm btn-danger">
                      Excluir
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="proposals.length === 0">
                <td colspan="5" class="text-center">Nenhuma proposta cadastrada</td>
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
          <h3 class="modal-title">{{ modalMode === 'create' ? 'Nova Proposta' : 'Editar Proposta' }}</h3>
        </div>
        <form @submit.prevent="handleSubmit">
          <div class="form-group">
            <label class="form-label">Número da Proposta (opcional)</label>
            <input v-model="form.number" type="text" class="form-control" />
          </div>
          <div class="form-group">
            <label class="form-label">Nome da Proposta *</label>
            <input v-model="form.name" type="text" class="form-control" required />
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
        <p>Deseja realmente excluir a proposta <strong>{{ proposalToDelete?.name }}</strong>?</p>
        <p v-if="proposalToDelete?.votes_count > 0" class="alert alert-error">
          Atenção: Esta proposta possui {{ proposalToDelete.votes_count }} voto(s) registrado(s). Ao excluí-la, todos os votos também serão removidos.
        </p>
        <div class="modal-footer">
          <button @click="showDeleteModal = false" class="btn btn-outline">Cancelar</button>
          <button @click="deleteProposal" class="btn btn-danger">Excluir</button>
        </div>
      </div>
    </div>

    <!-- Modal de Ativação -->
    <div v-if="showActivateModal" class="modal-overlay" @click.self="showActivateModal = false">
      <div class="modal">
        <div class="modal-header">
          <h3 class="modal-title">Confirmar Ativação</h3>
        </div>
        <p>Deseja ativar a proposta <strong>{{ proposalToActivate?.name }}</strong>?</p>
        <p class="alert alert-info">
          Ao ativar esta proposta, todas as outras propostas ativas serão desativadas automaticamente.
        </p>
        <div class="modal-footer">
          <button @click="showActivateModal = false" class="btn btn-outline">Cancelar</button>
          <button @click="confirmActivateProposal" class="btn btn-success">Ativar</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import AdminHeader from '@/components/AdminHeader.vue';

const router = useRouter();
const proposals = ref([]);
const showModal = ref(false);
const showDeleteModal = ref(false);
const showActivateModal = ref(false);
const modalMode = ref('create');
const proposalToDelete = ref(null);
const proposalToActivate = ref(null);
const error = ref('');
const success = ref('');

const form = reactive({
  id: null,
  number: '',
  name: '',
});

const loadProposals = async () => {
  try {
    const response = await axios.get('/proposals');
    proposals.value = response.data;
  } catch (err) {
    error.value = 'Erro ao carregar propostas';
  }
};

const openCreateModal = () => {
  modalMode.value = 'create';
  form.id = null;
  form.number = '';
  form.name = '';
  showModal.value = true;
};

const openEditModal = (proposal) => {
  modalMode.value = 'edit';
  form.id = proposal.id;
  form.number = proposal.number || '';
  form.name = proposal.name;
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
};

const handleSubmit = async () => {
  error.value = '';
  success.value = '';

  try {
    if (modalMode.value === 'create') {
      await axios.post('/proposals', form);
      success.value = 'Proposta criada com sucesso';
    } else {
      await axios.put(`/proposals/${form.id}`, form);
      success.value = 'Proposta atualizada com sucesso';
    }
    closeModal();
    loadProposals();

    setTimeout(() => {
      success.value = '';
    }, 3000);
  } catch (err) {
    error.value = err.response?.data?.message || 'Erro ao salvar proposta';
  }
};

const activateProposal = (proposal) => {
  proposalToActivate.value = proposal;
  showActivateModal.value = true;
};

const confirmActivateProposal = async () => {
  try {
    await axios.post(`/proposals/${proposalToActivate.value.id}/activate`);
    success.value = 'Proposta ativada com sucesso';
    showActivateModal.value = false;
    loadProposals();

    setTimeout(() => {
      success.value = '';
    }, 3000);
  } catch (err) {
    error.value = 'Erro ao ativar proposta';
    showActivateModal.value = false;
  }
};

const deactivateProposal = async (proposal) => {
  try {
    await axios.post(`/proposals/${proposal.id}/deactivate`);
    success.value = 'Proposta desativada com sucesso';
    loadProposals();

    setTimeout(() => {
      success.value = '';
    }, 3000);
  } catch (err) {
    error.value = 'Erro ao desativar proposta';
  }
};

const confirmDelete = (proposal) => {
  proposalToDelete.value = proposal;
  showDeleteModal.value = true;
};

const deleteProposal = async () => {
  try {
    await axios.delete(`/proposals/${proposalToDelete.value.id}`);
    success.value = 'Proposta excluída com sucesso';
    showDeleteModal.value = false;
    loadProposals();

    setTimeout(() => {
      success.value = '';
    }, 3000);
  } catch (err) {
    error.value = 'Erro ao excluir proposta';
    showDeleteModal.value = false;
  }
};

const viewResults = (proposal) => {
  // Navega para a página de resultados com o ID criptografado
  const encryptedId = btoa(proposal.id.toString());
  window.open(`/results/${encryptedId}`, '_blank');
};

onMounted(() => {
  loadProposals();

  // Atualiza a lista a cada 30 segundos
  setInterval(() => {
    loadProposals();
  }, 30000);
});
</script>

<style scoped>
.proposals-page {
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

.vote-count {
  display: flex;
  flex-direction: column;
}

.vote-total {
  font-weight: 600;
  font-size: 1.1rem;
}

.vote-detail {
  font-size: 0.75rem;
  color: var(--gray-60);
}

.action-buttons {
  display: flex;
  gap: var(--spacing-2);
  flex-wrap: wrap;
}

.btn-sm {
  padding: var(--spacing-2) var(--spacing-3);
  font-size: 0.875rem;
}
</style>
