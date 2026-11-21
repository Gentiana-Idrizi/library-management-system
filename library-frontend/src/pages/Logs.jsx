import { useState } from 'react';
import { Empty, ErrorNote, Loading, PageHeader, Pager } from '../components/ui.jsx';
import { formatDateTime, ROLE_LABELS } from '../utils/format.js';
import { useApi } from '../hooks/useApi.js';

const LIMIT = 50;

const ACTION_LABELS = {
  BOOK_BORROWED: 'Book borrowed',
  BOOK_RETURNED: 'Book returned',
  PENALTY_ISSUED: 'Penalty issued',
  PENALTY_PAID: 'Penalty paid',
  BOOK_CREATED: 'Book added',
  BOOK_UPDATED: 'Book edited',
  BOOK_DELETED: 'Book deleted',
  USER_CREATED: 'Account created',
  USER_UPDATED: 'Account edited',
  USER_DELETED: 'Account deleted',
  SETTINGS_UPDATED: 'Settings changed',
};

const GROUPS = [
  ['', 'Everything'],
  ['loan', 'Borrowing and returns'],
  ['penalty', 'Penalties'],
  ['book', 'Inventory'],
  ['user', 'Accounts'],
  ['system_setting', 'Settings'],
];

function describe(details) {
  if (!details || Object.keys(details).length === 0) return '';
  if (details.changes) {
    return Object.entries(details.changes)
      .map(([field, change]) => (typeof change === 'object' ? `${field}: ${change.old} to ${change.new}` : `${field} ${change}`))
      .join('; ');
  }
  return Object.entries(details)
    .filter(([, v]) => v !== null && v !== '' && typeof v !== 'object')
    .map(([k, v]) => `${k}: ${v}`)
    .join('; ');
}

export default function Logs() {
  const [entityType, setEntityType] = useState('');
  const [actionType, setActionType] = useState('');
  const [from, setFrom] = useState('');
  const [to, setTo] = useState('');
  const [page, setPage] = useState(1);
  const logs = useApi('/logs', { entityType, actionType, from, to, page, limit: LIMIT });

  const reset = (fn) => (e) => {
    fn(e.target.value);
    setPage(1);
  };

  return (
    <section>
      <PageHeader icon="list" title="System log" />

      <div className="row g-2 mb-3">
        <div className="col-sm-6 col-lg-3">
          <select className="form-select" aria-label="Area" value={entityType} onChange={reset(setEntityType)}>
            {GROUPS.map(([v, l]) => (
              <option key={v} value={v}>
                {l}
              </option>
            ))}
          </select>
        </div>
        <div className="col-sm-6 col-lg-3">
          <select className="form-select" aria-label="Action" value={actionType} onChange={reset(setActionType)}>
            <option value="">Any action</option>
            {Object.entries(ACTION_LABELS).map(([v, l]) => (
              <option key={v} value={v}>
                {l}
              </option>
            ))}
          </select>
        </div>
        <div className="col-sm-6 col-lg-3">
          <div className="input-group">
            <span className="input-group-text">From</span>
            <input type="date" className="form-control" value={from} onChange={reset(setFrom)} />
          </div>
        </div>
        <div className="col-sm-6 col-lg-3">
          <div className="input-group">
            <span className="input-group-text">To</span>
            <input type="date" className="form-control" value={to} onChange={reset(setTo)} />
          </div>
        </div>
      </div>

      <ErrorNote error={logs.error} onRetry={logs.reload} />
      {!logs.data && logs.loading && <Loading />}
      {logs.data && logs.data.items.length === 0 && <Empty title="Nothing was logged for these filters." />}
      {logs.data && logs.data.items.length > 0 && (
        <div className="card panel">
          <div className="table-responsive">
            <table className="table table-hover align-top mb-0 small">
              <thead>
                <tr>
                  <th scope="col">When</th>
                  <th scope="col">What</th>
                  <th scope="col">Who</th>
                  <th scope="col">Record</th>
                  <th scope="col">Details</th>
                </tr>
              </thead>
              <tbody>
                {logs.data.items.map((log) => (
                  <tr key={log.id}>
                    <td className="text-nowrap">{formatDateTime(log.timestamp)}</td>
                    <td>{ACTION_LABELS[log.actionType] ?? log.actionType}</td>
                    <td>{log.actor ? `${log.actor.name} (${ROLE_LABELS[log.actor.role]})` : 'System'}</td>
                    <td className="text-nowrap">{log.entityId ? `${log.entityType} #${log.entityId}` : log.entityType}</td>
                    <td className="text-secondary log-details">{describe(log.details)}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}
      {logs.data && <Pager page={page} limit={LIMIT} total={logs.data.total} onPage={setPage} />}
    </section>
  );
}
