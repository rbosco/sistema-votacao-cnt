<template>
  <div class="votacao-container">
    <header class="header">
      <h1>Sistema de Votação CNT</h1>
      <p>Vote nas propostas ativas</p>
    </header>

    <main class="main-content">
      <div v-if="carregando" class="loading">
        <p>Carregando proposta...</p>
      </div>

      <div v-else-if="erro" class="error">
        <p>{{ erro }}</p>
      </div>

      <div v-else-if="!proposta" class="info">
        <p>Não há propostas ativas no momento.</p>
      </div>

      <div v-else class="proposta-card">
        <h2>Proposta {{ proposta.numero }}: {{ proposta.nome }}</h2>

        <form @submit.prevent="enviarVoto" class="form-voto">
          <div class="form-group">
            <label for="cpf">CPF do Votante:</label>
            <input
              v-model="formulario.cpf_votante"
              type="text"
              id="cpf"
              required
              placeholder="000.000.000-00"
            />
          </div>

          <div class="form-group">
            <label for="nome">Nome Completo:</label>
            <input
              v-model="formulario.nome_votante"
              type="text"
              id="nome"
              required
            />
          </div>

          <div class="form-group">
            <label for="sindicato">Sindicato:</label>
            <input
              v-model="formulario.nome_sindicato"
              type="text"
              id="sindicato"
              required
            />
          </div>

          <div class="form-group">
            <label>Seu Voto:</label>
            <div class="radio-group">
              <label>
                <input type="radio" v-model="formulario.voto" value="a_favor" required />
                <span>A Favor</span>
              </label>
              <label>
                <input type="radio" v-model="formulario.voto" value="contra" required />
                <span>Contra</span>
              </label>
              <label>
                <input type="radio" v-model="formulario.voto" value="abstencao" required />
                <span>Abstenção</span>
              </label>
            </div>
          </div>

          <button type="submit" :disabled="enviando" class="btn-submit">
            {{ enviando ? 'Enviando...' : 'Confirmar Voto' }}
          </button>
        </form>

        <div v-if="mensagem" :class="['mensagem', mensagemTipo]">
          {{ mensagem }}
        </div>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import api from '@/servicos/api'

const proposta = ref<any>(null)
const carregando = ref(true)
const erro = ref('')
const enviando = ref(false)
const mensagem = ref('')
const mensagemTipo = ref<'sucesso' | 'erro'>('sucesso')

const formulario = ref({
  cpf_votante: '',
  nome_votante: '',
  nome_sindicato: '',
  voto: ''
})

onMounted(async () => {
  try {
    const response = await api.get('/api/propostas/ativa')
    proposta.value = response.data
  } catch (err: any) {
    erro.value = err.response?.data?.message || 'Erro ao carregar proposta'
  } finally {
    carregando.value = false
  }
})

async function enviarVoto() {
  if (!proposta.value) return

  enviando.value = true
  mensagem.value = ''

  try {
    await api.post('/api/votar', {
      ...formulario.value,
      proposta_id: proposta.value.id
    })

    mensagem.value = 'Voto registrado com sucesso!'
    mensagemTipo.value = 'sucesso'

    // Limpar formulário
    formulario.value = {
      cpf_votante: '',
      nome_votante: '',
      nome_sindicato: '',
      voto: ''
    }
  } catch (err: any) {
    mensagem.value = err.response?.data?.message || 'Erro ao registrar voto'
    mensagemTipo.value = 'erro'
  } finally {
    enviando.value = false
  }
}
</script>

<style scoped>
.votacao-container {
  min-height: 100vh;
  background: #f5f5f5;
}

.header {
  background: #1351b4;
  color: white;
  padding: 2rem;
  text-align: center;
}

.header h1 {
  margin: 0 0 0.5rem 0;
}

.main-content {
  max-width: 800px;
  margin: 2rem auto;
  padding: 0 1rem;
}

.proposta-card {
  background: white;
  border-radius: 8px;
  padding: 2rem;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.proposta-card h2 {
  color: #1351b4;
  margin-bottom: 2rem;
}

.form-voto {
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

.form-group input[type="text"] {
  padding: 0.75rem;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 1rem;
}

.radio-group {
  display: flex;
  gap: 1.5rem;
}

.radio-group label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
}

.btn-submit {
  background: #1351b4;
  color: white;
  padding: 1rem 2rem;
  border: none;
  border-radius: 4px;
  font-size: 1.1rem;
  cursor: pointer;
  transition: background 0.2s;
}

.btn-submit:hover:not(:disabled) {
  background: #0c3d8d;
}

.btn-submit:disabled {
  background: #ccc;
  cursor: not-allowed;
}

.loading, .error, .info {
  text-align: center;
  padding: 2rem;
  background: white;
  border-radius: 8px;
}

.error {
  color: #c92a2a;
}

.mensagem {
  margin-top: 1rem;
  padding: 1rem;
  border-radius: 4px;
  text-align: center;
}

.mensagem.sucesso {
  background: #d3f9d8;
  color: #2b8a3e;
}

.mensagem.erro {
  background: #ffe3e3;
  color: #c92a2a;
}
</style>
