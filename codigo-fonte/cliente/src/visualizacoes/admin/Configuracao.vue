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

        <!-- Duração do Temporizador -->
        <section class="config-section">
          <h2>Duração do Temporizador</h2>

          <div class="form-group">
            <label>Duração padrão da votação (minutos):</label>
            <input
              type="number"
              v-model.number="duracao"
              min="1"
              max="1440"
              placeholder="Ex: 30"
            />
          </div>

          <button
            @click="salvarDuracao"
            :disabled="salvando || !duracao"
            class="btn-primary"
          >
            {{ salvando ? 'Salvando...' : 'Salvar Duração' }}
          </button>

          <div class="alert info">
            <strong>ℹ️ Como funciona:</strong>
            <ul>
              <li>Defina aqui a duração padrão para o temporizador das propostas</li>
              <li>O temporizador deve ser ativado individualmente em cada proposta através do botão ⏱️</li>
              <li>Só é possível ativar o temporizador em propostas ativas com status "Em Votação"</li>
              <li>O tempo restante é mostrado na página de votação para propostas com temporizador ativo</li>
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

onMounted(async () => {
  await carregarConfiguracoes()
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

async function salvarDuracao() {
  salvando.value = true
  try {
    await api.put('/configuracoes', {
      temporizador_duracao_minutos: duracao.value
    })

    alert('Duração padrão do temporizador salva com sucesso!')
    await carregarConfiguracoes()
  } catch (err: any) {
    alert(err.response?.data?.mensagem || 'Erro ao salvar duração')
  } finally {
    salvando.value = false
  }
}

async function sair() {
  await armazenamentoAuth.sair()
  router.push('/login')
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
