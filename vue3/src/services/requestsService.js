import api from '@/lib/api'

export async function fetchRequests({ page = 1, perPage = 10 } = {}) {
  const res = await api.get('/api/requests', {
    params: { page, per_page: perPage },
  })

  return {
    requests: (res.data.requests || []).map(r => ({ ...r, status: Number(r.status) })),
    currentPage: res.data.current_page,
    lastPage: res.data.last_page,
    total: res.data.total,
  }
}

export async function createRequest(payload) {
  // payload: { model, request_type, source_text }
  return api.post('/api/requests', payload)
}

export async function deleteRequest(id) {
  return api.delete(`/api/requests/${id}`)
}
