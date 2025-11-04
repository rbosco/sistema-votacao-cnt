<template>
  <div class="votacao-container">
    <header class="header" v-if="!carregando">
      <img :src="bannerUrl" alt="Sistema de Votação CNT" class="banner" />
    </header>

    <main class="main-content">
      <div v-if="carregando" class="loading">
        <p>Carregando proposta...</p>
      </div>

      <div v-else-if="erro" class="error">
        <p>{{ erro }}</p>
      </div>

      <div v-else-if="!proposta" class="proposta-card">
        <div class="status-mensagem nao-iniciada">
          <div class="status-icone">⏳</div>
          <div class="status-texto">Votação Não Iniciada</div>
          <p class="status-descricao">Não há propostas ativas no momento.</p>
        </div>
      </div>

      <div v-else>
        <div class="proposta-card">
          <h2>Proposta {{ proposta.numero }}: {{ proposta.nome }}</h2>

        <!-- Status: Não Iniciada -->
        <div v-if="proposta.status === 'nao_iniciada'" class="status-mensagem nao-iniciada">
          <div class="status-icone">⏳</div>
          <div class="status-texto">Votação Não Iniciada</div>
          <p class="status-descricao">Aguarde o início da votação.</p>
        </div>

        <!-- Status: Encerrada -->
        <div v-else-if="proposta.status === 'encerrada'" class="status-mensagem encerrada">
          <div class="status-icone">🔒</div>
          <div class="status-texto">Votação Encerrada</div>
          <p class="status-descricao">Esta votação foi encerrada.</p>
        </div>

        <!-- Status: Em Votação -->
        <div v-else-if="proposta.status === 'em_votacao'">
          <!-- Temporizador -->
          <div v-if="temporizadorAtivo" class="temporizador-container">
            <div v-if="tempoRestante > 0" class="temporizador ativo">
              <div class="temporizador-icone">⏱️</div>
              <div class="temporizador-info">
                <div class="temporizador-label">Tempo restante para votação:</div>
                <div class="temporizador-tempo">{{ formatarTempo(tempoRestante) }}</div>
              </div>
            </div>
            <div v-else class="temporizador expirado">
              <div class="temporizador-icone">⏰</div>
              <div class="temporizador-info">
                <div class="temporizador-label">Tempo de votação encerrado</div>
              </div>
            </div>
          </div>

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
                @click="formulario.voto = 1"
                :class="['btn-voto', 'btn-sim', { ativo: formulario.voto === 1 }]"
              >
                <span class="icone">👍</span>
                <span class="texto">Sim</span>
              </button>
              <button
                type="button"
                @click="formulario.voto = 0"
                :class="['btn-voto', 'btn-nao', { ativo: formulario.voto === 0 }]"
              >
                <span class="icone">👎</span>
                <span class="texto">Não</span>
              </button>
            </div>
          </div>

          <button type="submit" :disabled="enviando || votacaoEncerrada" class="btn-submit">
            {{ enviando ? 'Enviando...' : votacaoEncerrada ? 'Votação Encerrada' : 'CONFIMAR' }}
          </button>
        </form>

          <div v-if="mensagem" :class="['mensagem', mensagemTipo]">
            {{ mensagem }}
          </div>
        </div>
        <!-- Fim do status em_votacao -->
        </div>
        <!-- Fim do proposta-card -->
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue'
import api from '@/servicos/api'
import { aplicarMascaraCPF as aplicarMascara, removerFormatacaoCPF } from '@/utilidades/formatadores'

const proposta = ref<any>(null)
const carregando = ref(true)
const erro = ref('')
const enviando = ref(false)
const mensagem = ref('')
const mensagemTipo = ref<'sucesso' | 'erro'>('sucesso')
const configuracoes = ref<any>({})
const tempoRestante = ref(0)

const temporizadorAtivo = computed(() => {
  return configuracoes.value.temporizador_ativo === '1' &&
         configuracoes.value.temporizador_ativo_verificado === true
})

const votacaoEncerrada = computed(() => {
  return temporizadorAtivo.value && tempoRestante.value <= 0
})

const bannerUrl = computed(() => {
  return configuracoes.value.banner_votacao || '/images/banner-cnt.png'
})

const formulario = ref<{
  cpf_votante: string
  nome_votante: string
  nome_sindicato: string
  voto: number | null
}>({
  cpf_votante: '',
  nome_votante: '',
  nome_sindicato: '',
  voto: null
})

function aplicarMascaraCPF(event: Event) {
  aplicarMascara(event)
  // Atualiza o v-model com o valor formatado
  formulario.value.cpf_votante = (event.target as HTMLInputElement).value
}

let intervalo: any = null

onMounted(async () => {
  try {
    // Carregar proposta e configurações
    const [propostaResponse, configResponse] = await Promise.all([
      api.get('/propostas/ativa'),
      api.get('/configuracoes')
    ])

    console.log('Resposta da API - proposta ativa:', propostaResponse.data)
    console.log('Tipo da resposta:', typeof propostaResponse.data)
    console.log('É null?', propostaResponse.data === null)
    console.log('É undefined?', propostaResponse.data === undefined)

    proposta.value = propostaResponse.data
    configuracoes.value = configResponse.data
    tempoRestante.value = configResponse.data.tempo_restante_segundos || 0

    // Polling para atualização em tempo real
    // Verifica mudanças a cada 3 segundos
    intervalo = setInterval(async () => {
      try {
        // skipLoading: true para não mostrar loading durante polling
        const [propostaResponse, configResponse] = await Promise.all([
          api.get('/propostas/ativa', { skipLoading: true }),
          api.get('/configuracoes', { skipLoading: true })
        ])

        const novaProposta = propostaResponse.data
        const novasConfiguracoes = configResponse.data

        // Verificar se o status da proposta mudou
        if (proposta.value && novaProposta &&
            proposta.value.id === novaProposta.id &&
            proposta.value.status !== novaProposta.status) {
          // Status mudou - recarregar página para aplicar nova visualização
          proposta.value = novaProposta
        } else {
          proposta.value = novaProposta
        }

        configuracoes.value = novasConfiguracoes
        tempoRestante.value = novasConfiguracoes.tempo_restante_segundos || 0
      } catch (err) {
        console.error('Erro ao atualizar dados:', err)
      }
    }, 3000) // Verificar a cada 3 segundos
  } catch (err: any) {
    erro.value = err.response?.data?.message || 'Erro ao carregar proposta'
  } finally {
    carregando.value = false
  }
})

onUnmounted(() => {
  if (intervalo) {
    clearInterval(intervalo)
  }
})

async function enviarVoto() {
  if (!proposta.value || votacaoEncerrada.value) return

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
      voto: null
    }
  } catch (err: any) {
    mensagem.value = err.response?.data?.message || 'Erro ao registrar voto'
    mensagemTipo.value = 'erro'
  } finally {
    enviando.value = false
  }
}

function formatarTempo(segundos: number): string {
  const horas = Math.floor(segundos / 3600)
  const minutos = Math.floor((segundos % 3600) / 60)
  const segs = segundos % 60

  if (horas > 0) {
    return `${horas}:${String(minutos).padStart(2, '0')}:${String(segs).padStart(2, '0')}`
  } else {
    return `${minutos}:${String(segs).padStart(2, '0')}`
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

.temporizador-container {
  margin-bottom: 2rem;
}

.temporizador {
  background: white;
  border-radius: 8px;
  padding: 1.5rem 2rem;
  display: flex;
  align-items: center;
  gap: 1.5rem;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.temporizador.ativo {
  border-left: 4px solid #1351b4;
}

.temporizador.expirado {
  border-left: 4px solid #c92a2a;
}

.temporizador-icone {
  font-size: 3rem;
  line-height: 1;
}

.temporizador-info {
  flex: 1;
}

.temporizador-label {
  font-size: 0.9rem;
  color: #666;
  margin-bottom: 0.25rem;
}

.temporizador-tempo {
  font-size: 2rem;
  font-weight: bold;
  color: #1351b4;
  font-family: 'Courier New', monospace;
}

.temporizador.expirado .temporizador-label {
  font-size: 1.2rem;
  font-weight: 600;
  color: #c92a2a;
}

.status-mensagem {
  text-align: center;
  padding: 3rem 2rem;
  margin: 2rem 0;
  border-radius: 12px;
  background: #f8f9fa;
}

.status-icone {
  font-size: 5rem;
  margin-bottom: 1rem;
}

.status-texto {
  font-size: 2rem;
  font-weight: bold;
  margin-bottom: 0.5rem;
}

.status-descricao {
  font-size: 1.1rem;
  color: #666;
  margin: 0;
}

.status-mensagem.nao-iniciada {
  background: #fff4e6;
  border: 2px solid #f59f00;
}

.status-mensagem.nao-iniciada .status-texto {
  color: #f59f00;
}

.status-mensagem.encerrada {
  background: #e9ecef;
  border: 2px solid #666;
}

.status-mensagem.encerrada .status-texto {
  color: #666;
}
</style>
