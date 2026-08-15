import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { assessmentApi } from '../api/assessments'
import { EmptyState, ErrorState, LoadingState } from '../components/States'

export function AssessmentListPage() {
  const [page, setPage] = useState(1)
  const [state, setState] = useState({ loading: true, items: [], meta: null, error: null })

  const load = () => {
    setState((current) => ({ ...current, loading: true, error: null }))
    assessmentApi.list({ page })
      .then((payload) => setState({ loading: false, items: payload.data, meta: payload.meta, error: null }))
      .catch((error) => setState({ loading: false, items: [], meta: null, error }))
  }

  useEffect(load, [page])

  return (
    <main>
      <div className="page-heading">
        <div><p className="eyebrow">Knowledge checks</p><h1>Assessments</h1></div>
        <Link className="button" to="/assessments/new">Create assessment</Link>
      </div>
      {state.loading && <LoadingState label="Loading assessments…" />}
      {state.error && <ErrorState error={state.error} onRetry={load} />}
      {!state.loading && !state.error && state.items.length === 0 && <EmptyState message="No published assessments are available." />}
      {!state.loading && !state.error && state.items.length > 0 && (
        <>
          <div className="assessment-grid">
            {state.items.map((item) => (
              <Link className="assessment-card" to={`/assessments/${item.id}`} key={item.id}>
                <span className={`status status--${item.status}`}>{item.status}</span>
                <h2>{item.title}</h2>
                <p>{item.description || 'No description provided.'}</p>
                <span className="card-link">Open assessment →</span>
              </Link>
            ))}
          </div>
          <nav className="pagination" aria-label="Assessment pages">
            <button type="button" disabled={page <= 1} onClick={() => setPage((value) => value - 1)}>Previous</button>
            <span>Page {state.meta?.page || page} of {Math.max(state.meta?.total_pages || 1, 1)}</span>
            <button type="button" disabled={!state.meta || page >= state.meta.total_pages} onClick={() => setPage((value) => value + 1)}>Next</button>
          </nav>
        </>
      )}
    </main>
  )
}

