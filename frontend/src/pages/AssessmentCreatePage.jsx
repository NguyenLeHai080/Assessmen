import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { assessmentApi } from '../api/assessments'
import { ErrorState } from '../components/States'

export function AssessmentCreatePage() {
  const navigate = useNavigate()
  const [form, setForm] = useState({ title: '', description: '', status: 'draft' })
  const [submitting, setSubmitting] = useState(false)
  const [error, setError] = useState(null)

  const submit = async (event) => {
    event.preventDefault()
    if (!form.title.trim()) {
      setError({ status: 422, fields: { title: 'Title is required.' } })
      return
    }
    setSubmitting(true)
    setError(null)
    try {
      const payload = await assessmentApi.create(form)
      navigate(`/assessments/${payload.data.id}`)
    } catch (requestError) {
      setError(requestError)
    } finally {
      setSubmitting(false)
    }
  }

  const update = (event) => setForm((current) => ({ ...current, [event.target.name]: event.target.value }))

  return (
    <main className="form-page">
      <Link className="back-link" to="/">← Cancel</Link>
      <div className="form-card">
        <p className="eyebrow">Administration</p>
        <h1>Create assessment</h1>
        {error && <ErrorState error={error} />}
        <form onSubmit={submit} noValidate>
          <label>Title<input name="title" value={form.title} onChange={update} maxLength="255" aria-invalid={Boolean(error?.fields?.title)} /></label>
          {error?.fields?.title && <p className="field-error">{error.fields.title}</p>}
          <label>Description<textarea name="description" value={form.description} onChange={update} rows="5" /></label>
          <label>Status<select name="status" value={form.status} onChange={update}><option value="draft">Draft</option><option value="published">Published</option></select></label>
          <button className="button" type="submit" disabled={submitting}>{submitting ? 'Creating…' : 'Create assessment'}</button>
        </form>
      </div>
    </main>
  )
}

