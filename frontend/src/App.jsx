import { HashRouter, Link, Route, Routes } from 'react-router-dom'
import { AssessmentCreatePage } from './pages/AssessmentCreatePage'
import { AssessmentDetailPage } from './pages/AssessmentDetailPage'
import { AssessmentListPage } from './pages/AssessmentListPage'

export default function App() {
  return (
    <HashRouter>
      <header className="site-header"><Link to="/" className="brand"><span>MA</span> Mini Assessment</Link></header>
      <Routes>
        <Route path="/" element={<AssessmentListPage />} />
        <Route path="/assessments/new" element={<AssessmentCreatePage />} />
        <Route path="/assessments/:id" element={<AssessmentDetailPage />} />
        <Route path="*" element={<main><h1>Page not found</h1><Link to="/">Return home</Link></main>} />
      </Routes>
    </HashRouter>
  )
}
