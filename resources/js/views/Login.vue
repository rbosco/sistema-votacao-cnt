<template>
  <div class="login-page">
    <div class="govbr-header">
      <div class="container">
        <div class="logo">
          <img src="/images/logo-cnt.png" alt="Logo CNT" class="logo-cnt" />
          <h1>Sistema de Votação CNT</h1>
        </div>
      </div>
    </div>

    <div class="container">
      <div class="login-container">
        <div class="card">
          <div class="card-header">
            <h2 class="card-title text-center">Acesso Administrativo</h2>
          </div>

          <form @submit.prevent="handleLogin">
            <div v-if="error" class="alert alert-error">
              {{ error }}
            </div>

            <div class="form-group">
              <label for="cpf" class="form-label">CPF</label>
              <input
                id="cpf"
                v-model="form.cpf"
                type="text"
                class="form-control"
                :class="{ error: errors.cpf }"
                placeholder="Apenas números"
                maxlength="11"
                @input="validateCPF"
              />
              <div v-if="errors.cpf" class="form-error">{{ errors.cpf }}</div>
            </div>

            <div class="form-group">
              <label for="password" class="form-label">Senha</label>
              <input
                id="password"
                v-model="form.password"
                type="password"
                class="form-control"
                :class="{ error: errors.password }"
                placeholder="Digite sua senha"
              />
              <div v-if="errors.password" class="form-error">{{ errors.password }}</div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;" :disabled="loading">
              {{ loading ? 'Entrando...' : 'Entrar' }}
            </button>
          </form>
        </div>

        <div class="text-center mt-4">
          <a href="/" class="btn btn-outline">Voltar para Votação</a>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const router = useRouter();
const authStore = useAuthStore();

const form = reactive({
  cpf: '',
  password: '',
});

const errors = reactive({
  cpf: '',
  password: '',
});

const error = ref('');
const loading = ref(false);

const validateCPF = () => {
  form.cpf = form.cpf.replace(/\D/g, '');
  errors.cpf = form.cpf.length !== 11 && form.cpf.length > 0 ? 'CPF deve conter 11 dígitos' : '';
};

const handleLogin = async () => {
  errors.cpf = '';
  errors.password = '';
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

  if (!form.password) {
    errors.password = 'Senha é obrigatória';
    return;
  }

  loading.value = true;

  try {
    await authStore.login(form);
    router.push('/admin');
  } catch (err) {
    if (err.response?.data?.errors) {
      const apiErrors = err.response.data.errors;
      if (apiErrors.cpf) errors.cpf = apiErrors.cpf[0];
      if (apiErrors.password) errors.password = apiErrors.password[0];
    } else {
      error.value = err.response?.data?.message || 'Erro ao fazer login. Verifique suas credenciais.';
    }
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
.login-page {
  min-height: 100vh;
  background-color: var(--gray-5);
}

.login-container {
  max-width: 450px;
  margin: 4rem auto;
  padding: 0 var(--spacing-4);
}

.logo-cnt {
  max-height: 60px;
  height: auto;
}

a {
  text-decoration: none;
}
</style>
