import { createRouter, createWebHistory } from 'vue-router'
import Home from '../pages/Home.vue'
import Catalog from '../pages/Catalog.vue'
import GenderSelect from '../pages/GenderSelect.vue'
import ProductPage from '../pages/ProductPage.vue'
import SignUpForm from '../pages/SignUpForm.vue'
import LoginForm from '../pages/LoginForm.vue'
import OrderCheckout from '../pages/OrderCheckout.vue'

const routes = [
  {
    path: '/',
    name: 'Home',
    component: Home,
  },
  {
    path: '/catalog',
    name: 'Catalog',
    component: Catalog,
  },
    {
        path: '/genderSelect',
        name: 'GenderSelect',
        component: GenderSelect,
    },
    {
        path: '/product/:id',
        name: 'ProductPage',
        component: ProductPage,
    },
    {
        path: '/signUpForm',
        name: 'SignUpForm',
        component: SignUpForm,
    },
    {
        path: '/login',
        name: 'LoginForm',
        component: LoginForm,
    },
    {
        path: '/orderCheckout',
        name: 'OrderCheckout',
        component: OrderCheckout,
    },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

export default router
