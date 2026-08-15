import { afterEach, describe, expect, it, vi } from 'vitest'
import { answerApi, assessmentApi, questionApi } from './assessments'

afterEach(() => vi.unstubAllGlobals())

function mockSuccess(data = {}) {
  const fetch = vi.fn().mockResolvedValue({ ok: true, status: 200, json: async () => ({ data }) })
  vi.stubGlobal('fetch', fetch)
  return fetch
}

describe('assessment management API', () => {
  it('updates an assessment with PATCH', async () => {
    const fetch = mockSuccess({ id: 7 })
    await assessmentApi.update(7, { title: 'Updated' })
    expect(fetch).toHaveBeenCalledWith(expect.stringMatching(/\/assessments\/7$/), expect.objectContaining({ method: 'PATCH', body: JSON.stringify({ title: 'Updated' }) }))
  })

  it('deletes an assessment', async () => {
    const fetch = mockSuccess({ deleted: true })
    await assessmentApi.remove(7)
    expect(fetch).toHaveBeenCalledWith(expect.stringMatching(/\/assessments\/7$/), expect.objectContaining({ method: 'DELETE' }))
  })

  it('creates a question for an assessment', async () => {
    const fetch = mockSuccess({ id: 8 })
    const body = { assessment_id: 7, content: 'Question', sort_order: 1, status: 'active' }
    await questionApi.create(body)
    expect(fetch).toHaveBeenCalledWith(expect.stringMatching(/\/questions$/), expect.objectContaining({ method: 'POST', body: JSON.stringify(body) }))
  })

  it('creates a scored answer for a question', async () => {
    const fetch = mockSuccess({ id: 9 })
    const body = { question_id: 8, content: 'Answer', score: 10, sort_order: 1 }
    await answerApi.create(body)
    expect(fetch).toHaveBeenCalledWith(expect.stringMatching(/\/answers$/), expect.objectContaining({ method: 'POST', body: JSON.stringify(body) }))
  })
})
