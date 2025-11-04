<template>
  <div v-if="paginacao.last_page > 1" class="paginacao">
    <button
      @click="mudarPagina(paginacao.current_page - 1)"
      :disabled="paginacao.current_page === 1"
      class="btn-pagina"
    >
      ‹ Anterior
    </button>

    <div class="numeros-pagina">
      <button
        v-for="pagina in paginasVisiveis"
        :key="pagina"
        @click="mudarPagina(pagina)"
        :class="['btn-numero', { ativo: pagina === paginacao.current_page }]"
      >
        {{ pagina }}
      </button>
    </div>

    <button
      @click="mudarPagina(paginacao.current_page + 1)"
      :disabled="paginacao.current_page === paginacao.last_page"
      class="btn-pagina"
    >
      Próxima ›
    </button>

    <div class="info-paginacao">
      Mostrando {{ paginacao.from }} a {{ paginacao.to }} de {{ paginacao.total }} registros
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Paginacao {
  current_page: number
  last_page: number
  from: number
  to: number
  total: number
}

interface Props {
  paginacao: Paginacao
}

const props = defineProps<Props>()

const emit = defineEmits<{
  (e: 'mudar-pagina', pagina: number): void
}>()

const paginasVisiveis = computed(() => {
  const paginas: number[] = []
  const paginaAtual = props.paginacao.current_page
  const ultimaPagina = props.paginacao.last_page

  // Mostrar no máximo 5 páginas
  let inicio = Math.max(1, paginaAtual - 2)
  let fim = Math.min(ultimaPagina, inicio + 4)

  // Ajustar o início se estivermos próximos do fim
  if (fim - inicio < 4) {
    inicio = Math.max(1, fim - 4)
  }

  for (let i = inicio; i <= fim; i++) {
    paginas.push(i)
  }

  return paginas
})

function mudarPagina(pagina: number) {
  if (pagina >= 1 && pagina <= props.paginacao.last_page) {
    emit('mudar-pagina', pagina)
  }
}
</script>

<style scoped>
.paginacao {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  margin-top: 2rem;
  padding: 1rem;
  flex-wrap: wrap;
}

.btn-pagina,
.btn-numero {
  padding: 0.5rem 1rem;
  border: 1px solid #ddd;
  background: white;
  color: #1351b4;
  border-radius: 4px;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.2s;
}

.btn-pagina:hover:not(:disabled),
.btn-numero:hover {
  background: #e7f5ff;
  border-color: #1351b4;
}

.btn-pagina:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  color: #999;
}

.numeros-pagina {
  display: flex;
  gap: 0.25rem;
}

.btn-numero.ativo {
  background: #1351b4;
  color: white;
  border-color: #1351b4;
}

.info-paginacao {
  width: 100%;
  text-align: center;
  color: #666;
  font-size: 0.875rem;
  margin-top: 0.5rem;
}
</style>
