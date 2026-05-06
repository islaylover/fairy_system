<template>
  <div class="page">
    <div class="container">
      <!-- Header -->
      <header class="header">
        <div>
          <h1 class="title">お願い事一覧</h1>
          <p class="sub">ChatGPTへのリクエストを管理します</p>
        </div>
        <div class="header-actions">
          <button class="btn" @click="toggleCreateForm" :disabled="isOverLimitAny">
            {{ showCreateFormFlag ? '閉じる' : '新規登録' }}
          </button>

          <button class="btn btn-danger" @click="logout" :disabled="loggingOut">
            {{ loggingOut ? 'ログアウト中...' : 'ログアウト' }}
          </button>
        </div>
      </header>

      <!-- Error -->
      <div v-if="errorMessage" class="alert alert-error">
        {{ errorMessage }}
      </div>

      <div v-if="isOverLimitAny" class="alert alert-error">
        {{ limitBlockMessage }}
      </div>

    <!-- 月次利用状況（2枚横並び） -->
    <div class="usage-cards">
      <!-- 月次利用状況（ユーザー） -->
      <section class="card">
        <div class="card-head">
          <h2 class="card-title">今月の利用状況（あなた）</h2>
        </div>

        <div v-if="usageError" class="alert alert-error">{{ usageError }}</div>

        <div v-if="!usageLoading && usageUser" class="usage-grid">
          <div class="usage-kv">
            <div class="k">対象年月</div>
            <div class="v mono">{{ usageUser.year_month }}</div>

            <div class="k">完了件数</div>
            <div class="v mono">{{ usageUser.requests_done_count }}</div>

            <div class="k">トークン合計</div>
            <div class="v mono">{{ usageUser.total_tokens }}</div>

            <div class="k">推定コスト(USD)</div>
            <div class="v mono" :class="{ 'danger-text': usageUser.is_over_limit }">
              {{ formatUsd(usageUser.estimated_cost_usd) }}
            </div>
          </div>

          <div class="usage-panel">
            <div class="usage-big">
              <div class="usage-big-label">残り(USD)</div>
              <div class="usage-big-value mono" :class="{ 'danger-text': usageUser.is_over_limit }">
                {{ formatUsd(usageUser.remaining_usd) }}
              </div>

              <div class="usage-sub">
                上限: <span class="mono">{{ formatUsd(usageUser.limit_usd) }}</span>
                <span v-if="usageUser.is_over_limit" class="over-badge">上限超過</span>
              </div>
            </div>

            <div class="usage-bar">
              <div class="bar">
                <div class="bar-fill" :style="{ width: usagePercentBar(usageUser) + '%' }"></div>
              </div>
              <div class="bar-meta">
                使用率: <span class="mono">{{ usagePercentLabel(usageUser) }}%</span>
              </div>
            </div>
          </div>
        </div>

        <div v-else-if="usageLoading" class="meta">読み込み中...</div>
        <div v-else class="meta">データがありません</div>
      </section>

      <!-- 月次利用状況（全体） -->
      <section class="card">
        <div class="card-head">
          <h2 class="card-title">今月の利用状況（全会員合計）</h2>
        </div>

        <div v-if="usageError" class="alert alert-error">{{ usageError }}</div>

        <div v-if="!usageLoading && usageAll" class="usage-grid">
          <div class="usage-kv">
            <div class="k">対象年月</div>
            <div class="v mono">{{ usageAll.year_month }}</div>

            <div class="k">完了件数</div>
            <div class="v mono">{{ usageAll.requests_done_count }}</div>

            <div class="k">トークン合計</div>
            <div class="v mono">{{ usageAll.total_tokens }}</div>

            <div class="k">推定コスト(USD)</div>
            <div class="v mono" :class="{ 'danger-text': usageAll.is_over_limit }">
              {{ formatUsd(usageAll.estimated_cost_usd) }}
            </div>
          </div>

          <div class="usage-panel">
            <div class="usage-big">
              <div class="usage-big-label">残り(USD)</div>
              <div class="usage-big-value mono" :class="{ 'danger-text': usageAll.is_over_limit }">
                {{ formatUsd(usageAll.remaining_usd) }}
              </div>

              <div class="usage-sub">
                上限: <span class="mono">{{ formatUsd(usageAll.limit_usd) }}</span>
                <span v-if="usageAll.is_over_limit" class="over-badge">上限超過</span>
              </div>
            </div>

            <div class="usage-bar">
              <div class="bar">
                <div class="bar-fill" :style="{ width: usagePercentBar(usageAll) + '%' }"></div>
              </div>
              <div class="bar-meta">
                使用率: <span class="mono">{{ usagePercentLabel(usageAll) }}%</span>
              </div>
            </div>
          </div>
        </div>

        <div v-else-if="usageLoading" class="meta">読み込み中...</div>
        <div v-else class="meta">データがありません</div>
      </section>
    </div>

      <!-- 新規登録フォーム -->
      <section v-if="showCreateFormFlag" class="card">
        <div class="card-head">
          <h2 class="card-title">新規登録</h2>
          <div class="card-actions">
            <button class="btn btn-ghost" @click="resetCreateForm" :disabled="creatingFlag">クリア</button>
            <button class="btn btn-primary" @click="requestCreate" :disabled="creatingFlag || isOverLimitAny">
              {{ creatingFlag ? '登録中...' : '登録する' }}
            </button>
          </div>
        </div>

        <div class="form-grid">
          <div class="field">
            <label class="label">model</label>
            <select v-model="createForm.model" class="control">
              <option value="">選択してください</option>
              <option v-for="m in modelOptions" :key="m.id" :value="m.id">
                {{ m.name || m.id }}
              </option>
            </select>
          </div>

          <div class="field">
            <label class="label">request_type</label>
            <select v-model="createForm.request_type" class="control">
              <option value="">選択してください</option>
              <option v-for="t in requestTypeOptions" :key="t.id" :value="t.id">
                {{ t.label || t.id }}
              </option>
            </select>
          </div>

          <div class="field field-span">
            <label class="label">source_text</label>
            <textarea v-model="createForm.source_text" class="control textarea" rows="4"></textarea>
          </div>
        </div>
      </section>

      <!-- 一覧 -->
      <section class="card">
        <div class="card-head">
          <h2 class="card-title">一覧</h2>
          <div class="meta">
            <span v-if="loading">読み込み中...</span>
            <span v-else>合計: {{ total }}</span>
          </div>
        </div>

        <div class="table-wrap">
          <table class="table">
            <thead>
              <tr>
                <th style="width:72px;">ID</th>
                <th style="width:160px;">モデル</th>
                <th style="width:140px;">種別</th>
                <th style="width:120px;">ステータス</th>
                <th>元テキスト</th>
                <th>結果</th>
                <th style="width:180px;">操作</th>
              </tr>
            </thead>

            <tbody>
              <tr v-if="!loading && requests.length === 0">
                <td colspan="7" class="empty">リクエストデータがありません</td>
              </tr>

              <tr v-for="req in requests" :key="req.id">
                <td class="mono">{{ req.id }}</td>
                <td class="mono">{{ modelLabel(req.model) }}</td>
                <td class="mono">{{ requestTypeLabel(req.request_type) }}</td>
                <td>
                  <span class="badge" :class="badgeClass(req.status)">
                    {{ requestStatusLabel(req.status) }}
                  </span>
                </td>

                <td>{{ truncate(req.source_text, 60) }}</td>

                <td>
                  <span v-if="req.status === 2">{{ truncate(req.result_text, 60) }}</span>
                  <span v-else-if="req.status === 1" class="muted">処理中...</span>
                  <span v-else-if="req.status === 9" class="muted">失敗（詳細で確認）</span>
                  <span v-else class="muted">未処理</span>
                </td>

                <td class="actions">
                  <button class="btn btn-sm" @click="openDetail(req)">詳細</button>
                  <button class="btn btn-danger btn-sm" @click="requestDelete(req.id)">削除</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Paging -->
        <div v-if="!loading && lastPage > 1" class="pager">
          <button class="btn btn-sm" @click="changePage(currentPage - 1)" :disabled="currentPage === 1">←</button>
          <span class="pager-text">{{ currentPage }} / {{ lastPage }}（Total: {{ total }}）</span>
          <button class="btn btn-sm" @click="changePage(currentPage + 1)" :disabled="currentPage === lastPage">→</button>
        </div>
      </section>
    </div>

    <!-- Detail Modal -->
    <div v-if="detailOpen" class="modal-overlay" @click.self="closeDetail">
      <div class="modal">
        <div class="modal-head">
          <div class="modal-title">リクエスト詳細 #{{ detailReq?.id }}</div>
          <button class="btn btn-sm" @click="closeDetail">閉じる</button>
        </div>

        <div class="modal-body">
          <div class="kv">
            <div class="k">モデル</div>
            <div class="v mono">{{ modelLabel(detailReq?.model) }}</div>
          </div>

          <div class="kv">
            <div class="k">種別</div>
            <div class="v mono">{{ requestTypeLabel(detailReq?.request_type) }}</div>
          </div>

          <div class="kv">
            <div class="k">ステータス</div>
            <div class="v">
              <span class="badge" :class="badgeClass(Number(detailReq?.status))">
                {{ requestStatusLabel(Number(detailReq?.status)) }}
              </span>
            </div>
          </div>

          <div class="kv kv-top">
            <div class="k">元テキスト</div>
            <div class="v"><pre class="pre">{{ detailReq?.source_text }}</pre></div>
          </div>

          <div class="kv kv-top">
            <div class="k">結果</div>
            <div class="v"><pre class="pre">{{ detailReq?.result_text }}</pre></div>
          </div>

          <div class="divider"></div>

          <!-- 再実行 -->
          <div class="section-head">
            <div class="section-title">編集して再実行（新規発行）</div>
            <div class="section-actions">
              <button class="btn btn-sm" @click="resetRerunFormFromOriginal" :disabled="rerunSubmitting">
                元に戻す
              </button>
              <button class="btn btn-primary btn-sm" @click="rerunWithEditNewThread" :disabled="rerunSubmitting || isOverLimitAny">
                {{ rerunSubmitting ? '実行中...' : '新しい会話として実行' }}
              </button>
              <button class="btn btn-sm" @click="rerunWithEditSameThread" :disabled="rerunSubmitting || !canAppendSameThread || isOverLimitAny">
                {{ rerunSubmitting ? '実行中...' : 'この会話に追加して実行' }}
              </button>
            </div>
          </div>

          <div class="form-grid">
            <div class="field">
              <label class="label">model</label>
              <select v-model="rerunForm.model" class="control" :disabled="rerunSubmitting">
                <option v-for="m in modelOptions" :key="m.id" :value="m.id">
                  {{ m.name || m.id }}
                </option>
              </select>
              <div class="help">※新規発行の内容です（元レコードは変更しません）</div>
            </div>

            <div class="field">
              <label class="label">request_type</label>
              <select v-model="rerunForm.request_type" class="control" :disabled="rerunSubmitting">
                <option v-for="t in requestTypeOptions" :key="t.id" :value="t.id">
                  {{ t.label || t.id }}
                </option>
              </select>
              <div class="help">※新規発行の内容です（元レコードは変更しません）</div>
            </div>

            <div class="field field-span">
              <label class="label">source_text（修正して再実行）</label>
              <textarea
                v-model="rerunForm.source_text"
                class="control textarea"
                rows="6"
                :disabled="rerunSubmitting"
              ></textarea>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/lib/api'
import { fetchChatGPTConfig } from '@/services/chatgptConfigService'
import { fetchRequests as fetchRequestsApi, createRequest, deleteRequest } from '@/services/requestsService'
import { fetchMonthlyUsage } from '@/services/usageService.js'

// -- ログアウト関連 --
const router = useRouter()
const loggingOut = ref(false)

// -- 一覧表示関連 --
const requests = ref([])
const currentPage = ref(1)
const lastPage = ref(1)
const total = ref(0)
const perPage = ref(10)

const loading = ref(false)
const errorMessage = ref('')

// config
const modelOptions = ref([])
const requestTypeOptions = ref([])
const requestStatusOptions = ref([])

// monthly usage
const usageLoading = ref(false)
const usageError = ref('')
const usageUser = ref(null)
const usageAll  = ref(null)
const usageYearMonth = ref('')

// create
const showCreateFormFlag = ref(false)
const creatingFlag = ref(false)
const createForm = ref({
  model: '',
  request_type: '',
  source_text: '',
})

// detail
const detailOpen = ref(false)
const detailReq = ref(null)
const canAppendSameThread = computed(() => {
  if (!detailReq.value) return false
  // status=2(done) かつ conversation_id があるか判定
  return Number(detailReq.value.status) === 2 && !!detailReq.value.conversation_id
})

// rerun
const rerunSubmitting = ref(false)
const rerunForm = ref({
  model: '',
  request_type: '',
  source_text: '',
})

// 419 error string
const ERROR_CODES = {
  USAGE_LIMIT_EXCEEDED: 'USAGE_LIMIT_EXCEEDED',
}

// ----- 表示用 helper
function formatUsd(v) {
  if (v === null || v === undefined) return '-'
  const n = Number(v)
  if (Number.isNaN(n)) return String(v)
  return n.toFixed(5)
}

function usagePercentRaw(u) {
  if (!u) return 0
  const limit = Number(u.limit_usd)
  const used  = Number(u.estimated_cost_usd)

  if (!Number.isFinite(limit) || limit <= 0) return 0
  if (!Number.isFinite(used)  || used  <  0) return 0

  return (used / limit) * 100
}


// バー表示（0.01%でも見えるように、最低1%にするならここで調整）
function usagePercentBar(u) {
  const p = usagePercentRaw(u)
  if (p <= 0) return 0
  return p < 1 ? 1 : Math.floor(p)
}

// 数字表示（小数2桁）
function usagePercentLabel(u) {
  const p = usagePercentRaw(u)
  if (p <= 0) return '0.00'
  return p.toFixed(2)
}


async function logout() {
  loggingOut.value = true
  errorMessage.value = ''

  try {
    await api.post('/api/logout')
  } catch (e) {
    console.log('logout failed', e)
  } finally {
    localStorage.removeItem('bearer_token')
    localStorage.removeItem('user')
    loggingOut.value = false
    router.replace({ name: 'Login' })
  }
}

// ---- config
async function loadConfig() {
  try {
    const cfg = await fetchChatGPTConfig()
    modelOptions.value = cfg.modelOptions
    requestTypeOptions.value = cfg.requestTypeOptions
    requestStatusOptions.value = cfg.requestStatusOptions

    if (!createForm.value.model && modelOptions.value.length > 0) {
      createForm.value.model = modelOptions.value[0].id
    }
    if (!createForm.value.request_type && requestTypeOptions.value.length > 0) {
      createForm.value.request_type = requestTypeOptions.value[0].id
    }
  } catch (e) {
    console.log('loadConfig failed', e)
    errorMessage.value = 'ChatGPT問い合わせ用情報（model/種別）の取得に失敗しました'
  }
}

// ---- list
async function loadRequests(page = 1) {
  loading.value = true
  errorMessage.value = ''
  try {
    const res = await fetchRequestsApi({ page, perPage: perPage.value })
    const safePage = Math.min(page, res.lastPage || 1)
    if (safePage !== page) {
      loading.value = false
      return loadRequests(safePage)
    }
    requests.value = res.requests
    currentPage.value = res.currentPage
    lastPage.value = res.lastPage
    total.value = res.total
  } catch (e) {
    console.log('loadRequests failed', e)
    if (e.response) {
      errorMessage.value = e.response.data?.message || '一覧データ取得に失敗しました'
    } else {
      errorMessage.value = '通信エラーが発生しました'
    }
  } finally {
    loading.value = false
  }
}

function changePage(page) {
  if (page < 1 || page > lastPage.value) return
  loadRequests(page)
}

// ---- create
function toggleCreateForm() {
  showCreateFormFlag.value = !showCreateFormFlag.value
  errorMessage.value = ''
}

function resetCreateForm() {
  createForm.value = { model: '', request_type: '', source_text: '' }
  errorMessage.value = ''
}

async function requestCreate() {
  errorMessage.value = ''
  if (isOverLimitAny.value) {
    errorMessage.value = limitBlockMessage.value
    return
  }
  if (!createForm.value.model || !createForm.value.request_type || !createForm.value.source_text) {
    errorMessage.value = 'model / request_type / source_text は必須です'
    return
  }

  creatingFlag.value = true
  try {
    await createRequest({
      model: createForm.value.model,
      request_type: createForm.value.request_type,
      source_text: createForm.value.source_text,
    })
    resetCreateForm()
    showCreateFormFlag.value = false
    await loadRequests(1)
    await loadMonthlyUsage()
  } catch (e) {
    console.log('requestCreate failed', e)
    if (e.response) {
      // 429(上限超過)なら usage を取り直して UI を同期
      if (e.response.status === 429 && e.response.data?.code === ERROR_CODES.USAGE_LIMIT_EXCEEDED) {
        await loadMonthlyUsage()
      }
      errorMessage.value = e.response.data?.message || 'リクスト登録に失敗しました'
    } else {
      errorMessage.value = '通信エラーが発生しました'
    }
  } finally {
    creatingFlag.value = false
  }
}

// ---- delete
async function requestDelete(id) {
  if (!confirm(`${id}を削除しますか?`)) return
  try {
    await deleteRequest(id)
    await loadRequests(currentPage.value)
    await loadMonthlyUsage()
  } catch (e) {
    console.log('requestDelete failed', e)
    if (e.response) {
      errorMessage.value = e.response.data?.message || 'リクスト削除に失敗しました'
    } else {
      errorMessage.value = '通信エラーが発生しました'
    }
  }
}

// ---- monthly usage info
async function loadMonthlyUsage() {
  usageLoading.value = true
  usageError.value = ''
  try {
    const ym = usageYearMonth.value?.trim() || null
    const res = await fetchMonthlyUsage({ yearMonth: ym })
    usageUser.value = res?.user ?? null
    usageAll.value  = res?.user_all ?? null
  } catch (e) {
    console.log('loadMonthlyUsage failed', e)
    usageError.value = e.response?.data?.message || '月次利用状況の取得に失敗しました'
    usageUser.value = null
    usageAll.value = null
  } finally {
    usageLoading.value = false
  }
}

// --- 利用料金限度額超え
const isOverLimitAny = computed(() => {
  const u = usageUser.value
  const a = usageAll.value
  const uOver = !!u?.is_over_limit
  const aOver = !!a?.is_over_limit
  return uOver || aOver
})

// --- 利用料金限度額超え時のメッセージ
const limitBlockMessage = computed(() => {
  if (!isOverLimitAny.value) return ''
  const uOver = !!usageUser.value?.is_over_limit
  const aOver = !!usageAll.value?.is_over_limit
  if (uOver && aOver) return '今月の利用上限（あなた / 全体）を超過しているため、新規リクエストできません。'
  if (uOver) return '今月の利用上限（あなた）を超過しているため、新規リクエストできません。'
  return '今月の利用上限（全体）を超過しているため、新規リクエストできません。'
})


// ---- detail + rerun
function openDetail(req) {
  detailReq.value = req
  detailOpen.value = true
  rerunForm.value = {
    model: req?.model || '',
    request_type: req?.request_type || '',
    source_text: req?.source_text || '',
  }
}

function closeDetail() {
  detailOpen.value = false
  detailReq.value = null
  rerunForm.value = { model: '', request_type: '', source_text: '' }
}

function resetRerunFormFromOriginal() {
  if (!detailReq.value) return
  rerunForm.value = {
    model: detailReq.value.model || '',
    request_type: detailReq.value.request_type || '',
    source_text: detailReq.value.source_text || '',
  }
}

async function rerunWithEditNewThread() {
  errorMessage.value = ''
  if (!rerunForm.value.model || !rerunForm.value.request_type || !rerunForm.value.source_text) {
    errorMessage.value = '再実行フォームの model / request_type / source_text は必須です'
    return
  }
  await rerunInternal({
    model: rerunForm.value.model,
    request_type: rerunForm.value.request_type,
    source_text: rerunForm.value.source_text,
  })
}

async function rerunWithEditSameThread() {
  if (!detailReq.value) return
  await rerunInternal({
    model: rerunForm.value.model,
    request_type: rerunForm.value.request_type,
    source_text: rerunForm.value.source_text,
    conversation_id: detailReq.value.conversation_id,
  })
}

async function rerunInternal(payload) {
  if (isOverLimitAny.value) {
    errorMessage.value = limitBlockMessage.value
    return
  }
  rerunSubmitting.value = true
  try {
    await createRequest(payload)
    await loadRequests(1)
    await loadMonthlyUsage() // ★追加：月次も更新
  } catch (e) {
    console.log('rerun failed', e)
    if (e.response) {
      errorMessage.value = e.response.data?.message || '実行に失敗しました'
    } else {
      errorMessage.value = '通信エラーが発生しました'
    }
  } finally {
    rerunSubmitting.value = false
    closeDetail()
  }
}

// ---- helpers
function truncate(text, length) {
  if (!text) return ''
  return text.length > length ? text.slice(0, length) + '...' : text
}

function badgeClass(status) {
  switch (status) {
    case 2: return 'badge-done'
    case 9: return 'badge-failed'
    case 1: return 'badge-processing'
    default: return 'badge-pending'
  }
}

function modelLabel(id) {
  const m = modelOptions.value.find(x => x.id === id)
  return m?.name || id
}

function requestTypeLabel(id) {
  const t = requestTypeOptions.value.find(x => x.id === id)
  return t?.label || id
}

function requestStatusLabel(id) {
  const s = requestStatusOptions.value.find(x => String(x.id) === String(id))
  return s?.label || id
}

onMounted(async () => {
  await loadConfig()
  await loadRequests()
  await loadMonthlyUsage()
})
</script>

<style scoped>
/* ここは「動いた版」のCSSをそのまま貼ってOK（省略せず使ってね） */

.page { padding: 24px 0 48px; background: #fff; }
.container { width: min(1100px, calc(100% - 32px)); margin: 0 auto; }

.header { display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; margin-bottom: 16px; }
.title { margin: 0; font-size: 22px; font-weight: 700; letter-spacing: 0.02em; }
.sub { margin: 4px 0 0; font-size: 12px; color: #666; }

.card { border: 1px solid #e6e6e6; border-radius: 10px; padding: 14px; margin-bottom: 14px; box-shadow: 0 1px 2px rgba(0,0,0,.04); }
.card-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 10px; }
.card-title { margin: 0; font-size: 16px; font-weight: 700; }
.card-actions { display: flex; gap: 8px; }
.meta { color: #666; font-size: 12px; }

.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.field-span { grid-column: 1 / -1; }
.field { min-width: 0; }

.label { display: block; font-size: 12px; color: #555; margin-bottom: 6px; }
.help { margin-top: 6px; font-size: 12px; color: #777; }

.control { width: 100%; border: 1px solid #d8d8d8; border-radius: 8px; padding: 8px 10px; font-size: 14px; outline: none; }
.control:focus { border-color: #b8b8b8; }
.textarea { resize: vertical; min-height: 44px; }

.table-wrap { overflow-x: auto; }
.table { width: 100%; border-collapse: collapse; font-size: 14px; }
.table th, .table td { border-top: 1px solid #eee; padding: 10px 10px; vertical-align: top; }
.table thead th { border-top: none; background: #fafafa; font-size: 12px; color: #555; text-align: left; }
.table tbody tr:hover { background: #fcfcfc; }
.empty { text-align: center; color: #777; padding: 18px 10px; }

.actions { display: flex; gap: 8px; align-items: center; }
.mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono","Courier New", monospace; }

.btn { border: 1px solid #d8d8d8; background: #fff; padding: 8px 12px; border-radius: 8px; cursor: pointer; font-size: 13px; }
.btn:hover { background: #f7f7f7; }
.btn:disabled { opacity: .6; cursor: not-allowed; }

.btn-sm { padding: 6px 10px; font-size: 12px; border-radius: 7px; }
.btn-primary { border-color: #1f6feb; background: #1f6feb; color: #fff; }
.btn-primary:hover { background: #1a5fd0; }
.btn-danger { border-color: #e35b5b; background: #fff; color: #b00020; }
.btn-danger:hover { background: #fff5f5; }

.alert { border-radius: 10px; padding: 10px 12px; margin-bottom: 14px; font-size: 13px; }
.alert-error { background: #fff3f3; border: 1px solid #ffd0d0; color: #a10000; }

.badge { display: inline-block; padding: 3px 8px; border-radius: 999px; font-size: 12px; border: 1px solid #ddd; background: #fafafa; }
.badge-pending { background: #fff7e6; border-color: #ffe1a6; }
.badge-processing { background: #eef6ff; border-color: #b7d9ff; }
.badge-done { background: #eefaf1; border-color: #b7e6c4; }
.badge-failed { background: #fff3f3; border-color: #ffd0d0; }

.pager { display: flex; justify-content: center; align-items: center; gap: 10px; margin-top: 12px; }
.pager-text { font-size: 12px; color: #666; }
.muted { color: #777; font-size: 12px; }

/* 月次利用状況 */
.usage-grid{
  display: grid;
  grid-template-columns: 1fr 280px;
  gap: 14px;
  align-items: start;
}

.usage-kv{
  display: grid;
  grid-template-columns: 120px 1fr;
  gap: 8px 12px;
  font-size: 13px;
}

.usage-kv .k{
  color: #666;
  font-size: 12px;
  padding-top: 2px;
}

.usage-kv .v{
  text-align: left;
}

.usage-panel{
  border: 1px solid #eee;
  border-radius: 10px;
  padding: 12px;
  background: #fafafa;
}

.usage-big-label{
  font-size: 12px;
  color: #666;
}

.usage-big-value{
  font-size: 18px;
  font-weight: 700;
  margin-top: 4px;
}

.usage-sub{
  margin-top: 6px;
  font-size: 12px;
  color: #666;
  display: flex;
  gap: 10px;
  align-items: center;
  flex-wrap: wrap;
}

/* 月次利用状況カードを横並びにする */
.usage-cards{
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px;
  align-items: start;
  margin-bottom: 14px; /* 次のカードとの間隔（任意） */
}

/* 画面が狭い時は縦に戻す */
@media (max-width: 980px) {
  .usage-cards{
    grid-template-columns: 1fr;
  }
}

.over-badge{
  display: inline-block;
  border: 1px solid #ffd0d0;
  background: #fff3f3;
  color: #a10000;
  padding: 2px 8px;
  border-radius: 999px;
  font-size: 12px;
}

.usage-bar{
  margin-top: 10px;
}

.bar{
  height: 8px;
  background: #eaeaea;
  border-radius: 999px;
  overflow: hidden;
}

.bar-fill{
  height: 100%;
  background: #1f6feb;
  width: 0%;
}

.bar-meta{
  margin-top: 6px;
  font-size: 12px;
  color: #666;
}

.danger-text{
  color: #a10000;
}

/* Modal */
.modal-overlay {
  position: fixed; inset: 0; background: rgba(0,0,0,.35);
  display: flex; align-items: center; justify-content: center;
  padding: 18px; z-index: 50;
}
.modal {
  width: min(900px, 100%); background: #fff;
  border-radius: 12px; border: 1px solid #e6e6e6;
  box-shadow: 0 10px 30px rgba(0,0,0,.15); overflow: hidden;
}
.modal-head {
  display: flex; justify-content: space-between; align-items: center;
  padding: 12px 14px; border-bottom: 1px solid #eee; background: #fafafa;
}
.modal-title { font-weight: 700; font-size: 14px; }
.modal-body { padding: 14px; }

.kv { display: grid; grid-template-columns: 90px 1fr; gap: 10px; padding: 8px 0; border-bottom: 1px dashed #eee; }
.k { color: #666; font-size: 12px; }
.v { font-size: 13px; }
.kv-top { align-items: flex-start; }

.pre {
  white-space: pre-wrap; word-break: break-word; text-align: left;
  width: 100%; margin: 0; background: #fcfcfc;
  border: 1px solid #eee; border-radius: 10px;
  padding: 10px; font-size: 13px; line-height: 1.6;
  max-height: 220px; overflow: auto;
}

.divider { height: 1px; background: #eee; margin: 14px 0; }
.section-head { display: flex; justify-content: space-between; align-items: center; gap: 10px; margin-bottom: 10px; }
.section-title { font-weight: 700; font-size: 13px; }
.section-actions { display: flex; gap: 8px; }

@media (max-width: 720px) {
  .form-grid { grid-template-columns: 1fr; }
  .header { align-items: flex-start; flex-direction: column; }
  .kv { grid-template-columns: 80px 1fr; }

  .usage-grid{ grid-template-columns: 1fr; }
}
</style>
