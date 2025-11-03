import axios from 'axios'
import { useArmazenamentoLoading } from '@/armazenamentos/loading'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8090/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
})

// Adicionar token de autenticação em todas as requisições
const token = localStorage.getItem('token')
if (token) {
  api.defaults.headers.common['Authorization'] = `Bearer ${token}`
}

// Interceptor para adicionar loading
api.interceptors.request.use(
  config => {
    const loadingStore = useArmazenamentoLoading()
    loadingStore.iniciar()
    return config
  },
  erro => {
    const loadingStore = useArmazenamentoLoading()
    loadingStore.parar()
    return Promise.reject(erro)
  }
)

// Interceptor para lidar com erros de autenticação e remover loading
api.interceptors.response.use(
  resposta => {
    const loadingStore = useArmazenamentoLoading()
    loadingStore.parar()
    return resposta
  },
  erro => {
    const loadingStore = useArmazenamentoLoading()
    loadingStore.parar()

    if (erro.response && erro.response.status === 401) {
      localStorage.removeItem('token')
      localStorage.removeItem('usuario')
      window.location.href = '/login'
    }
    return Promise.reject(erro)
  }
)

export default api
