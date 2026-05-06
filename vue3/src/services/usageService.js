import api from '@/lib/api'

/**
 * 月次利用状況を取得
 * @param {Object} params
 * @param {string|null} params.yearMonth - 'YYYY-MM' 形式（例: '2026-01'）
 */
export async function fetchMonthlyUsage({ yearMonth = null } = {}) {
  const res = await api.get('/api/usage/monthly', {
    params: yearMonth ? { year_month: yearMonth } : {},
  })
  return res.data
}
