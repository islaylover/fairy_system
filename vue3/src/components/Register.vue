<template>
  <div class="page">
    <div class="container">
      <section class="card auth-card">
        <header class="card-head">
          <div>
            <h1 class="title">会員登録</h1>
            <p class="sub">必要事項を入力して登録してください</p>
          </div>
        </header>

        <!-- invalid token -->
        <div v-if="invalid" class="alert alert-error">
          {{ errorMessage || 'この登録リンクは無効か、有効期限が切れています。' }}
        </div>

        <!-- form -->
        <form v-else class="form" @submit.prevent="submitRegister">
          <div v-if="errorMessage" class="alert alert-error">
            {{ errorMessage }}
          </div>

          <div class="field">
            <label class="label">メールアドレス</label>
            <input class="control" type="email" v-model="email" readonly />
            <p class="hint">このメールアドレスで登録します</p>
          </div>

          <div class="field">
            <label class="label">名前</label>
            <input
              class="control"
              type="text"
              placeholder="名前"
              v-model="name"
              required
            />
          </div>

          <div class="field">
            <label class="label">パスワード</label>
            <input
              class="control"
              type="password"
              placeholder="パスワード"
              v-model="password"
              autocomplete="new-password"
              required
            />
          </div>

          <div class="actions">
            <button class="btn btn-primary" type="submit" :disabled="loading">
              {{ loading ? '登録中...' : '登録' }}
            </button>
            <router-link class="link" to="/login">ログインへ戻る</router-link>
          </div>
        </form>
      </section>
    </div>
  </div>
</template>

<script setup>
import {ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/lib/api'

// =======================
// Vue Router パラメータ取得
// =======================
const route = useRoute()
const router = useRouter()

// =======================
// sate 
// =======================
const token    = ref('')
const email    = ref('')
const name     = ref('')
const password = ref('')

const invalid  = ref(false)
const loading  = ref(false) 
const errorMessage = ref('')
const successMessage = ref('')


// =======================
// lifecycle
// =======================
onMounted(() => {
  token.value = route.query.token || ''
  if (!token.value) {
    invalid.value = true
    errorMessage.value = 'トークンが指定されていません'
    return
  }
  checkToken()
})

// =======================
// API: check token 
// =======================
async function checkToken() {
  loading.value = true
  errorMessage.value = ''

  try {
    const res = await api.get('/api/register/confirm', {
      params : { token: token.value }
    })
    email.value = res.data.email
  } catch (e) {
    console.error('Token invalid', e)
    invalid.value = true

    if (e.response) {
      const status = e.response.status
      const msg = e.response.data?.message

      if (status === 422 || status === 404) {
        // トークン不正・期限切れなど
        errorMessage.value = msg || '登録リンクが無効か、有効期限が切れています。'
      } else {
        // サーバエラー
        errorMessage.value = msg || 'サーバーエラーが発生しました。時間をおいて再度お試しください。'
      }
    } else {
      errorMessage.value = '通信エラーが発生しました。ネットワーク状態を確認してから再度お試しください。'
    }
  } finally {
    loading.value = false
  }
}


// =======================
// API: 会員登録
// =======================
async function submitRegister() {
  loading.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    const res = await api.post('/api/register', {
      token:    token.value,
      email:    email.value,
      name:     name.value,
      password: password.value 
    })
    alert("登録完了しました");
    successMessage.value = res.data.message || '登録が完了しました。'
    router.push('/login')
  } catch (e) {
    console.log('Failed to register')
    if (e.response) {
      const status = e.response.status
      const msg = e.response.data?.message
      if (status === 422) {
        // バリデーション or 業務エラー（例: メール既に登録済み）
        errorMessage.value = msg || '入力内容に誤りがあります。'
      } else if (status === 403) {
        // トークンの整合性がおかしい/期限切れなど
        errorMessage.value = msg || 'この登録リンクは無効か有効期限が切れています。最初からやり直してください。'
      } else if (status >= 500) {
        errorMessage.value = msg || 'サーバーエラーが発生しました。時間をおいて再度お試しください。'
      } else {
        errorMessage.value = msg || '登録に失敗しました。'
      }
    } else {
      loading.value = false
      errorMessage.value =
        '通信エラーが発生しました。ネットワーク状態を確認してから再度お試しください。'
    }
  } finally {
    loading.value = false
  }
}

</script>

<style scoped>
.page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px 0 48px;
  background: #fff;
}
.container {
  width: min(560px, calc(100% - 32px));
  margin: 0 auto;
}

.card {
  border: 1px solid #e6e6e6;
  border-radius: 12px;
  padding: 18px;
  box-shadow: 0 1px 2px rgba(0,0,0,.04);
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

.form { display: grid; gap: 12px; margin-top: 10px; }

.label {
  display: block;
  font-size: 12px;
  color: #555;
  margin-bottom: 6px;
}
.control {
  width: 100%;
  box-sizing: border-box;
  border: 1px solid #d8d8d8;
  border-radius: 10px;
  padding: 10px 12px;
  font-size: 14px;
  outline: none;
}
.control:focus { border-color: #b8b8b8; }

.hint {
  margin: 6px 0 0;
  font-size: 12px;
  color: #777;
}

.actions {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-top: 6px;
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
  font-size: 13px;
  margin: 8px 0 6px;
}
.alert-error {
  background: #fff3f3;
  border: 1px solid #ffd0d0;
  color: #a10000;
}
</style>