/**
 * Formata um CPF para o padrão XXX.XXX.XXX-XX
 * @param cpf - CPF sem formatação (apenas números)
 * @returns CPF formatado ou string vazia se inválido
 */
export function formatarCPF(cpf: string): string {
  if (!cpf) return ''

  // Remove tudo que não é número
  const apenasNumeros = cpf.replace(/\D/g, '')

  // Aplica a máscara
  if (apenasNumeros.length <= 11) {
    return apenasNumeros
      .replace(/(\d{3})(\d)/, '$1.$2')
      .replace(/(\d{3})(\d)/, '$1.$2')
      .replace(/(\d{3})(\d{1,2})$/, '$1-$2')
  }

  return cpf
}

/**
 * Remove a formatação do CPF, mantendo apenas números
 * @param cpf - CPF formatado
 * @returns CPF apenas com números
 */
export function removerFormatacaoCPF(cpf: string): string {
  if (!cpf) return ''
  return cpf.replace(/\D/g, '')
}

/**
 * Aplica máscara de CPF em tempo real em um evento de input
 * @param event - Evento do input
 */
export function aplicarMascaraCPF(event: Event): void {
  const input = event.target as HTMLInputElement
  let valor = input.value.replace(/\D/g, '')

  // Limita a 11 dígitos
  if (valor.length > 11) {
    valor = valor.slice(0, 11)
  }

  // Aplica a máscara
  if (valor.length > 9) {
    input.value = valor.replace(/(\d{3})(\d{3})(\d{3})(\d{1,2})/, '$1.$2.$3-$4')
  } else if (valor.length > 6) {
    input.value = valor.replace(/(\d{3})(\d{3})(\d{1,3})/, '$1.$2.$3')
  } else if (valor.length > 3) {
    input.value = valor.replace(/(\d{3})(\d{1,3})/, '$1.$2')
  } else {
    input.value = valor
  }
}
