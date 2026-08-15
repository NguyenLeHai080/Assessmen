import { request } from './client'

export const assessmentApi = {
  list: ({ page = 1, perPage = 10 } = {}) => request(`/assessments?page=${page}&per_page=${perPage}`),
  get: (id) => request(`/assessments/${id}`),
  create: (data) => request('/assessments', { method: 'POST', body: data }),
  update: (id, data) => request(`/assessments/${id}`, { method: 'PATCH', body: data }),
  remove: (id) => request(`/assessments/${id}`, { method: 'DELETE' }),
}

export const questionApi = {
  list: (assessmentId) => request(`/assessments/${assessmentId}/questions`),
  create: (data) => request('/questions', { method: 'POST', body: data }),
}

export const answerApi = {
  list: (questionId) => request(`/questions/${questionId}/answers`),
  create: (data) => request('/answers', { method: 'POST', body: data }),
}

