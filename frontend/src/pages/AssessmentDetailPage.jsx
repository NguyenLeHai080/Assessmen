import { useEffect, useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import { assessmentApi, questionApi } from '../api/assessments'
import { QuestionList } from '../components/QuestionList'
import { ErrorState, LoadingState } from '../components/States'

export function AssessmentDetailPage() {
  const { id } = useParams()
  const [state, setState] = useState({ loading: true, assessment: null, questions: [], error: null })

  const load = () => {
    setState((current) => ({ ...current, loading: true, error: null }))
    Promise.all([assessmentApi.get(id), questionApi.list(id)])
      .then(([assessment, questions]) => setState({ loading: false, assessment: assessment.data, questions: questions.data, error: null }))
      .catch((error) => setState({ loading: false, assessment: null, questions: [], error }))
  }

  useEffect(load, [id])

  if (state.loading) return <main><LoadingState label="Loading assessment…" /></main>
  if (state.error) return <main><ErrorState error={state.error} onRetry={load} /></main>

  return (
    <main>
      <Link className="back-link" to="/">← All assessments</Link>
      <header className="detail-header">
        <span className={`status status--${state.assessment.status}`}>{state.assessment.status}</span>
        <h1>{state.assessment.title}</h1>
        {state.assessment.description && <p>{state.assessment.description}</p>}
      </header>
      <section aria-labelledby="questions-heading">
        <h2 id="questions-heading">Questions</h2>
        <QuestionList questions={state.questions} />
      </section>
    </main>
  )
}

