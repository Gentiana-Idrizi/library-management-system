import { useState } from 'react';
import { api } from '../api/client.js';
import ConfirmDialog from '../components/ConfirmDialog.jsx';
import { useToast } from '../components/Toast.jsx';
import { Empty, ErrorNote, Loading, PageHeader, Pager } from '../components/ui.jsx';
import { money, plural } from '../utils/format.js';
import { useApi } from '../hooks/useApi.js';
import { Link } from '../utils/router.jsx';
import LoanTable from '../components/LoanTable.jsx';
import ReturnSummary from '../components/ReturnSummary.jsx';

const LIMIT = 25;

export default function StudentDetail({ id }) {
  const toast = useToast();
  const [page, setPage] = useState(1);
  const [dialog, setDialog] = useState(null);
  const history = useApi(`/users/${id}/history`, { page, limit: LIMIT });
  const data = history.data;
  const owes = data && Number(data.unpaidPenaltyTotal) > 0;

  async function processReturn(loan) {
    const result = await api(`/loans/${loan.id}/return`, { method: 'POST' });
    toast(result.message, result.loan.penalty ? 'warn' : 'ok');
    history.reload();
  }

  async function recordPayment(penalty) {
    const result = await api(`/penalties/${penalty.id}/pay`, { method: 'POST' });
    toast(result.message);
    history.reload();
  }

  return (
    <section>
      <nav aria-label="breadcrumb">
        <ol className="breadcrumb small mb-2">
          <li className="breadcrumb-item">
            <Link to="/students">Students</Link>
          </li>
          {data && <li className="breadcrumb-item active">{data.user.name}</li>}
        </ol>
      </nav>

      <ErrorNote error={history.error} onRetry={history.reload} />
      {!data && history.loading && <Loading />}

      {data && (
        <>
          <PageHeader icon="user" title={data.user.name} intro={data.user.email}>
            <div className={`balance${owes ? ' is-owing' : ''}`}>
              <span className="balance-amount">{money(data.unpaidPenaltyTotal)}</span>
              <span className="balance-label">{owes ? 'unpaid penalties, borrowing blocked' : 'nothing owed'}</span>
            </div>
          </PageHeader>

          {data.unpaidPenalties.length > 0 && (
            <div className="card panel border-start border-danger border-3 mb-4">
              <div className="card-header bg-white fw-semibold">Unpaid penalties</div>
              <ul className="list-group list-group-flush">
                {data.unpaidPenalties.map((p) => (
                  <li key={p.id} className="list-group-item d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <span>
                      <strong>{p.loan.book.title}</strong>, {plural(p.daysLate, 'day')} late: {money(p.amount)}
                    </span>
                    <button type="button" className="btn btn-sm btn-primary" onClick={() => setDialog({ type: 'pay', item: p })}>
                      Record payment
                    </button>
                  </li>
                ))}
              </ul>
            </div>
          )}

          <h2 className="section-title">Borrowing history</h2>
          {data.loans.items.length === 0 ? (
            <Empty title="This student hasn't borrowed anything yet." />
          ) : (
            <LoanTable loans={data.loans.items} onReturn={(loan) => setDialog({ type: 'return', item: loan })} />
          )}
          <Pager page={page} limit={LIMIT} total={data.loans.total} onPage={setPage} />
        </>
      )}

      {dialog?.type === 'return' && (
        <ConfirmDialog
          title="Return this book?"
          confirmLabel="Return book"
          onConfirm={() => processReturn(dialog.item)}
          onClose={() => setDialog(null)}
        >
          <ReturnSummary loan={dialog.item} />
        </ConfirmDialog>
      )}
      {dialog?.type === 'pay' && (
        <ConfirmDialog
          title="Record a payment"
          confirmLabel={`Record ${money(dialog.item.amount)} paid`}
          onConfirm={() => recordPayment(dialog.item)}
          onClose={() => setDialog(null)}
        >
          <p className="mb-0">
            {data.user.name} pays <strong>{money(dialog.item.amount)}</strong> at the desk for "{dialog.item.loan.book.title}". The loan
            will be closed.
          </p>
        </ConfirmDialog>
      )}
    </section>
  );
}
