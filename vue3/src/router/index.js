import { createRouter, createWebHistory } from 'vue-router'
import PreRegister from '@/components/PreRegister.vue'
import Register from '@/components/Register.vue'
import Login from '@/components/Login.vue'
import Requests from '@/components/Requests.vue'


const routes = [
  {
    path: '/pre-register',
    name: 'PreRegister',
    component: PreRegister,
  },
  {
    path: '/register',
    component: Register,
  },
  {
    path: '/register/confirm',
    name: 'RegisterConfirm',
    component: Register, 
  },
  {
    path: '/login', 
    name: 'Login',
    component: Login, 
  },
  {
    path: '/requests',
    name: 'Requests',
    component: Requests,
    meta: { requiresAuth: true }
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

// 認証ガード
router.beforeEach((to, from, next) => {
  const isLoggedIn = !!localStorage.getItem('bearer_token')

  if (to.meta.requiresAuth && !isLoggedIn) {
    next({
      name: 'Login',
      query: { redirect: to.fullPath },
    })
  } else {
    next()
  }

})

export default router