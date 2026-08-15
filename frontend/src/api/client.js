const runtimeSettings = typeof window !== 'undefined' ? (window.miniAssessmentSettings || {}) : {}
const baseUrl = (runtimeSettings.apiBase || import.meta.env.VITE_API_BASE_URL || '/wp-json/assessment/v1').replace(/\/$/, '')

export class ApiError extends Error {
  constructor(message, status, code, fields = {}) {
    super(message)
    this.name = 'ApiError'
    this.status = status
    this.code = code
    this.fields = fields
  }
}

export async function request(path, options = {}) {
  const headers = { Accept: 'application/json', ...options.headers }
  const nonce = runtimeSettings.nonce || import.meta.env.VITE_WP_NONCE

  if (options.body) headers['Content-Type'] = 'application/json'
  if (nonce) headers['X-WP-Nonce'] = nonce

  let response
  try {
    response = await fetch(`${baseUrl}${path}`, {
      credentials: 'include',
      ...options,
      headers,
      body: options.body ? JSON.stringify(options.body) : undefined,
    })
  } catch {
    throw new ApiError('Unable to connect to the server.', 0, 'network_error')
  }

  const payload = response.status === 204 ? null : await response.json().catch(() => null)
  if (!response.ok) {
    throw new ApiError(
      payload?.message || 'The request could not be completed.',
      response.status,
      payload?.code || 'unknown_error',
      payload?.data?.fields || {},
    )
  }

  return payload
}
