import axios from 'axios'
import router from '@/router'
// import { routeLocationKey } from 'vue-router'

console.log('env:', process.env)

const api = axios.create({
  baseURL: process.env.VUE_APP_API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
  },
})

// request: token をヘッダーに自動付与
api.interceptors.request.use((config) => {
  const url = config.url || ''

  //仮登録時
  if (url.includes('/pre-register')) {
    const token = localStorage.getItem('temp_token')
    if (token) {
      config.headers['X-TEMP-TOKEN'] = token
    }
  }

  const bearer = localStorage.getItem('bearer_token')
  if (bearer) config.headers['Authorization'] = `Bearer ${bearer}`

  return config
})


// response: 401ならログインへ強制リダイレクト
api.interceptors.response.use(
  (response) => response,
  (error) => {
    const status = error?.response?.status
    const message = error?.response?.data?.message

    if (status === 401 || message === 'Unauthenticated.') {
      // トークン破棄
      localStorage.removeItem('bearer_token')
      localStorage.removeItem('user')

      // 今いるURLをredirect先として保持してログインページへ
      const currentPath = router.currentRoute.value.fullPath
      router.replace({
        name: 'Login',
        query: { redirect: currentPath }
      })
    }
    return Promise.reject(error)
  } 
)



export default api
