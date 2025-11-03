<template>
  <div class="admin-container">
    <nav class="sidebar">
      <div class="logo">
        <h2>Admin CNT</h2>
      </div>
      <ul class="menu">
        <li><router-link to="/admin">Painel</router-link></li>
        <li><router-link to="/admin/propostas">Propostas</router-link></li>
        <li><router-link to="/admin/usuarios">Usuários</router-link></li>
        <li><router-link to="/admin/votos">Votos</router-link></li>
        <li><router-link to="/admin/configuracao" class="active">Configuração</router-link></li>
        <li><a href="#" @click.prevent="sair" class="sair">Sair</a></li>
      </ul>
    </nav>

    <main class="main-content">
      <header class="header">
        <h1>Configurações do Sistema</h1>
      </header>

      <div class="config-container">
        <!-- Banner da Votação -->
        <section class="config-section">
          <h2>Banner da Página de Votação</h2>
          <div class="banner-preview" v-if="configuracoes.banner_votacao">
            <img :src="configuracoes.banner_votacao" alt="Banner atual" />
          </div>
          <div class="form-group">
            <label>Upload de novo banner:</label>
            <input type="file" @change="selecionarBanner" accept="image/*" ref="bannerInput" />
            <button @click="uploadBanner" :disabled="!bannerArquivo || uploading" class="btn-primary">
              {{ uploading ? 'Enviando...' : 'Enviar Banner' }}
            </button>
          </div>
          <p class="help-text">Formatos aceitos: JPG, PNG, GIF. Tamanho máximo: 5MB</p>
        </section>

        <!-- Temporizador de Votação -->
        <section class="config-section">
          <h2>Temporizador de Votação</h2>

          <div class="timer-status">
            <div class="status-badge" :class="{ ativo: temporizadorAtivo, inativo: !temporizadorAtivo }">
              {{ temporizadorAtivo ? 'Ativo' : 'Inativo' }}
            </div>
            <div v-if="temporizadorAtivo && tempoRestante > 0" class="time-remaining">
              Tempo restante: <strong>{{ formatarTempoRestante() }}</strong>
            </div>
            <div v-else-if="temporizadorAtivo && tempoRestante <= 0" class="time-expired">
              Tempo expirado
            </div>
          </div>

          <div class="form-group">
            <label>Duração da votação (minutos):</label>
            <input
              type="number"
              v-model.number="duracao"
              min="1"
              max="1440"
              placeholder="Ex: 30"
            />
          </div>

          <div class="timer-actions">
            <button
              @click="ativarTemporizador"
              :disabled="salvando || !duracao"
              class="btn-success"
            >
              {{ temporizadorAtivo ? 'Reiniciar Temporizador' : 'Ativar Temporizador' }}
            </button>
            <button
              @click="desativarTemporizador"
              :disabled="salvando || !temporizadorAtivo"
              class="btn-danger"
            >
              Desativar Temporizador
            </button>
          </div>

          <div class="alert info">
            <strong>ℹ️ Como funciona:</strong>
            <ul>
              <li>Quando o temporizador está ativo, a votação só aceita votos durante o tempo configurado</li>
              <li>Quando o temporizador está inativo, a votação segue o status da proposta (ativa/inativa)</li>
              <li>O tempo restante é mostrado na página de votação</li>
            </ul>
          </div>
        </section>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useArmazenamentoAutenticacao } from '@/armazenamentos/autenticacao'
import api from '@/servicos/api'

const router = useRouter()
const armazenamentoAuth = useArmazenamentoAutenticacao()

const configuracoes = ref<any>({})
const bannerArquivo = ref<File | null>(null)
const bannerInput = ref<HTMLInputElement>()
const uploading = ref(false)
const salvando = ref(false)
const duracao = ref(30)

const temporizadorAtivo = computed(() => {
  return configuracoes.value.temporizador_ativo === '1' &&
         configuracoes.value.temporizador_ativo_verificado === true
})

const tempoRestante = computed(() => {
  return configuracoes.value.tempo_restante_segundos || 0
})

let intervalo: any = null

onMounted(async () => {
  await carregarConfiguracoes()
  // Atualizar tempo restante a cada segundo
  intervalo = setInterval(atualizarTempoRestante, 1000)
})

onUnmounted(() => {
  if (intervalo) {
    clearInterval(intervalo)
  }
})

async function carregarConfiguracoes() {
  try {
    const response = await api.get('/configuracoes')
    configuracoes.value = response.data
    duracao.value = parseInt(configuracoes.value.temporizador_duracao_minutos || '30')
  } catch (err) {
    console.error('Erro ao carregar configurações:', err)
  }
}

async function atualizarTempoRestante() {
  if (temporizadorAtivo.value) {
    try {
      const response = await api.get('/configuracoes')
      configuracoes.value.tempo_restante_segundos = response.data.tempo_restante_segundos
      configuracoes.value.temporizador_ativo_verificado = response.data.temporizador_ativo_verificado
    } catch (err) {
      console.error('Erro ao atualizar tempo restante:', err)
    }
  }
}

function selecionarBanner(event: Event) {
  const target = event.target as HTMLInputElement
  if (target.files && target.files[0]) {
    bannerArquivo.value = target.files[0]
  }
}

async function uploadBanner() {
  if (!bannerArquivo.value) return

  uploading.value = true
  try {
    const formData = new FormData()
    formData.append('banner', bannerArquivo.value)

    const response = await api.post('/configuracoes/banner', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })

    alert('Banner atualizado com sucesso!')
    await carregarConfiguracoes()
    bannerArquivo.value = null
    if (bannerInput.value) {
      bannerInput.value.value = ''
    }
  } catch (err: any) {
    alert(err.response?.data?.mensagem || 'Erro ao fazer upload do banner')
  } finally {
    uploading.value = false
  }
}

async function ativarTemporizador() {
  salvando.value = true
  try {
    // Salvar duração
    await api.put('/configuracoes', {
      temporizador_duracao_minutos: duracao.value,
      temporizador_ativo: '1'
    })

    // Reiniciar temporizador
    await api.post('/configuracoes/temporizador/reiniciar')

    alert('Temporizador ativado com sucesso!')
    await carregarConfiguracoes()
  } catch (err: any) {
    alert(err.response?.data?.mensagem || 'Erro ao ativar temporizador')
  } finally {
    salvando.value = false
  }
}

async function desativarTemporizador() {
  salvando.value = true
  try {
    await api.put('/configuracoes', {
      temporizador_ativo: '0'
    })

    alert('Temporizador desativado!')
    await carregarConfiguracoes()
  } catch (err: any) {
    alert(err.response?.data?.mensagem || 'Erro ao desativar temporizador')
  } finally {
    salvando.value = false
  }
}

function formatarTempoRestante() {
  const segundos = tempoRestante.value
  const horas = Math.floor(segundos / 3600)
  const minutos = Math.floor((segundos % 3600) / 60)
  const segs = segundos % 60

  if (horas > 0) {
    return `${horas}h ${minutos}m ${segs}s`
  } else if (minutos > 0) {
    return `${minutos}m ${segs}s`
  } else {
    return `${segs}s`
  }
}

function sair() {
  armazenamentoAuth.sair()
  router.push('/login')
}

function onUnmounted(callback: () => void) {
  // Vue 3 onUnmounted hook
  if (typeof window !== 'undefined') {
    window.addEventListener('beforeunload', callback)
  }
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
  margin-bottom: 2rem;
}

.header h1 {
  color: #1351b4;
  margin: 0;
}

.config-container {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.config-section {
  background: white;
  border-radius: 8px;
  padding: 2rem;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.config-section h2 {
  color: #1351b4;
  margin-top: 0;
  margin-bottom: 1.5rem;
  font-size: 1.5rem;
}

.banner-preview {
  margin-bottom: 1.5rem;
  border: 2px solid #e9ecef;
  border-radius: 4px;
  overflow: hidden;
}

.banner-preview img {
  width: 100%;
  max-width: 600px;
  height: auto;
  display: block;
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-group label {
  display: block;
  font-weight: 600;
  margin-bottom: 0.5rem;
  color: #333;
}

.form-group input[type="file"] {
  display: block;
  margin-bottom: 1rem;
  padding: 0.5rem;
}

.form-group input[type="number"] {
  width: 100%;
  max-width: 200px;
  padding: 0.75rem;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 1rem;
}

.help-text {
  color: #666;
  font-size: 0.875rem;
  margin: 0;
}

.btn-primary,
.btn-success,
.btn-danger {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 4px;
  font-size: 1rem;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-primary {
  background: #1351b4;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: #0d3a7f;
}

.btn-success {
  background: #2b8a3e;
  color: white;
  margin-right: 1rem;
}

.btn-success:hover:not(:disabled) {
  background: #1e6129;
}

.btn-danger {
  background: #c92a2a;
  color: white;
}

.btn-danger:hover:not(:disabled) {
  background: #9c1f1f;
}

button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.timer-status {
  margin-bottom: 1.5rem;
  padding: 1rem;
  background: #f8f9fa;
  border-radius: 4px;
}

.status-badge {
  display: inline-block;
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-weight: 600;
  margin-bottom: 0.5rem;
}

.status-badge.ativo {
  background: #d3f9d8;
  color: #2b8a3e;
}

.status-badge.inativo {
  background: #f1f3f5;
  color: #666;
}

.time-remaining {
  font-size: 1.1rem;
  margin-top: 0.5rem;
}

.time-remaining strong {
  color: #1351b4;
  font-size: 1.3rem;
}

.time-expired {
  color: #c92a2a;
  font-weight: 600;
  margin-top: 0.5rem;
}

.timer-actions {
  display: flex;
  gap: 1rem;
  margin-top: 1.5rem;
}

.alert {
  padding: 1rem 1.5rem;
  border-radius: 4px;
  margin-top: 1.5rem;
}

.alert.info {
  background: #e7f5ff;
  border: 1px solid #339af0;
  color: #1864ab;
}

.alert strong {
  display: block;
  margin-bottom: 0.5rem;
}

.alert ul {
  margin: 0;
  padding-left: 1.5rem;
}

.alert li {
  margin-bottom: 0.25rem;
}
</style>
