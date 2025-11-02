import { createApp } from 'vue'
import { createPinia } from 'pinia'
import roteador from './roteador'
import App from './App.vue'
import './estilos/app.css'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(roteador)
app.mount('#app')
