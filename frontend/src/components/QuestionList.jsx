import { useEffect, useState } from 'react'
import { answerApi } from '../api/assessments'
import { EmptyState, ErrorState, LoadingState } from './States'

function Question({ question, canManage }) {
  const [state, setState] = useState({ loading: true, answers: [], error: null })
  const [form, setForm] = useState({ content: '', score: 0, sort_order: 0 })
  const [submitting, setSubmitting] = useState(false)
  const [formError, setFormError] = useState(null)

  const load = () => {
    setState((current) => ({ ...current, loading: true, error: null }))
    answerApi.list(question.id)
      .then((payload) => setState({ loading: false, answers: payload.data, error: null }))
      .catch((error) => setState({ loading: false, answers: [], error }))
  }

  useEffect(load, [question.id])

  const createAnswer = async (event) => {
    event.preventDefault()
    if (!form.content.trim()) {
      setFormError({ status: 422, fields: { content: 'Answer content is required.' } })
      return
    }
    setSubmitting(true)
    setFormError(null)
    try {
      const payload = await answerApi.create({
        ...form,
        question_id: Number(question.id),
        score: Number(form.score),
        sort_order: Number(form.sort_order),
      })
      setState((current) => ({ ...current, answers: [...current.answers, payload.data].sort((a, b) => a.sort_order - b.sort_order || a.id - b.id) }))
      setForm({ content: '', score: 0, sort_order: 0 })
    } catch (error) {
      setFormError(error)
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <article className="question">
      <div className="question-heading"><h3>{question.content}</h3><span className={`status status--${question.status}`}>{question.status}</span></div>
      {state.loading && <LoadingState label="Loading answers…" />}
      {state.error && <ErrorState error={state.error} onRetry={load} />}
      {!state.loading && !state.error && state.answers.length === 0 && <EmptyState message="No answers have been added." />}
      {!state.loading && !state.error && state.answers.length > 0 && (
        <ol className="answers">
          {state.answers.map((answer) => <li key={answer.id}><span>{answer.content}</span>{canManage && answer.score !== undefined && <strong>{answer.score} pts</strong>}</li>)}
        </ol>
      )}
      {canManage && (
        <form className="answer-form" onSubmit={createAnswer}>
          <h4>Add answer</h4>
          {formError && <ErrorState error={formError} />}
          <label>Content<input value={form.content} onChange={(event) => setForm((current) => ({ ...current, content: event.target.value }))} placeholder="Enter an answer" aria-invalid={Boolean(formError?.fields?.content)} /></label>
          <div className="form-row">
            <label>Score<input type="number" min="0" max="100" step="0.01" value={form.score} onChange={(event) => setForm((current) => ({ ...current, score: event.target.value }))} /></label>
            <label>Sort order<input type="number" min="0" value={form.sort_order} onChange={(event) => setForm((current) => ({ ...current, sort_order: event.target.value }))} /></label>
          </div>
          <button className="button button--secondary button--small" disabled={submitting}>{submitting ? 'Adding…' : 'Add answer'}</button>
        </form>
      )}
    </article>
  )
}

export function QuestionList({ questions, canManage = false }) {
  if (questions.length === 0) return <EmptyState message="This assessment has no questions yet." />
  return <div className="question-list">{questions.map((question) => <Question key={question.id} question={question} canManage={canManage} />)}</div>
}
