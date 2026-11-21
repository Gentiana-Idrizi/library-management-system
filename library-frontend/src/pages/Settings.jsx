import { useEffect, useState } from 'react';
import { api } from '../api/client.js';
import { useToast } from '../components/Toast.jsx';
import { ErrorNote, Field, Loading, PageHeader } from '../components/ui.jsx';
import { useApi } from '../hooks/useApi.js';

const FIELDS = [
  ['borrowFee', 'Borrow fee (€)', 'Charged once when a book is borrowed', { inputMode: 'decimal' }],
  ['penaltyRate', 'Penalty per day (€)', 'Charged for every day after the return date', { inputMode: 'decimal' }],
  ['borrowPeriodDays', 'Borrowing period (days)', 'Return date = day of borrowing + this many days', { type: 'number', min: 1, max: 365 }],
  ['borrowLimit', 'Books per student', 'How many books a student may have at the same time', { type: 'number', min: 1, max: 50 }],
  [
    'lowStockThreshold',
    'Low-stock alert at',
    'Staff are notified when copies on the shelf drop to this number',
    { type: 'number', min: 0 },
  ],
];

export default function Settings() {
  const toast = useToast();
  const settings = useApi('/settings');
  const [values, setValues] = useState(null);
  const [errors, setErrors] = useState({});
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    if (settings.data) setValues(settings.data);
  }, [settings.data]);

  async function submit(e) {
    e.preventDefault();
    setBusy(true);
    setErrors({});
    try {
      const body = Object.fromEntries(FIELDS.map(([key]) => [key, values[key]]));
      const saved = await api('/settings', { method: 'PUT', body });
      setValues(saved);
      toast(saved.changed.length ? 'Settings saved. They apply to new loans and returns.' : 'Nothing changed.');
    } catch (err) {
      setErrors(err.fields);
      if (Object.keys(err.fields).length === 0) toast(err.message, 'bad');
    } finally {
      setBusy(false);
    }
  }

  return (
    <section>
      <PageHeader icon="settings" title="Settings" intro="Loans that already exist keep the fee and return date they were given." />
      <ErrorNote error={settings.error} onRetry={settings.reload} />
      {!values && settings.loading && <Loading />}
      {values && (
        <div className="card panel settings-card">
          <div className="card-body p-4">
            <form onSubmit={submit} noValidate>
              {FIELDS.map(([key, label, hint, attrs]) => (
                <Field key={key} label={label} id={`s-${key}`} error={errors[key]} hint={hint}>
                  <input
                    id={`s-${key}`}
                    className={`form-control${errors[key] ? ' is-invalid' : ''}`}
                    value={values[key]}
                    onChange={(e) => setValues((v) => ({ ...v, [key]: e.target.value }))}
                    {...attrs}
                  />
                </Field>
              ))}
              <button type="submit" className="btn btn-primary mt-2" disabled={busy}>
                {busy ? 'Saving…' : 'Save settings'}
              </button>
            </form>
          </div>
        </div>
      )}
    </section>
  );
}
