import { formatDate, LOAN_STATUS_LABELS, plural } from '../utils/format.js';
import Icon from './Icon.jsx';

export function Field({ label, error, hint, children, id }) {
  return (
    <div className="mb-3">
      <label htmlFor={id} className="form-label">
        {label}
      </label>
      {children}
      {error ? <div className="invalid-feedback d-block">{error}</div> : hint && <div className="form-text">{hint}</div>}
    </div>
  );
}

export function ErrorNote({ error, onRetry }) {
  if (!error) return null;
  return (
    <div className="alert alert-danger d-flex align-items-center justify-content-between gap-3" role="alert">
      <span>{error.message}</span>
      {onRetry && (
        <button type="button" className="btn btn-sm btn-outline-danger" onClick={onRetry}>
          Try again
        </button>
      )}
    </div>
  );
}

export function Empty({ title, children }) {
  return (
    <div className="empty-state">
      <p className="empty-state-title">{title}</p>
      {children && <p className="mb-0">{children}</p>}
    </div>
  );
}

export function Loading() {
  return (
    <div className="d-flex align-items-center gap-2 text-secondary py-4">
      <span className="spinner-border spinner-border-sm" aria-hidden="true" />
      Loading…
    </div>
  );
}

export function PageHeader({ icon, title, intro, children }) {
  return (
    <div className="page-header">
      <div className="d-flex align-items-center gap-3">
        {icon && (
          <span className="icon-tile">
            <Icon name={icon} />
          </span>
        )}
        <div>
          <h1 className="page-title">{title}</h1>
          {intro && <p className="page-intro">{intro}</p>}
        </div>
      </div>
      {children && <div className="d-flex flex-wrap gap-2">{children}</div>}
    </div>
  );
}

export function LoanStatus({ status }) {
  return <span className={`status status-${status}`}>{LOAN_STATUS_LABELS[status] ?? status}</span>;
}

export function Availability({ book }) {
  return book.available ? (
    <span className="status status-available">{plural(book.stock, 'copy', 'copies')} on the shelf</span>
  ) : (
    <span className="status status-unavailable">Unavailable</span>
  );
}

export function DueStamp({ loan }) {
  if (loan.status === 'overdue') {
    return (
      <div className="due-stamp due-stamp-overdue" aria-label={`Overdue by ${plural(loan.daysOverdue, 'day')}`}>
        <span className="due-stamp-label">Overdue</span>
        <span className="due-stamp-date">{plural(loan.daysOverdue, 'day')}</span>
        <span className="due-stamp-note">was due {formatDate(loan.dueAt)}</span>
      </div>
    );
  }
  return (
    <div className="due-stamp" aria-label={`Return by ${formatDate(loan.dueAt)}`}>
      <span className="due-stamp-label">Return by</span>
      <span className="due-stamp-date">{formatDate(loan.dueAt)}</span>
    </div>
  );
}

export function Pager({ page, limit, total, onPage }) {
  const pages = Math.max(1, Math.ceil(total / limit));
  if (pages <= 1) return null;
  return (
    <nav className="d-flex align-items-center justify-content-center gap-3 mt-4" aria-label="Pages">
      <button type="button" className="btn btn-sm btn-light border" disabled={page <= 1} onClick={() => onPage(page - 1)}>
        Previous
      </button>
      <span className="small text-secondary">
        Page {page} of {pages}
      </span>
      <button type="button" className="btn btn-sm btn-light border" disabled={page >= pages} onClick={() => onPage(page + 1)}>
        Next
      </button>
    </nav>
  );
}

export function Tabs({ value, onChange, options, label }) {
  return (
    <ul className="nav nav-tabs mb-3" role="tablist" aria-label={label}>
      {options.map(([key, text]) => (
        <li className="nav-item" key={key} role="presentation">
          <button
            type="button"
            role="tab"
            aria-selected={value === key}
            className={`nav-link${value === key ? ' active' : ''}`}
            onClick={() => onChange(key)}
          >
            {text}
          </button>
        </li>
      ))}
    </ul>
  );
}
