import { useState } from 'react';
import { api } from '../api/client.js';
import { useAuth } from '../context/AuthContext.jsx';
import ConfirmDialog from '../components/ConfirmDialog.jsx';
import { useToast } from '../components/Toast.jsx';
import { Empty, ErrorNote, Loading, PageHeader, Pager, Tabs } from '../components/ui.jsx';
import { formatDate, money, plural } from '../utils/format.js';
import { useApi } from '../hooks/useApi.js';
import { Link } from '../utils/router.jsx';

const LIMIT = 20;

export default function Penalties() {
  const { isStaff, refresh } = useAuth();
  const toast = useToast();
  const [paid, setPaid] = useState('0');
  const [page, setPage] = useState(1);
  const [paying, setPaying] = useState(null);
  const penalties = useApi('/penalties', { paid, page, limit: LIMIT });
  const items = penalties.data?.items ?? [];

  async function pay(penalty) {
    const result = await api(`/penalties/${penalty.id}/pay`, { method: 'POST' });
    toast(result.message);
    penalties.reload();
    if (!isStaff) refresh();
  }

  let intro = null;
  if (!isStaff && penalties.data) {
    intro =
      Number(penalties.data.unpaidTotal) > 0
        ? `You owe ${money(penalties.data.unpaidTotal)}. Borrowing is blocked until every penalty is paid.`
        : 'You owe nothing.';
  }

  return (
    <section>
      <PageHeader icon="wallet" title="Penalties" intro={intro} />

      <Tabs
        label="Penalty status"
        value={paid}
        options={[
          ['0', 'Unpaid'],
          ['1', 'Paid'],
          ['', 'All'],
        ]}
        onChange={(v) => {
          setPaid(v);
          setPage(1);
        }}
      />

      <ErrorNote error={penalties.error} onRetry={penalties.reload} />
      {!penalties.data && penalties.loading && <Loading />}
      {penalties.data && items.length === 0 && (
        <Empty title={paid === '0' ? 'No unpaid penalties.' : 'Nothing to show.'}>
          {paid === '0' && !isStaff && 'Return books by their date and it stays that way.'}
        </Empty>
      )}

      {items.length > 0 && (
        <div className="card panel">
          <div className="table-responsive">
            <table className="table table-hover align-middle mb-0">
              <thead>
                <tr>
                  <th scope="col">Book</th>
                  {isStaff && <th scope="col">Student</th>}
                  <th scope="col">Was due</th>
                  <th scope="col">Returned</th>
                  <th scope="col">Late</th>
                  <th scope="col" className="text-end">
                    Amount
                  </th>
                  <th scope="col">Status</th>
                  <th scope="col">
                    <span className="visually-hidden">Actions</span>
                  </th>
                </tr>
              </thead>
              <tbody>
                {items.map((p) => (
                  <tr key={p.id}>
                    <td className="fw-semibold">{p.loan.book.title}</td>
                    {isStaff && (
                      <td>
                        <Link to={`/students/${p.student.id}`}>{p.student.name}</Link>
                      </td>
                    )}
                    <td className="text-nowrap">{formatDate(p.loan.dueAt)}</td>
                    <td className="text-nowrap">{formatDate(p.loan.returnedAt)}</td>
                    <td className="text-nowrap">{plural(p.daysLate, 'day')}</td>
                    <td className="text-end fw-semibold">{money(p.amount)}</td>
                    <td>
                      {p.paid ? (
                        <span className="status status-closed">
                          Paid {formatDate(p.paidAt)}
                          {p.paidBy && p.paidBy.id !== p.student.id ? ' at the desk' : ''}
                        </span>
                      ) : (
                        <span className="status status-overdue">Unpaid</span>
                      )}
                    </td>
                    <td className="text-end">
                      {!p.paid && (
                        <button type="button" className="btn btn-sm btn-primary text-nowrap" onClick={() => setPaying(p)}>
                          {isStaff ? 'Record payment' : `Pay ${money(p.amount)}`}
                        </button>
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}
      {penalties.data && <Pager page={page} limit={LIMIT} total={penalties.data.total} onPage={setPage} />}

      {paying && (
        <ConfirmDialog
          title={isStaff ? 'Record a payment' : 'Pay this penalty?'}
          confirmLabel={isStaff ? `Record ${money(paying.amount)} paid` : `Pay ${money(paying.amount)}`}
          onConfirm={() => pay(paying)}
          onClose={() => setPaying(null)}
        >
          <p className="dialog-book">{paying.loan.book.title}</p>
          <p className="mb-0">
            {isStaff ? `${paying.student.name} pays ` : 'You pay '}
            <strong>{money(paying.amount)}</strong> for returning it {plural(paying.daysLate, 'day')} late. The loan will be closed.
          </p>
        </ConfirmDialog>
      )}
    </section>
  );
}
