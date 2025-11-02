<template>
  <div class="votacao-container">
    <header class="header">
      <img src="/images/banner-cnt.png" alt="Sistema de Votação CNT" class="banner" />
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
            <label for="cpf">CPF</label>
            <input
              v-model="formulario.cpf_votante"
              @input="aplicarMascaraCPF"
              type="text"
              id="cpf"
              required
              placeholder="000.000.000-00"
              maxlength="14"
            />
          </div>

          <div class="form-group">
            <div class="botoes-voto">
              <button
                type="button"
                @click="formulario.voto = 'a_favor'"
                :class="['btn-voto', 'btn-sim', { ativo: formulario.voto === 'a_favor' }]"
              >
                <span class="icone">👍</span>
                <span class="texto">Sim</span>
              </button>
              <button
                type="button"
                @click="formulario.voto = 'contra'"
                :class="['btn-voto', 'btn-nao', { ativo: formulario.voto === 'contra' }]"
              >
                <span class="icone">👎</span>
                <span class="texto">Não</span>
              </button>
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
import { aplicarMascaraCPF as aplicarMascara, removerFormatacaoCPF } from '@/utilidades/formatadores'

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

function aplicarMascaraCPF(event: Event) {
  aplicarMascara(event)
  // Atualiza o v-model com o valor formatado
  formulario.value.cpf_votante = (event.target as HTMLInputElement).value
}

onMounted(async () => {
  try {
    const response = await api.get('/propostas/ativa')
    console.log(response.data);
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
    await api.post('/votar', {
      ...formulario.value,
      cpf_votante: removerFormatacaoCPF(formulario.value.cpf_votante),
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
  padding: 0;
  text-align: center;
}

.header .banner {
  width: 100%;
  height: auto;
  display: block;
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

.botoes-voto {
  display: flex;
  gap: 1.5rem;
  justify-content: center;
}

.btn-voto {
  flex: 1;
  max-width: 200px;
  padding: 1.5rem 2rem;
  border: 3px solid transparent;
  border-radius: 12px;
  font-size: 1.1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  background: white;
}

.btn-voto .icone {
  font-size: 3rem;
  line-height: 1;
}

.btn-voto .texto {
  font-size: 1.2rem;
}

.btn-sim {
  border-color: #d1d5db;
  color: #6b7280;
}

.btn-sim:hover {
  border-color: #22c55e;
  background: #f0fdf4;
}

.btn-sim.ativo {
  border-color: #22c55e;
  background: #22c55e;
  color: white;
}

.btn-nao {
  border-color: #d1d5db;
  color: #6b7280;
}

.btn-nao:hover {
  border-color: #ef4444;
  background: #fef2f2;
}

.btn-nao.ativo {
  border-color: #ef4444;
  background: #ef4444;
  color: white;
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
