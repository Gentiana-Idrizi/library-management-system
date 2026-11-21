import { useState } from 'react';
import { Empty, ErrorNote, Loading, PageHeader, Pager } from '../components/ui.jsx';
import { useApi } from '../hooks/useApi.js';
import LoanTable from '../components/LoanTable.jsx';

const LIMIT = 20;

export default function History() {
  const [page, setPage] = useState(1);
  const loans = useApi('/loans', { page, limit: LIMIT });

  return (
    <section>
      <PageHeader icon="history" title="Borrowing history" />
      <ErrorNote error={loans.error} onRetry={loans.reload} />
      {!loans.data && loans.loading && <Loading />}
      {loans.data && loans.data.items.length === 0 && <Empty title="You haven't borrowed any books yet." />}
      {loans.data && loans.data.items.length > 0 && <LoanTable loans={loans.data.items} />}
      {loans.data && <Pager page={page} limit={LIMIT} total={loans.data.total} onPage={setPage} />}
    </section>
  );
}
