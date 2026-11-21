import { LoanStatus } from './ui.jsx';
import { formatDate, money, plural } from '../utils/format.js';
import { Link } from '../utils/router.jsx';

function PenaltyCell({ loan }) {
  if (loan.penalty) {
    return (
      <span className={loan.penalty.paid ? 'text-success' : 'text-danger fw-medium'}>
        {money(loan.penalty.amount)} {loan.penalty.paid ? 'paid' : 'unpaid'}
      </span>
    );
  }
  if (loan.accruingPenalty) {
    return <span className="small text-secondary">{money(loan.accruingPenalty)} so far</span>;
  }
  return null;
}

export default function LoanTable({ loans, showStudent = false, onReturn }) {
  return (
    <div className="card panel">
      <div className="table-responsive">
        <table className="table table-hover align-middle mb-0">
          <thead>
            <tr>
              <th scope="col">Book</th>
              {showStudent && <th scope="col">Student</th>}
              <th scope="col">Borrowed</th>
              <th scope="col">Return by</th>
              <th scope="col">Returned</th>
              <th scope="col">Status</th>
              <th scope="col">Penalty</th>
              {onReturn && (
                <th scope="col">
                  <span className="visually-hidden">Actions</span>
                </th>
              )}
            </tr>
          </thead>
          <tbody>
            {loans.map((loan) => {
              const open = loan.status === 'active' || loan.status === 'overdue';
              const overdue = loan.status === 'overdue';
              return (
                <tr key={loan.id}>
                  <td className="fw-semibold">{loan.book.title}</td>
                  {showStudent && (
                    <td>
                      <Link to={`/students/${loan.student.id}`}>{loan.student.name}</Link>
                    </td>
                  )}
                  <td className="text-nowrap">{formatDate(loan.borrowedAt)}</td>
                  <td className={`text-nowrap${overdue ? ' text-danger' : ''}`}>
                    {formatDate(loan.dueAt)}
                    {overdue && <div className="small">{plural(loan.daysOverdue, 'day')} late</div>}
                  </td>
                  <td className="text-nowrap">{loan.returnedAt ? formatDate(loan.returnedAt) : ''}</td>
                  <td>
                    <LoanStatus status={loan.status} />
                  </td>
                  <td className="text-nowrap">
                    <PenaltyCell loan={loan} />
                  </td>
                  {onReturn && (
                    <td className="text-end">
                      {open && (
                        <button type="button" className="btn btn-sm btn-outline-primary" onClick={() => onReturn(loan)}>
                          Return
                        </button>
                      )}
                    </td>
                  )}
                </tr>
              );
            })}
          </tbody>
        </table>
      </div>
    </div>
  );
}
