<template>
  <div class="page">
    <div class="container">
      <section class="card auth-card">
        <header class="card-head">
          <div>
            <h1 class="title">仮登録</h1>
            <p class="sub">登録用のメールを送信します</p>
          </div>
        </header>

        <!-- message area -->
        <div v-if="message" class="alert" :class="finished ? 'alert-success' : 'alert-error'">
          {{ message }}
        </div>

        <!-- form -->
        <form v-if="!finished" class="form" @submit.prevent="submitEmail">
          <div class="field">
            <label class="label">メールアドレス</label>
            <input
              v-model="email"
              class="control"
              type="email"
              placeholder="you@example.com"
              autocomplete="email"
              required
            />
            <p class="hint">入力したメールアドレス宛に確認メールを送ります</p>
          </div>

          <div class="actions">
            <button class="btn btn-primary" type="submit" :disabled="loading">
              {{ loading ? '送信中...' : '送信' }}
            </button>
            <router-link class="link" to="/login">ログインへ戻る</router-link>
          </div>
        </form>

        <!-- finished view -->
        <div v-else class="done">
          <p class="done-text">メールを送信しました。受信箱をご確認ください。</p>
          <div class="actions">
            <router-link class="btn" to="/login">ログインへ</router-link>
            <button class="btn btn-ghost" @click="reset">別のメールで送る</button>
          </div>
        </div>
      </section>
    </div>
  </div>
</template>


<script>
import api from '@/lib/api'

export default {
  name: 'PreRegister',
  data() {
    return {
      email: '',
      message: '',
      finished: false,
      loading: false,
    }
  },
  mounted() {
    this.getTempToken()
  },
  methods: {
    ////////////////////////////
    //// temporary token取得 ////
    async getTempToken() {
      try {
        //一時token取得
        const response = await api.post('/api/issue-temp-token')
        const token = response.data.token
        localStorage.setItem('temp_token', token)
      } catch (error) {
        console.error('temp_token 発行失敗:', error)
      }
    },

    //////////////////
    //// 仮登録処理 ////
    async submitEmail() {
      this.loading = true
      this.message = ''

      try {
        await api.post('/api/pre-register', { email: this.email })
        localStorage.removeItem('temp_token')
        this.finished = true
        this.message = '仮登録メールを送信しました'
      } catch (error) {
        if (error.response) {
          if (error.response.status === 422) {
            this.message = error.response.data.message
          } else if (error.response.status === 403) {
            this.message = '画面の有効期限が切れています。リロードしてください。'
          } else {
            this.message = 'サーバーエラーが発生しました。'
          }
        } else {
          this.message = '通信エラーです。ネットワークを確認してください。'
        }
      } finally {
        this.loading = false
      }
    },

    reset() {
      this.email = ''
      this.message = ''
      this.finished = false
      this.getTempToken()
    }
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
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}
.btn:disabled { opacity: .6; cursor: not-allowed; }
.btn-primary { border-color: #1f6feb; background: #1f6feb; color: #fff; }
.btn-primary:hover { background: #1a5fd0; }
.btn-ghost { background: #fff; }
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
.alert-success {
  background: #eefaf1;
  border: 1px solid #b7e6c4;
  color: #0b6b2a;
}

.done { margin-top: 10px; }
.done-text { margin: 0 0 10px; font-size: 14px; color: #333; }
</style>