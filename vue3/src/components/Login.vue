<template>
  <div class="page">
    <div class="container">
      <section class="card auth-card">
        <header class="card-head">
          <div>
            <h1 class="title">ログイン</h1>
            <p class="sub">メールアドレスとパスワードを入力してください</p>
          </div>
        </header>

        <div v-if="errorMessage" class="alert alert-error">
          {{ errorMessage }}
        </div>

        <form class="form" @submit.prevent="submitLogin">
          <div class="field">
            <label class="label">メールアドレス</label>
            <input
              class="control"
              type="email"
              v-model="email"
              placeholder="you@example.com"
              autocomplete="email"
              required
            />
          </div>

          <div class="field">
            <label class="label">パスワード</label>
            <input
              class="control"
              type="password"
              v-model="password"
              placeholder="パスワード"
              autocomplete="current-password"
              required
            />
          </div>

          <div class="actions">
            <button class="btn btn-primary" type="submit" :disabled="loading">
              {{ loading ? 'ログイン中...' : 'ログイン' }}
            </button>

            <!-- 任意：仮登録への導線（ルートがあるなら） -->
            <router-link class="link" to="/pre-register">アカウント作成（仮登録）</router-link>
          </div>
        </form>
      </section>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/lib/api'

const router = useRouter()
const route = useRoute()

const email = ref('')
const password = ref('')
const loading = ref(false)
const errorMessage = ref('')


async function submitLogin() {
    loading.value = true
    errorMessage.value = ''

    try {
        const res = await api.post('/api/login', {
            email: email.value,
            password: password.value
        })

        const token = res.data.token
        if (token) {
            //`bearer_token`に認証トークン保存
            localStorage.setItem('bearer_token', token)
            //`user`に会員情報を
            localStorage.setItem('user', JSON.stringify(res.data.user))
        }
        console.log("login OK")

        const redirect = route.query.redirect || '/requests'
        router.push(redirect)

    } catch (e) {
        console.log("login NG")
        if (e.response) {
            const status = e.response.status
            const message = e.response.data?.message
            if (status === 422) {
                errorMessage.value = message || 'メールアドレスまたはパスワードが正しくありません'
            } else {
                errorMessage.value = 'ログインに失敗しました。時間をおいて再度お試しください'
            }
        } else {
            errorMessage.value = '通信エラーが発生しました。ネットワーク状態を確認してください'
        }
    } finally {
        loading.value = false
    }
}


</script>

<style scoped>
.page {
  min-height: calc(100vh - 0px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px 0 48px;
  background: #fff;
}
.container {
  width: min(520px, calc(100% - 32px));
  margin: 0 auto;
}

.card {
  border: 1px solid #e6e6e6;
  border-radius: 12px;
  padding: 18px;
  box-shadow: 0 1px 2px rgba(0,0,0,.04);
}
.card-head {
  margin-bottom: 12px;
}

.title {
  margin: 0;
  font-size: 22px;
  font-weight: 800;
  letter-spacing: 0.02em;
}
.sub {
  margin: 6px 0 0;
  font-size: 12px;
  color: #666;
}

.form {
  display: grid;
  gap: 12px;
}

.field {}
.label {
  display: block;
  font-size: 12px;
  color: #555;
  margin-bottom: 6px;
}
.control {
  width: 100%;
  border: 1px solid #d8d8d8;
  border-radius: 10px;
  padding: 10px 12px;
  font-size: 14px;
  outline: none;
  box-sizing: border-box;
}
.control:focus {
  border-color: #b8b8b8;
}

.actions {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-top: 4px;
}

.btn {
  border: 1px solid #d8d8d8;
  background: #fff;
  padding: 10px 14px;
  border-radius: 10px;
  cursor: pointer;
  font-size: 13px;
}
.btn:disabled { opacity: .6; cursor: not-allowed; }
.btn-primary { border-color: #1f6feb; background: #1f6feb; color: #fff; }
.btn-primary:hover { background: #1a5fd0; }

.link {
  font-size: 12px;
  color: #1f6feb;
  text-decoration: none;
}
.link:hover { text-decoration: underline; }

.alert {
  border-radius: 10px;
  padding: 10px 12px;
  margin: 8px 0 6px;
  font-size: 13px;
}
.alert-error {
  background: #fff3f3;
  border: 1px solid #ffd0d0;
  color: #a10000;
}
</style>