import { useState } from 'react';
import { api } from '../api/client.js';
import ConfirmDialog from '../components/ConfirmDialog.jsx';
import { useToast } from '../components/Toast.jsx';
import { Empty, ErrorNote, Loading, PageHeader, Pager, Tabs } from '../components/ui.jsx';
import { useApi } from '../hooks/useApi.js';
import LoanTable from '../components/LoanTable.jsx';
import ReturnSummary from '../components/ReturnSummary.jsx';

const LIMIT = 20;
const FILTERS = [
  ['open', 'Out now'],
  ['overdue', 'Overdue'],
  ['awaiting_payment', 'Awaiting payment'],
  ['closed', 'Closed'],
  ['', 'All'],
];

export default function Loans() {
  const toast = useToast();
  const [status, setStatus] = useState('open');
  const [page, setPage] = useState(1);
  const [returning, setReturning] = useState(null);
  const loans = useApi('/loans', { status, page, limit: LIMIT });

  async function processReturn(loan) {
    const result = await api(`/loans/${loan.id}/return`, { method: 'POST' });
    toast(result.message, result.loan.penalty ? 'warn' : 'ok');
    loans.reload();
  }

  return (
    <section>
      <PageHeader icon="swap" title="Loans and returns" />

      <Tabs
        label="Loan status"
        value={status}
        options={FILTERS}
        onChange={(s) => {
          setStatus(s);
          setPage(1);
        }}
      />

      <ErrorNote error={loans.error} onRetry={loans.reload} />
      {!loans.data && loans.loading && <Loading />}
      {loans.data && loans.data.items.length === 0 && <Empty title="No loans in this list." />}
      {loans.data && loans.data.items.length > 0 && <LoanTable loans={loans.data.items} showStudent onReturn={setReturning} />}
      {loans.data && <Pager page={page} limit={LIMIT} total={loans.data.total} onPage={setPage} />}

      {returning && (
        <ConfirmDialog
          title={`Return for ${returning.student.name}?`}
          confirmLabel="Return book"
          onConfirm={() => processReturn(returning)}
          onClose={() => setReturning(null)}
        >
          <ReturnSummary loan={returning} />
        </ConfirmDialog>
      )}
    </section>
  );
}
