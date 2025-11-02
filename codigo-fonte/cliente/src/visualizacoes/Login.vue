<template>
  <div class="login-container">
    <div class="login-card">
      <h1>Login Administrativo</h1>
      <p>Sistema de Votação CNT</p>

      <form @submit.prevent="fazerLogin" class="login-form">
        <div class="form-group">
          <label for="cpf">CPF:</label>
          <input
            v-model="formulario.cpf"
            @input="aplicarMascaraCPF"
            type="text"
            id="cpf"
            required
            placeholder="000.000.000-00"
            maxlength="14"
            autocomplete="username"
          />
        </div>

        <div class="form-group">
          <label for="senha">Senha:</label>
          <input
            v-model="formulario.senha"
            type="password"
            id="senha"
            required
            autocomplete="current-password"
          />
        </div>

        <button type="submit" :disabled="enviando" class="btn-login">
          {{ enviando ? 'Entrando...' : 'Entrar' }}
        </button>
      </form>

      <div v-if="erro" class="erro">
        {{ erro }}
      </div>

      <div class="voltar">
        <router-link to="/votacao">← Voltar para votação</router-link>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useArmazenamentoAutenticacao } from '@/armazenamentos/autenticacao'
import { aplicarMascaraCPF as aplicarMascara, removerFormatacaoCPF } from '@/utilidades/formatadores'

const router = useRouter()
const armazenamentoAuth = useArmazenamentoAutenticacao()

const formulario = ref({
  cpf: '',
  senha: ''
})

const enviando = ref(false)
const erro = ref('')

function aplicarMascaraCPF(event: Event) {
  aplicarMascara(event)
  // Atualiza o v-model com o valor formatado
  formulario.value.cpf = (event.target as HTMLInputElement).value
}

async function fazerLogin() {
  enviando.value = true
  erro.value = ''

  try {
    await armazenamentoAuth.entrar({
      cpf: removerFormatacaoCPF(formulario.value.cpf),
      senha: formulario.value.senha
    })

    router.push('/admin')
  } catch (err: any) {
    erro.value = err.response?.data?.message || 'Credenciais inválidas'
  } finally {
    enviando.value = false
  }
}
</script>

<style scoped>
.login-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #1351b4 0%, #0c3d8d 100%);
  padding: 1rem;
}

.login-card {
  background: white;
  border-radius: 8px;
  padding: 3rem;
  width: 100%;
  max-width: 400px;
  box-shadow: 0 10px 40px rgba(0,0,0,0.2);
}

.login-card h1 {
  color: #1351b4;
  margin: 0 0 0.5rem 0;
  text-align: center;
}

.login-card > p {
  text-align: center;
  color: #666;
  margin-bottom: 2rem;
}

.login-form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-group label {
  font-weight: 600;
  color: #333;
}

.form-group input {
  padding: 0.75rem;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 1rem;
}

.btn-login {
  background: #1351b4;
  color: white;
  padding: 1rem;
  border: none;
  border-radius: 4px;
  font-size: 1.1rem;
  cursor: pointer;
  transition: background 0.2s;
  margin-top: 0.5rem;
}

.btn-login:hover:not(:disabled) {
  background: #0c3d8d;
}

.btn-login:disabled {
  background: #ccc;
  cursor: not-allowed;
}

.erro {
  background: #ffe3e3;
  color: #c92a2a;
  padding: 1rem;
  border-radius: 4px;
  text-align: center;
  margin-top: 1rem;
}

.voltar {
  text-align: center;
  margin-top: 1.5rem;
}

.voltar a {
  color: #1351b4;
  text-decoration: none;
}

.voltar a:hover {
  text-decoration: underline;
}
</style>
