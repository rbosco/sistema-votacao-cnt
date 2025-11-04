import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { resolve } from 'path'

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': resolve(__dirname, 'src'),
    },
  },
  server: {
    host: true,              // ou '0.0.0.0' — aceita conexões externas (docker)
    port: 8088,              // mesma porta que você abre no navegador
    strictPort: true,        // se 8088 estiver ocupada, ele não muda sozinho
    allowedHosts: [
      'conferencianacional-hml.trabalho.gov.br',
      'conferencianacional.trabalho.gov.br',
    ],
    watch: {
      usePolling: true,      // importante para docker/wsl
      interval: 100,         // pode aumentar se quiser
    },
    hmr: {
      host: 'localhost',     // como você acessa do navegador
      port: 8088,            // mesma porta exposta
      protocol: 'ws',        // padrão
    },
    proxy: {
      '/api': {
        target: 'http://servico:9000',
        changeOrigin: true,
      },
    },
  },
})
