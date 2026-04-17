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
    path: '/contacts',
    name: 'Contacts',
    component: Contacts,
  },
  {
    path: '/about',
    name: 'About',
    component: AboutUs,
  },
  {
    path: '/sizes',
    name: 'SizeGuide',
    component: AboutSize,
  },
  {
    path: '/how-to-order',
    name: 'HowToOrder',
    component: HowToOrder,
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
    {
        path: '/orders',
        name: 'Orders',
        component: Orders,
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
    },
    {
        path: '/admin',
        name: 'AdminPanel',
        component: AdminPanel,
        meta: {
            requiresAuth: true,
            requiresAdmin: true,
        },
    },
    {
        path: '/admin/orders',
        name: 'AllOrders',
        component: AllOrders,
        meta: {
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
            requiresAuth: true,
            requiresAdmin: true,
        },
    },

]

const router = createRouter({
  history: createWebHistory(),
  routes,
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
