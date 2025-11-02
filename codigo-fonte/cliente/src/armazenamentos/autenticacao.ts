import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/servicos/api'

interface Usuario {
  id: number
  nome: string
  cpf: string
  is_admin: boolean
}

export const useArmazenamentoAutenticacao = defineStore('autenticacao', () => {
  const usuario = ref<Usuario | null>(null)
  const token = ref<string | null>(localStorage.getItem('token'))

  const estaAutenticado = computed(() => !!token.value)
  const ehAdmin = computed(() => usuario.value?.is_admin || false)

  async function entrar(credenciais: { cpf: string; password: string }) {
    try {
      const resposta = await api.post('/entrar', credenciais)
      token.value = resposta.data.token
      usuario.value = resposta.data.usuario

      localStorage.setItem('token', token.value!)
      localStorage.setItem('usuario', JSON.stringify(usuario.value))
      api.defaults.headers.common['Authorization'] = `Bearer ${token.value}`

      return resposta.data
    } catch (erro) {
      throw erro
    }
  }

  async function sair() {
    try {
      await api.post('/sair')
    } catch (erro) {
      console.error('Erro ao fazer logout:', erro)
    } finally {
      token.value = null
      usuario.value = null
      localStorage.removeItem('token')
      localStorage.removeItem('usuario')
      delete api.defaults.headers.common['Authorization']
    }
  }

  function carregarUsuario() {
    const tokenArmazenado = localStorage.getItem('token')
    const usuarioArmazenado = localStorage.getItem('usuario')

    if (tokenArmazenado && usuarioArmazenado) {
      token.value = tokenArmazenado
      usuario.value = JSON.parse(usuarioArmazenado)
      api.defaults.headers.common['Authorization'] = `Bearer ${tokenArmazenado}`
    }
  }

  return {
    usuario,
    token,
    estaAutenticado,
    ehAdmin,
    entrar,
    sair,
    carregarUsuario,
  }
})
