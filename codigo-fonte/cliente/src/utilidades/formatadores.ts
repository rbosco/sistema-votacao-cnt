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

/**
 * Valida se um CPF é válido segundo o algoritmo de dígitos verificadores
 * @param cpf - CPF com ou sem formatação
 * @returns true se o CPF for válido, false caso contrário
 */
export function validarCPF(cpf: string): boolean {
  if (!cpf) return false

  // Remove formatação
  const cpfLimpo = cpf.replace(/\D/g, '')

  // Verifica se tem 11 dígitos
  if (cpfLimpo.length !== 11) return false

  // Verifica se todos os dígitos são iguais (ex: 111.111.111-11)
  if (/^(\d)\1+$/.test(cpfLimpo)) return false

  // Validação dos dígitos verificadores
  let soma = 0
  let resto

  // Valida primeiro dígito verificador
  for (let i = 1; i <= 9; i++) {
    soma += parseInt(cpfLimpo.substring(i - 1, i)) * (11 - i)
  }
  resto = (soma * 10) % 11
  if (resto === 10 || resto === 11) resto = 0
  if (resto !== parseInt(cpfLimpo.substring(9, 10))) return false

  // Valida segundo dígito verificador
  soma = 0
  for (let i = 1; i <= 10; i++) {
    soma += parseInt(cpfLimpo.substring(i - 1, i)) * (12 - i)
  }
  resto = (soma * 10) % 11
  if (resto === 10 || resto === 11) resto = 0
  if (resto !== parseInt(cpfLimpo.substring(10, 11))) return false

  return true
}
