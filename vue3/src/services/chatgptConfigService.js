import api from '@/lib/api'

function normalizeModels(models) {
  return Array.isArray(models)
    ? models
    : Object.entries(models || {}).map(([id, v]) => ({ id, name: v?.name || id }))
}

function normalizeRequestTypes(requestTypes) {
  return Array.isArray(requestTypes)
    ? requestTypes
    : Object.entries(requestTypes || {}).map(([id, v]) => ({ id, label: v?.label || id }))
}

function normalizeStatuses(statuses) {
  return Array.isArray(statuses)
    ? statuses
    : Object.entries(statuses || {}).map(([id, v]) => ({ id, label: v?.label || id }))
}

export async function fetchChatGPTConfig() {
  const res = await api.get('/api/chatgpt/config')

  return {
    modelOptions: normalizeModels(res.data.models),
    requestTypeOptions: normalizeRequestTypes(res.data.request_types),
    requestStatusOptions: normalizeStatuses(res.data.request_status),
  }
}
