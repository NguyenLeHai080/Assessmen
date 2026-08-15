export function LoadingState({ label = 'Loading…' }) {
  return <div className="state state--loading" role="status">{label}</div>
}

export function EmptyState({ message = 'No data is available.' }) {
  return <div className="state">{message}</div>
}

export function ErrorState({ error, onRetry }) {
  const messages = {
    0: 'Cannot connect to the server. Check the API URL and try again.',
    401: 'Please sign in to continue.',
    403: 'You do not have permission to perform this action.',
    404: 'The requested content could not be found.',
    422: 'Please correct the highlighted information.',
    500: 'The server encountered an error. Please try again.',
  }
  return (
    <div className="state state--error" role="alert">
      <p>{messages[error?.status] || error?.message || 'Something went wrong.'}</p>
      {onRetry && <button type="button" className="button button--secondary" onClick={onRetry}>Try again</button>}
    </div>
  )
}

