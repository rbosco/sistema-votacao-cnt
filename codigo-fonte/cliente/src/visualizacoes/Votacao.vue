<template>
  <div class="votacao-container">
    <!-- Modal de CPF -->
    <div v-if="mostrarModalCPF" class="modal-overlay">
      <div class="modal-cpf">
        <h2>Identificação do Votante</h2>
        <p class="modal-descricao">Por favor, informe seu CPF para continuar com a votação.</p>

        <form @submit.prevent="validarESalvarCPF" class="form-cpf">
          <div class="form-group">
            <label for="cpf-modal">CPF:</label>
            <input
              v-model="cpfModal"
              @input="aplicarMascaraCPFModal"
              type="text"
              id="cpf-modal"
              required
              placeholder="000.000.000-00"
              maxlength="14"
              autofocus
            />
            <span v-if="erroCPF" class="erro-validacao">{{ erroCPF }}</span>
          </div>

          <button type="submit" class="btn-confirmar-cpf" :disabled="validandoCPF">
            {{ validandoCPF ? 'Validando...' : 'Confirmar' }}
          </button>
        </form>
      </div>
    </div>

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
          <h2>{{ proposta.nome }}</h2>

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
            <label for="bancada">Bancada</label>
            <select
              v-model="formulario.bancada_id"
              id="bancada"
              required
              class="select-bancada"
              :disabled="bancadaBloqueada"
            >
              <option :value="null">Selecione sua bancada</option>
              <option
                v-for="bancada in bancadas"
                :key="bancada.id"
                :value="bancada.id"
              >
                {{ bancada.nome }}
              </option>
            </select>
          </div>

          <div class="form-group">
            <label for="cpf">CPF</label>
            <input
              v-model="formulario.cpf_votante"
              @input="aplicarMascaraCPFFormulario"
              type="text"
              id="cpf"
              required
              placeholder="000.000.000-00"
              maxlength="14"
              :readonly="cpfVotante !== ''"
              :class="{ 'campo-readonly': cpfVotante !== '' }"
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
            </div>
          </div>

          <button type="submit" :disabled="enviando || votacaoEncerrada" class="btn-submit">
            {{ enviando ? 'Enviando...' : votacaoEncerrada ? 'Votação Encerrada' : 'CONFIRMAR' }}
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
import { ref, onMounted, onUnmounted, computed, watch } from 'vue'
import api from '@/servicos/api'
import { aplicarMascaraCPF as aplicarMascara, removerFormatacaoCPF, validarCPF } from '@/utilidades/formatadores'

const proposta = ref<any>(null)
const carregando = ref(true)
const erro = ref('')
const enviando = ref(false)
const mensagem = ref('')
const mensagemTipo = ref<'sucesso' | 'erro'>('sucesso')
const configuracoes = ref<any>({})
const tempoRestante = ref(0)
const bancadas = ref<any[]>([])
const bancadaBloqueada = ref(false)

// Modal de CPF
const mostrarModalCPF = ref(false)
const cpfModal = ref('')
const erroCPF = ref('')
const validandoCPF = ref(false)
const cpfVotante = ref('') // CPF do votante logado

const STORAGE_KEY_CPF = 'votacao_cpf_votante'

const temporizadorAtivo = computed(() => {
  return proposta.value?.temporizador_ativo === true
})

const votacaoEncerrada = computed(() => {
  return (temporizadorAtivo.value && tempoRestante.value <= 0) ||
         proposta.value?.atingiu_limite === true
})

const bannerUrl = computed(() => {
  return configuracoes.value.banner_votacao || '/images/banner-cnt.png'
})

const formulario = ref<{
  bancada_id: number | null
  cpf_votante: string
  nome_votante: string
  nome_sindicato: string
  voto: number | null
}>({
  bancada_id: null,
  cpf_votante: '',
  nome_votante: '',
  nome_sindicato: '',
  voto: null
})

// Funções de CPF e localStorage
function recuperarCPFLocalStorage(): string | null {
  try {
    return localStorage.getItem(STORAGE_KEY_CPF)
  } catch (err) {
    console.error('Erro ao recuperar CPF do localStorage:', err)
    return null
  }
}

function salvarCPFLocalStorage(cpf: string): void {
  try {
    localStorage.setItem(STORAGE_KEY_CPF, cpf)
  } catch (err) {
    console.error('Erro ao salvar CPF no localStorage:', err)
  }
}

function aplicarMascaraCPFModal(event: Event): void {
  aplicarMascara(event)
  cpfModal.value = (event.target as HTMLInputElement).value
}

function aplicarMascaraCPFFormulario(event: Event): void {
  aplicarMascara(event)
  formulario.value.cpf_votante = (event.target as HTMLInputElement).value
}

function preencherCPFFormulario(cpf: string): void {
  // Formata o CPF para exibição
  const cpfFormatado = cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4')
  formulario.value.cpf_votante = cpfFormatado
}

function validarESalvarCPF(): void {
  erroCPF.value = ''
  validandoCPF.value = true

  // Valida o CPF
  if (!validarCPF(cpfModal.value)) {
    erroCPF.value = 'CPF inválido. Por favor, verifique o número digitado.'
    validandoCPF.value = false
    return
  }

  // Salva no localStorage
  const cpfLimpo = removerFormatacaoCPF(cpfModal.value)
  salvarCPFLocalStorage(cpfLimpo)
  cpfVotante.value = cpfLimpo

  // Preenche o campo do formulário
  preencherCPFFormulario(cpfLimpo)

  // Fecha a modal
  mostrarModalCPF.value = false
  validandoCPF.value = false
}

function verificarCPF(): void {
  const cpfSalvo = recuperarCPFLocalStorage()
  if (cpfSalvo) {
    cpfVotante.value = cpfSalvo
    preencherCPFFormulario(cpfSalvo)
    mostrarModalCPF.value = false
  } else {
    mostrarModalCPF.value = true
  }
}

// Watch para recuperar CPF quando a proposta mudar
watch(() => proposta.value?.id, (novoId, antigoId) => {
  if (novoId && novoId !== antigoId) {
    // Proposta mudou, recuperar CPF do localStorage
    const cpfSalvo = recuperarCPFLocalStorage()
    if (cpfSalvo) {
      cpfVotante.value = cpfSalvo
      preencherCPFFormulario(cpfSalvo)
    }

    // Limpar mensagens quando proposta mudar
    mensagem.value = ''
  }
})

let intervalo: any = null
let intervaloTemporizador: any = null

onMounted(async () => {
  // Verificar CPF no início
  verificarCPF()

  try {
    // Carregar proposta, configurações e bancadas
    const [propostaResponse, configResponse, bancadasResponse] = await Promise.all([
      api.get('/propostas/ativa'),
      api.get('/configuracoes'),
      api.get('/bancadas')
    ])

    console.log('Resposta da API - proposta ativa:', propostaResponse.data)
    console.log('Tipo da resposta:', typeof propostaResponse.data)
    console.log('É null?', propostaResponse.data === null)
    console.log('É undefined?', propostaResponse.data === undefined)
    console.log('É objeto vazio?', propostaResponse.data && Object.keys(propostaResponse.data).length === 0)

    // Tratar null, undefined ou objeto vazio como "sem proposta"
    if (!propostaResponse.data || Object.keys(propostaResponse.data).length === 0) {
      proposta.value = null
    } else {
      proposta.value = propostaResponse.data
      // Atualizar tempo restante da proposta
      tempoRestante.value = propostaResponse.data.tempo_restante_segundos || 0
    }
    configuracoes.value = configResponse.data
    bancadas.value = bancadasResponse.data.data || []

    // Verificar query parameter 'bancada' na URL
    const urlParams = new URLSearchParams(window.location.search)
    const bancadaParam = urlParams.get('bancada')
    if (bancadaParam) {
      const bancadaId = parseInt(bancadaParam)
      if (bancadaId && bancadas.value.some(b => b.id === bancadaId)) {
        formulario.value.bancada_id = bancadaId
        bancadaBloqueada.value = true // Bloquear select quando vier da URL
      }
    }

    // Temporizador local - decrementa a cada 1 segundo
    intervaloTemporizador = setInterval(() => {
      if (tempoRestante.value > 0 && temporizadorAtivo.value) {
        tempoRestante.value--
      }
    }, 1000) // Atualizar a cada 1 segundo

    // Polling para atualização em tempo real
    // Sincroniza com servidor a cada 10 segundos
    intervalo = setInterval(async () => {
      try {
        // skipLoading: true para não mostrar loading durante polling
        const propostaResponse = await api.get('/propostas/ativa', { skipLoading: true })

        let novaProposta = propostaResponse.data

        // Tratar null, undefined ou objeto vazio como "sem proposta"
        if (!novaProposta || Object.keys(novaProposta).length === 0) {
          novaProposta = null
          proposta.value = null
          tempoRestante.value = 0
        } else {
          // Verificar se o status da proposta mudou
          if (proposta.value && novaProposta &&
              proposta.value.id === novaProposta.id &&
              proposta.value.status !== novaProposta.status) {
            // Status mudou - recarregar página para aplicar nova visualização
            proposta.value = novaProposta
          } else {
            proposta.value = novaProposta
          }

          // Sincronizar tempo restante com servidor (a cada 10 segundos)
          tempoRestante.value = novaProposta.tempo_restante_segundos || 0
        }
      } catch (err) {
        console.error('Erro ao atualizar dados:', err)
      }
    }, 10000) // Sincronizar com servidor a cada 10 segundos
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
  if (intervaloTemporizador) {
    clearInterval(intervaloTemporizador)
  }
})

async function enviarVoto() {
  if (!proposta.value || votacaoEncerrada.value) return

  enviando.value = true
  mensagem.value = ''

  try {
    await api.post('/votar', {
      ...formulario.value,
      cpf_votante: removerFormatacaoCPF(formulario.value.cpf_votante), // Remove formatação antes de enviar
      proposta_id: proposta.value.id
    })

    mensagem.value = 'Voto registrado com sucesso!'
    mensagemTipo.value = 'sucesso'

    // Limpar formulário (mas manter CPF e bancada)
    const cpfAtual = formulario.value.cpf_votante
    const bancadaAtual = formulario.value.bancada_id
    formulario.value = {
      bancada_id: bancadaAtual,
      cpf_votante: cpfAtual,
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

.form-group input[type="text"],
.form-group select {
  padding: 0.75rem;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 1rem;
}

.form-group input[type="text"].campo-readonly {
  background: #f5f5f5;
  cursor: not-allowed;
  color: #666;
}

.select-bancada {
  background: white;
  cursor: pointer;
}

.select-bancada:disabled {
  background: #f5f5f5;
  cursor: not-allowed;
  opacity: 0.7;
}

.select-bancada:focus {
  outline: none;
  border-color: #1351b4;
  box-shadow: 0 0 0 3px rgba(19, 81, 180, 0.1);
}

.botoes-voto {
  display: flex;
  gap: 1.5rem;
  justify-content: center;
  align-items: center;
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

/* Modal de CPF */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.7);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  backdrop-filter: blur(5px);
}

.modal-cpf {
  background: white;
  border-radius: 12px;
  padding: 2.5rem;
  max-width: 500px;
  width: 90%;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
  animation: modalAppear 0.3s ease-out;
}

@keyframes modalAppear {
  from {
    opacity: 0;
    transform: translateY(-20px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

.modal-cpf h2 {
  color: #1351b4;
  margin: 0 0 1rem 0;
  font-size: 1.8rem;
  text-align: center;
}

.modal-descricao {
  text-align: center;
  color: #666;
  margin-bottom: 2rem;
  font-size: 1rem;
}

.form-cpf {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.form-cpf .form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-cpf label {
  font-weight: 600;
  color: #333;
  font-size: 1rem;
}

.form-cpf input {
  padding: 1rem;
  border: 2px solid #ddd;
  border-radius: 8px;
  font-size: 1.1rem;
  transition: all 0.2s;
  text-align: center;
  letter-spacing: 0.5px;
}

.form-cpf input:focus {
  outline: none;
  border-color: #1351b4;
  box-shadow: 0 0 0 4px rgba(19, 81, 180, 0.1);
}

.erro-validacao {
  color: #c92a2a;
  font-size: 0.875rem;
  font-weight: 500;
  padding: 0.5rem;
  background: #ffe3e3;
  border-radius: 4px;
  text-align: center;
  margin-top: 0.5rem;
  display: block;
}

.btn-confirmar-cpf {
  background: #1351b4;
  color: white;
  padding: 1rem 2rem;
  border: none;
  border-radius: 8px;
  font-size: 1.1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.btn-confirmar-cpf:hover:not(:disabled) {
  background: #0d3a7f;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(19, 81, 180, 0.3);
}

.btn-confirmar-cpf:disabled {
  background: #ccc;
  cursor: not-allowed;
  transform: none;
}
</style>
