import { useEffect, useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { assessmentApi, questionApi } from '../api/assessments'
import { QuestionList } from '../components/QuestionList'
import { ErrorState, LoadingState } from '../components/States'

const isAuthenticated = Boolean(window.miniAssessmentSettings?.isAuthenticated)

export function AssessmentDetailPage() {
  const { id } = useParams()
  const navigate = useNavigate()
  const [state, setState] = useState({ loading: true, assessment: null, questions: [], error: null })
  const [editing, setEditing] = useState(false)
  const [editForm, setEditForm] = useState({ title: '', description: '', status: 'draft' })
  const [questionForm, setQuestionForm] = useState({ content: '', sort_order: 0, status: 'active' })
  const [action, setAction] = useState({ submitting: false, deleting: false, error: null })

  const load = () => {
    setState((current) => ({ ...current, loading: true, error: null }))
    Promise.all([assessmentApi.get(id), questionApi.list(id)])
      .then(([assessment, questions]) => {
        setState({ loading: false, assessment: assessment.data, questions: questions.data, error: null })
        setEditForm({ title: assessment.data.title, description: assessment.data.description || '', status: assessment.data.status })
      })
      .catch((error) => setState({ loading: false, assessment: null, questions: [], error }))
  }

  useEffect(load, [id])

  const updateAssessment = async (event) => {
    event.preventDefault()
    if (!editForm.title.trim()) {
      setAction((current) => ({ ...current, error: { status: 422, fields: { title: 'Title is required.' } } }))
      return
    }
    setAction({ submitting: true, deleting: false, error: null })
    try {
      const payload = await assessmentApi.update(id, editForm)
      setState((current) => ({ ...current, assessment: payload.data }))
      setEditing(false)
    } catch (error) {
      setAction((current) => ({ ...current, error }))
    } finally {
      setAction((current) => ({ ...current, submitting: false }))
    }
  }

  const deleteAssessment = async () => {
    if (!window.confirm('Delete this assessment and all of its questions and answers?')) return
    setAction({ submitting: false, deleting: true, error: null })
    try {
      await assessmentApi.remove(id)
      navigate('/')
    } catch (error) {
      setAction((current) => ({ ...current, deleting: false, error }))
    }
  }

  const createQuestion = async (event) => {
    event.preventDefault()
    if (!questionForm.content.trim()) {
      setAction((current) => ({ ...current, error: { status: 422, fields: { content: 'Question content is required.' } } }))
      return
    }
    setAction({ submitting: true, deleting: false, error: null })
    try {
      const payload = await questionApi.create({ ...questionForm, assessment_id: Number(id), sort_order: Number(questionForm.sort_order) })
      setState((current) => ({ ...current, questions: [...current.questions, payload.data].sort((a, b) => a.sort_order - b.sort_order || a.id - b.id) }))
      setQuestionForm({ content: '', sort_order: 0, status: 'active' })
    } catch (error) {
      setAction((current) => ({ ...current, error }))
    } finally {
      setAction((current) => ({ ...current, submitting: false }))
    }
  }

  if (state.loading) return <main><LoadingState label="Loading assessment…" /></main>
  if (state.error) return <main><ErrorState error={state.error} onRetry={load} /></main>

  return (
    <main>
      <Link className="back-link" to="/">← All assessments</Link>
      <header className="detail-header">
        <div className="detail-toolbar">
          <span className={`status status--${state.assessment.status}`}>{state.assessment.status}</span>
          {isAuthenticated && (
            <div className="button-group">
              <button className="button button--secondary button--small" type="button" onClick={() => setEditing((value) => !value)}>Edit</button>
              <button className="button button--danger button--small" type="button" disabled={action.deleting} onClick={deleteAssessment}>{action.deleting ? 'Deleting…' : 'Delete'}</button>
            </div>
          )}
        </div>
        <h1>{state.assessment.title}</h1>
        {state.assessment.description && <p>{state.assessment.description}</p>}
      </header>

      {action.error && <ErrorState error={action.error} />}

      {isAuthenticated && editing && (
        <section className="management-panel" aria-labelledby="edit-assessment-heading">
          <h2 id="edit-assessment-heading">Edit assessment</h2>
          <form onSubmit={updateAssessment}>
            <label>Title<input value={editForm.title} maxLength="255" onChange={(event) => setEditForm((current) => ({ ...current, title: event.target.value }))} aria-invalid={Boolean(action.error?.fields?.title)} /></label>
            {action.error?.fields?.title && <p className="field-error">{action.error.fields.title}</p>}
            <label>Description<textarea rows="4" value={editForm.description} onChange={(event) => setEditForm((current) => ({ ...current, description: event.target.value }))} /></label>
            <label>Status<select value={editForm.status} onChange={(event) => setEditForm((current) => ({ ...current, status: event.target.value }))}><option value="draft">Draft</option><option value="published">Published</option><option value="archived">Archived</option></select></label>
            <div className="button-group"><button className="button" disabled={action.submitting}>{action.submitting ? 'Saving…' : 'Save changes'}</button><button className="button button--secondary" type="button" onClick={() => setEditing(false)}>Cancel</button></div>
          </form>
        </section>
      )}

      <section aria-labelledby="questions-heading">
        <div className="section-heading"><div><p className="eyebrow">Assessment content</p><h2 id="questions-heading">Questions</h2></div></div>
        {isAuthenticated && (
          <form className="inline-form" onSubmit={createQuestion}>
            <label>New question<textarea rows="2" value={questionForm.content} onChange={(event) => setQuestionForm((current) => ({ ...current, content: event.target.value }))} placeholder="Enter the question content" aria-invalid={Boolean(action.error?.fields?.content)} /></label>
            {action.error?.fields?.content && <p className="field-error">{action.error.fields.content}</p>}
            <div className="form-row">
              <label>Sort order<input type="number" min="0" value={questionForm.sort_order} onChange={(event) => setQuestionForm((current) => ({ ...current, sort_order: event.target.value }))} /></label>
              <label>Status<select value={questionForm.status} onChange={(event) => setQuestionForm((current) => ({ ...current, status: event.target.value }))}><option value="active">Active</option><option value="inactive">Inactive</option></select></label>
            </div>
            <button className="button" disabled={action.submitting}>{action.submitting ? 'Adding…' : 'Add question'}</button>
          </form>
        )}
        <QuestionList questions={state.questions} canManage={isAuthenticated} />
      </section>
    </main>
  )
}
