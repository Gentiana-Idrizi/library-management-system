import { formatDate, money, plural } from '../utils/format.js';

export default function ReturnSummary({ loan }) {
  return (
    <>
      <p className="dialog-book">{loan.book.title}</p>
      {loan.status === 'overdue' ? (
        <p className="mb-0">
          This book was due on {formatDate(loan.dueAt)} and is {plural(loan.daysOverdue, 'day')} late. A penalty of{' '}
          <strong>{money(loan.accruingPenalty)}</strong> will be added. The book goes back on the shelf now; the loan closes once the
          penalty is paid.
        </p>
      ) : (
        <p className="mb-0">The book is on time, so there is nothing to pay. The loan will be closed.</p>
      )}
    </>
  );
}
