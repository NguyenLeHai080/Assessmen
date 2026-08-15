import { afterEach, describe, expect, it, vi } from 'vitest'
import { ApiError, request } from './client'

afterEach(() => vi.unstubAllGlobals())

describe('API client', () => {
  it('returns parsed JSON for successful requests', async () => {
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue({ ok: true, status: 200, json: async () => ({ data: [] }) }))
    await expect(request('/assessments')).resolves.toEqual({ data: [] })
  })

  it('normalizes validation errors', async () => {
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue({
      ok: false,
      status: 422,
      json: async () => ({ code: 'assessment_validation_failed', message: 'Invalid.', data: { fields: { title: 'Required.' } } }),
    }))
    await expect(request('/assessments')).rejects.toMatchObject({ status: 422, code: 'assessment_validation_failed', fields: { title: 'Required.' } })
  })

  it('maps network failures to ApiError', async () => {
    vi.stubGlobal('fetch', vi.fn().mockRejectedValue(new Error('offline')))
    await expect(request('/assessments')).rejects.toBeInstanceOf(ApiError)
  })
})

