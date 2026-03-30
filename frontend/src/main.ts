import { createApp } from 'vue'
import './style.css'
import App from './App.vue'
import router from './router'
import axios from 'axios'

const app = createApp(App)

axios.defaults.withCredentials = true 
axios.defaults.baseURL = 'http://localhost'

app.use(router)
app.mount('#app')

