import { createRouter, createWebHistory } from 'vue-router'
import Home from '../pages/Home.vue'
import Catalog from '../pages/Catalog.vue'
import GenderSelect from '../pages/GenderSelect.vue'
import ProductPage from '../pages/ProductPage.vue'
import SignUpForm from '../pages/SignUpForm.vue'
import LoginForm from '../pages/LoginForm.vue'
import OrderCheckout from '../pages/OrderCheckout.vue'
import Orders from '../pages/Orders.vue'
import OrderDetails from '../pages/OrderDetails.vue'
import ProfileEdit from '../pages/ProfileEdit.vue'
import AdminPanel from '../pages/AdminPanel.vue'
import AllOrders from '../pages/AllOrders.vue'
import AdminOrderDetails from '../pages/AdminOrderDetails.vue'
import AdminProducts from '../pages/AdminProducts.vue'
import Contacts from '../pages/Contacts.vue'
import AboutUs from '../pages/AboutUs.vue'
import AboutSize from '../pages/AboutSize.vue'
import HowToOrder from '../pages/HowToOrder.vue'
import axios from 'axios'

const DEFAULT_DOCUMENT_TITLE = 'school-uniform-shop'

const routes = [
  {
    path: '/',
    name: 'Home',
    component: Home,
    meta: { title: 'Главная' },
  },
  {
    path: '/catalog',
    name: 'Catalog',
    component: Catalog,
    meta: { title: 'Каталог' },
  },
  {
    path: '/contacts',
    name: 'Contacts',
    component: Contacts,
    meta: { title: 'Контакты' },
  },
  {
    path: '/about',
    name: 'About',
    component: AboutUs,
    meta: { title: 'О нас' },
  },
  {
    path: '/sizes',
    name: 'SizeGuide',
    component: AboutSize,
    meta: { title: 'О размере' },
  },
  {
    path: '/how-to-order',
    name: 'HowToOrder',
    component: HowToOrder,
    meta: { title: 'Как заказать' },
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
        meta: { title: 'Регистрация' },
    },
    {
        path: '/login',
        name: 'LoginForm',
        component: LoginForm,
        meta: { title: 'Логин' },
    },
    {
        path: '/orderCheckout',
        name: 'OrderCheckout',
        component: OrderCheckout,
        meta: { title: 'Оформление заказа' },
    },
    {
        path: '/orders',
        name: 'Orders',
        component: Orders,
        meta: { title: 'Профиль пользователя' },
    },
    {
        path: '/orders/:id',
        name: 'OrderDetails',
        component: OrderDetails,
    },
    {
        path: '/profile',
        name: 'ProfileEdit',
        component: ProfileEdit,
        meta: { title: 'Редактировать профиль' },
    },
    {
        path: '/admin',
        name: 'AdminPanel',
        component: AdminPanel,
        meta: {
            title: 'Админ панель',
            requiresAuth: true,
            requiresAdmin: true,
        },
    },
    {
        path: '/admin/orders',
        name: 'AllOrders',
        component: AllOrders,
        meta: {
            title: 'Управление заказами',
            requiresAuth: true,
            requiresAdmin: true,
        },
    },
    {
        path: '/admin/orders/:id',
        name: 'AdminOrderDetails',
        component: AdminOrderDetails,
        meta: {
            requiresAuth: true,
            requiresAdmin: true,
        },
    },
    {
        path: '/admin/products',
        name: 'AdminProducts',
        component: AdminProducts,
        meta: {
            title: 'Управление товарами',
            requiresAuth: true,
            requiresAdmin: true,
        },
    },

]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

function documentTitleForOrderDetailsRoute(to: { name: unknown; params: Record<string, string | string[]> }): string | null {
  if (to.name !== 'OrderDetails') return null
  const raw = to.params.id
  const idPart = Array.isArray(raw) ? raw[0] : raw
  if (typeof idPart !== 'string' || !idPart.trim()) return null
  return `Заказ №${idPart.trim()}`
}

router.afterEach((to) => {
  const orderTitle = documentTitleForOrderDetailsRoute(to)
  if (orderTitle !== null) {
    document.title = orderTitle
    return
  }
  document.title =
    typeof to.meta.title === 'string' ? to.meta.title : DEFAULT_DOCUMENT_TITLE
})

function getStoredToken(): string | null {
  return localStorage.getItem('auth_token') || localStorage.getItem('token')
}

router.beforeEach(async (to) => {
  const requiresAuth = Boolean(to.meta.requiresAuth)
  const requiresAdmin = Boolean(to.meta.requiresAdmin)

  if (!requiresAuth && !requiresAdmin) {
    return true
  }

  const token = getStoredToken()
  if (!token) {
    return '/login'
  }

  if (!requiresAdmin) {
    return true
  }

  try {
    const response = await axios.get('/api/getUser', {
      headers: {
        Accept: 'application/json',
        Authorization: `Bearer ${token}`,
      },
    })

    const role = response.data?.role
    if (role === 'admin' || role === 'super_admin') {
      return true
    }

    return '/'
  } catch {
    return '/login'
  }
})

export default router
