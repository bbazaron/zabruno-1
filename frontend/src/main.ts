import { createApp } from 'vue'
import './style.css'
import App from './App.vue'
import router from './router'
import axios from 'axios'
import { getApiBaseUrl } from './utils/apiBaseUrl'

const app = createApp(App)

axios.defaults.withCredentials = true
axios.defaults.baseURL = getApiBaseUrl()

app.use(router)
app.mount('#app')

