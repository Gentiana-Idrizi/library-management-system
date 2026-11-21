const dateFormat = new Intl.DateTimeFormat('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
const dateTimeFormat = new Intl.DateTimeFormat('en-GB', {
  day: 'numeric',
  month: 'short',
  year: 'numeric',
  hour: '2-digit',
  minute: '2-digit',
});

export function formatDate(value) {
  if (!value) return '';
  const date = value.length === 10 ? new Date(`${value}T00:00:00`) : new Date(value);
  return dateFormat.format(date);
}

export function formatDateTime(value) {
  return value ? dateTimeFormat.format(new Date(value)) : '';
}

export function money(amount) {
  return `€${Number(amount ?? 0).toFixed(2)}`;
}

export function plural(count, one, many = `${one}s`) {
  return `${count} ${count === 1 ? one : many}`;
}

export function addDays(days) {
  const d = new Date();
  d.setDate(d.getDate() + days);
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

export const ROLE_LABELS = { student: 'Student', operator: 'Operator', super_admin: 'Super Admin' };

export const LOAN_STATUS_LABELS = {
  active: 'On loan',
  overdue: 'Overdue',
  awaiting_payment: 'Awaiting payment',
  closed: 'Closed',
};
