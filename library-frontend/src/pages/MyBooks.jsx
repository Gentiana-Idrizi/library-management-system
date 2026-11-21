import { useState } from 'react';
import { api } from '../api/client.js';
import { useAuth } from '../context/AuthContext.jsx';
import ConfirmDialog from '../components/ConfirmDialog.jsx';
import { useToast } from '../components/Toast.jsx';
import { DueStamp, Empty, ErrorNote, Loading, PageHeader } from '../components/ui.jsx';
import { formatDate, money, plural } from '../utils/format.js';
import { useApi } from '../hooks/useApi.js';
import { Link } from '../utils/router.jsx';
import ReturnSummary from '../components/ReturnSummary.jsx';

export default function MyBooks() {
  const { user, refresh } = useAuth();
  const toast = useToast();
  const [returning, setReturning] = useState(null);
  const open = useApi('/loans', { status: 'open', limit: 50 });
  const settings = useApi('/settings');
  const unpaid = Number(user.unpaidPenaltyTotal ?? 0);
  const loans = open.data?.items ?? [];

  async function giveBack(loan) {
    const result = await api(`/loans/${loan.id}/return`, { method: 'POST' });
    toast(result.message, result.loan.penalty ? 'warn' : 'ok');
    open.reload();
    refresh();
  }

  return (
    <section>
      <PageHeader
        icon="bookmark"
        title="My books"
        intro={settings.data ? `You have ${loans.length} of ${plural(settings.data.borrowLimit, 'book')} you may borrow at once.` : null}
      />

      {unpaid > 0 && (
        <div className="alert alert-danger">
          You owe {money(unpaid)} in penalties. You can't borrow books until it is paid.{' '}
          <Link to="/penalties" className="alert-link">
            Pay penalties
          </Link>
        </div>
      )}

      <ErrorNote error={open.error} onRetry={open.reload} />
      {!open.data && open.loading && <Loading />}

      {open.data && loans.length === 0 && (
        <Empty title="You have no books at the moment.">
          <Link to="/catalog">Find a book</Link> to borrow.
        </Empty>
      )}

      <div className="d-grid gap-3">
        {loans.map((loan) => (
          <article key={loan.id} className={`card panel loan-slip${loan.status === 'overdue' ? ' is-overdue' : ''}`}>
            <div className="card-body d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-4">
              <div>
                <h2 className="book-title">{loan.book.title}</h2>
                <p className="book-author">{loan.book.author}</p>
                <p className="small text-secondary mb-3">
                  Borrowed {formatDate(loan.borrowedAt)}, fee {money(loan.borrowFee)}
                </p>
                {loan.status === 'overdue' && (
                  <p className="text-danger fw-medium mb-3">
                    Penalty so far: {money(loan.accruingPenalty)}. It grows every day until the book is back.
                  </p>
                )}
                <button type="button" className="btn btn-sm btn-primary px-3" onClick={() => setReturning(loan)}>
                  Return book
                </button>
              </div>
              <DueStamp loan={loan} />
            </div>
          </article>
        ))}
      </div>

      {returning && (
        <ConfirmDialog
          title="Return this book?"
          confirmLabel="Return book"
          onConfirm={() => giveBack(returning)}
          onClose={() => setReturning(null)}
        >
          <ReturnSummary loan={returning} />
        </ConfirmDialog>
      )}
    </section>
  );
}
