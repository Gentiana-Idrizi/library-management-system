import { useState } from 'react';
import Icon from '../components/Icon.jsx';
import { Empty, ErrorNote, Loading, PageHeader, Pager } from '../components/ui.jsx';
import { formatDate } from '../utils/format.js';
import { useApi } from '../hooks/useApi.js';
import { useDebounced } from '../hooks/useDebounced.js';
import { Link } from '../utils/router.jsx';

const LIMIT = 20;

export default function Students() {
  const [q, setQ] = useState('');
  const [page, setPage] = useState(1);
  const search = useDebounced(q);
  const students = useApi('/users', { role: 'student', q: search, page, limit: LIMIT });

  return (
    <section>
      <PageHeader icon="users" title="Students" />

      <div className="input-group mb-3 search-box">
        <span className="input-group-text bg-white">
          <Icon name="search" size={18} />
        </span>
        <input
          type="search"
          className="form-control border-start-0"
          placeholder="Name or email"
          aria-label="Search students"
          value={q}
          onChange={(e) => {
            setQ(e.target.value);
            setPage(1);
          }}
        />
      </div>

      <ErrorNote error={students.error} onRetry={students.reload} />
      {!students.data && students.loading && <Loading />}
      {students.data && students.data.items.length === 0 && <Empty title="No students found." />}
      {students.data && students.data.items.length > 0 && (
        <div className="card panel">
          <div className="table-responsive">
            <table className="table table-hover align-middle mb-0">
              <thead>
                <tr>
                  <th scope="col">Name</th>
                  <th scope="col">Email</th>
                  <th scope="col">Member since</th>
                </tr>
              </thead>
              <tbody>
                {students.data.items.map((s) => (
                  <tr key={s.id}>
                    <td>
                      <Link to={`/students/${s.id}`} className="fw-semibold">
                        {s.name}
                      </Link>
                    </td>
                    <td>{s.email}</td>
                    <td>{formatDate(s.createdAt)}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}
      {students.data && <Pager page={page} limit={LIMIT} total={students.data.total} onPage={setPage} />}
    </section>
  );
}
