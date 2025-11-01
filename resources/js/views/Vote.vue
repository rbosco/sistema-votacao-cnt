<template>
  <div class="vote-page">
    <div class="govbr-header">
      <div class="container">
        <div class="logo">
          <img src="/images/logo-cnt.png" alt="Logo CNT" class="logo-cnt" />
          <h1>Sistema de Votação CNT</h1>
        </div>
      </div>
    </div>

    <div class="container">
      <div class="vote-container">
        <!-- Proposta Ativa -->
        <div v-if="activeProposal" class="card">
          <div class="card-header">
            <h2 class="card-title">Proposta em Votação</h2>
          </div>

          <div class="proposal-info">
            <div v-if="activeProposal.number" class="proposal-number">
              Proposta nº {{ activeProposal.number }}
            </div>
            <h3 class="proposal-name">{{ activeProposal.name }}</h3>
          </div>

          <form @submit.prevent="handleVote">
            <div v-if="error" class="alert alert-error">
              {{ error }}
            </div>

            <div v-if="success" class="alert alert-success">
              {{ success }}
            </div>

            <div class="form-group">
              <label for="cpf" class="form-label">CPF do Delegado</label>
              <input
                id="cpf"
                v-model="form.cpf"
                type="text"
                class="form-control"
                :class="{ error: errors.cpf }"
                placeholder="Apenas números"
                maxlength="11"
                @input="validateCPF"
                :disabled="voted"
              />
              <div v-if="errors.cpf" class="form-error">{{ errors.cpf }}</div>
            </div>

            <div class="form-group">
              <label for="name" class="form-label">Nome Completo</label>
              <input
                id="name"
                v-model="form.name"
                type="text"
                class="form-control"
                :class="{ error: errors.name }"
                placeholder="Digite seu nome completo"
                :disabled="voted"
              />
              <div v-if="errors.name" class="form-error">{{ errors.name }}</div>
            </div>

            <div class="form-group">
              <label for="union_name" class="form-label">Sindicato</label>
              <input
                id="union_name"
                v-model="form.union_name"
                type="text"
                class="form-control"
                :class="{ error: errors.union_name }"
                placeholder="Digite o nome do sindicato"
                :disabled="voted"
              />
              <div v-if="errors.union_name" class="form-error">{{ errors.union_name }}</div>
            </div>

            <div class="vote-buttons">
              <button
                type="button"
                class="btn btn-success btn-vote"
                @click="confirmVote(true)"
                :disabled="loading || voted"
              >
                SIM
              </button>
              <button
                type="button"
                class="btn btn-danger btn-vote"
                @click="confirmVote(false)"
                :disabled="loading || voted"
              >
                NÃO
              </button>
            </div>
          </form>
        </div>

        <!-- Nenhuma Proposta Ativa -->
        <div v-else-if="!loading" class="card text-center">
          <div class="alert alert-info">
            <h3>Nenhuma votação em andamento</h3>
            <p>Aguarde a abertura de uma nova votação.</p>
          </div>
        </div>

        <!-- Loading -->
        <div v-if="loading && !activeProposal" class="card text-center">
          <p>Carregando...</p>
        </div>

        <div class="text-center mt-4">
          <a href="/login" class="btn btn-outline">Acesso Administrativo</a>
        </div>
      </div>
    </div>

    <!-- Modal de Confirmação -->
    <div v-if="showConfirmModal" class="modal-overlay" @click.self="showConfirmModal = false">
      <div class="modal">
        <div class="modal-header">
          <h3 class="modal-title">Confirmar Voto</h3>
        </div>
        <div class="modal-body">
          <p><strong>Proposta:</strong> {{ activeProposal?.name }}</p>
          <p><strong>Seu voto:</strong> <span :class="selectedVote ? 'text-success' : 'text-danger'">{{ selectedVote ? 'SIM' : 'NÃO' }}</span></p>
          <p class="mt-4"><strong>Atenção:</strong> Após confirmar, você não poderá alterar seu voto.</p>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline" @click="showConfirmModal = false">Cancelar</button>
          <button class="btn btn-primary" @click="submitVote">Confirmar Voto</button>
        </div>
      </div>
    </div>

    <!-- Modal de Votação Encerrada -->
    <div v-if="showClosedModal" class="modal-overlay" @click.self="showClosedModal = false">
      <div class="modal">
        <div class="modal-header">
          <h3 class="modal-title">Votação Encerrada</h3>
        </div>
        <div class="modal-body">
          <p>A votação para esta proposta foi encerrada.</p>
          <p>Não é mais possível registrar votos.</p>
        </div>
        <div class="modal-footer">
          <button class="btn btn-primary" @click="handleClosedModal">OK</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';

const activeProposal = ref(null);
const loading = ref(false);
const voted = ref(false);
const error = ref('');
const success = ref('');
const showConfirmModal = ref(false);
const showClosedModal = ref(false);
const selectedVote = ref(null);

const form = reactive({
  cpf: '',
  name: '',
  union_name: '',
});

const errors = reactive({
  cpf: '',
  name: '',
  union_name: '',
});

const validateCPF = () => {
  form.cpf = form.cpf.replace(/\D/g, '');
  errors.cpf = form.cpf.length !== 11 && form.cpf.length > 0 ? 'CPF deve conter 11 dígitos' : '';
};

const loadActiveProposal = async () => {
  loading.value = true;
  try {
    const response = await axios.get('/proposals/active');
    activeProposal.value = response.data;
  } catch (err) {
    console.error('Erro ao carregar proposta ativa:', err);
  } finally {
    loading.value = false;
  }
};

const confirmVote = (vote) => {
  errors.cpf = '';
  errors.name = '';
  errors.union_name = '';
  error.value = '';

  // Validações
  if (!form.cpf) {
    errors.cpf = 'CPF é obrigatório';
    return;
  }

  if (form.cpf.length !== 11) {
    errors.cpf = 'CPF deve conter 11 dígitos';
    return;
  }

  if (!form.name) {
    errors.name = 'Nome é obrigatório';
    return;
  }

  if (!form.union_name) {
    errors.union_name = 'Sindicato é obrigatório';
    return;
  }

  selectedVote.value = vote;
  showConfirmModal.value = true;
};

const submitVote = async () => {
  loading.value = true;
  showConfirmModal.value = false;

  try {
    await axios.post('/vote', {
      cpf: form.cpf,
      name: form.name,
      union_name: form.union_name,
      vote: selectedVote.value,
    });

    success.value = 'Voto registrado com sucesso!';
    voted.value = true;

    // Limpa o formulário após 3 segundos
    setTimeout(() => {
      form.cpf = '';
      form.name = '';
      form.union_name = '';
      success.value = '';
      voted.value = false;
    }, 3000);

  } catch (err) {
    if (err.response?.data?.status === 'closed') {
      showClosedModal.value = true;
      loadActiveProposal(); // Recarrega para verificar se a proposta foi desativada
    } else if (err.response?.data?.errors) {
      const apiErrors = err.response.data.errors;
      if (apiErrors.cpf) errors.cpf = apiErrors.cpf[0];
      if (apiErrors.name) errors.name = apiErrors.name[0];
      if (apiErrors.union_name) errors.union_name = apiErrors.union_name[0];
    } else {
      error.value = err.response?.data?.message || 'Erro ao registrar voto. Tente novamente.';
    }
  } finally {
    loading.value = false;
  }
};

const handleClosedModal = () => {
  showClosedModal.value = false;
};

const handleVote = () => {
  // Previne o submit do formulário
};

onMounted(() => {
  loadActiveProposal();

  // Atualiza a proposta ativa a cada 30 segundos
  setInterval(() => {
    loadActiveProposal();
  }, 30000);
});
</script>

<style scoped>
.vote-page {
  min-height: 100vh;
  background-color: var(--gray-5);
}

.vote-container {
  max-width: 600px;
  margin: 4rem auto;
  padding: 0 var(--spacing-4);
}

.logo-cnt {
  max-height: 60px;
  height: auto;
}

.proposal-info {
  margin-bottom: var(--spacing-5);
  padding: var(--spacing-4);
  background-color: var(--yellow-vivid-10);
  border-radius: var(--border-radius-md);
}

.proposal-number {
  color: var(--blue-warm-vivid-60);
  font-weight: 600;
  font-size: 0.875rem;
  text-transform: uppercase;
  margin-bottom: var(--spacing-2);
}

.proposal-name {
  color: var(--blue-warm-vivid-50);
  font-size: 1.5rem;
  font-weight: 600;
  margin: 0;
}

.vote-buttons {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--spacing-4);
  margin-top: var(--spacing-5);
}

.btn-vote {
  padding: var(--spacing-5);
  font-size: 1.5rem;
  font-weight: 700;
}

a {
  text-decoration: none;
}

.text-success {
  color: var(--green-cool-vivid-50);
  font-weight: 600;
}

.text-danger {
  color: var(--red-vivid-50);
  font-weight: 600;
}

.modal-body {
  font-size: 1rem;
  line-height: 1.6;
}

.modal-body p {
  margin-bottom: var(--spacing-3);
}
</style>
