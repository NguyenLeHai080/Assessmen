import { useEffect, useState } from 'react'
import { answerApi } from '../api/assessments'
import { EmptyState, ErrorState, LoadingState } from './States'

function Question({ question }) {
  const [state, setState] = useState({ loading: true, answers: [], error: null })

  const load = () => {
    setState((current) => ({ ...current, loading: true, error: null }))
    answerApi.list(question.id)
      .then((payload) => setState({ loading: false, answers: payload.data, error: null }))
      .catch((error) => setState({ loading: false, answers: [], error }))
  }

  useEffect(load, [question.id])

  return (
    <article className="question">
      <h3>{question.content}</h3>
      {state.loading && <LoadingState label="Loading answers…" />}
      {state.error && <ErrorState error={state.error} onRetry={load} />}
      {!state.loading && !state.error && state.answers.length === 0 && <EmptyState message="No answers have been added." />}
      {!state.loading && !state.error && state.answers.length > 0 && (
        <ol className="answers">
          {state.answers.map((answer) => <li key={answer.id}>{answer.content}</li>)}
        </ol>
      )}
    </article>
  )
}

export function QuestionList({ questions }) {
  if (questions.length === 0) return <EmptyState message="This assessment has no questions yet." />
  return <div className="question-list">{questions.map((question) => <Question key={question.id} question={question} />)}</div>
}

