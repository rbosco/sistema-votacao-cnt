import { createRouter, createWebHistory, RouteRecordRaw } from 'vue-router'
import { useArmazenamentoAutenticacao } from '@/armazenamentos/autenticacao'

const rotas: RouteRecordRaw[] = [
  {
    path: '/',
    redirect: '/votacao',
  },
  {
    path: '/votacao',
    name: 'Votacao',
    component: () => import('@/visualizacoes/Votacao.vue'),
    meta: { publica: true },
  },
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/visualizacoes/Login.vue'),
    meta: { publica: true },
  },
  {
    path: '/admin',
    name: 'Admin',
    component: () => import('@/visualizacoes/admin/PainelControle.vue'),
    meta: { requerAutenticacao: true },
  },
  {
    path: '/admin/usuarios',
    name: 'Usuarios',
    component: () => import('@/visualizacoes/admin/Usuarios.vue'),
    meta: { requerAutenticacao: true },
  },
  {
    path: '/admin/propostas',
    name: 'Propostas',
    component: () => import('@/visualizacoes/admin/Propostas.vue'),
    meta: { requerAutenticacao: true },
  },
  {
    path: '/admin/votos',
    name: 'Votos',
    component: () => import('@/visualizacoes/admin/Votos.vue'),
    meta: { requerAutenticacao: true },
  },
  {
    path: '/admin/configuracao',
    name: 'Configuracao',
    component: () => import('@/visualizacoes/admin/Configuracao.vue'),
    meta: { requerAutenticacao: true },
  },
  {
    path: '/resultados/:idCriptografado',
    name: 'Resultados',
    component: () => import('@/visualizacoes/Resultados.vue'),
    meta: { publica: true },
  },
]

const roteador = createRouter({
  history: createWebHistory(),
  routes: rotas,
})

roteador.beforeEach((para, de, proximo) => {
  const armazenamentoAuth = useArmazenamentoAutenticacao()
  const requerAutenticacao = para.matched.some(registro => registro.meta.requerAutenticacao)

  if (requerAutenticacao && !armazenamentoAuth.estaAutenticado) {
    proximo('/login')
  } else if (para.path === '/login' && armazenamentoAuth.estaAutenticado) {
    proximo('/admin')
  } else {
    proximo()
  }
})

export default roteador
