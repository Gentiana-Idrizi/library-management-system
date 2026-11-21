import { useState } from 'react';
import { api } from '../api/client.js';
import { useAuth } from '../context/AuthContext.jsx';
import ConfirmDialog from '../components/ConfirmDialog.jsx';
import Icon from '../components/Icon.jsx';
import Modal from '../components/Modal.jsx';
import { useToast } from '../components/Toast.jsx';
import { Empty, ErrorNote, Field, Loading, PageHeader, Pager, Tabs } from '../components/ui.jsx';
import { formatDate, ROLE_LABELS } from '../utils/format.js';
import { useApi } from '../hooks/useApi.js';
import { useDebounced } from '../hooks/useDebounced.js';

const LIMIT = 20;

export default function Users() {
  const { user: me } = useAuth();
  const toast = useToast();
  const [role, setRole] = useState('');
  const [q, setQ] = useState('');
  const [page, setPage] = useState(1);
  const [dialog, setDialog] = useState(null);
  const search = useDebounced(q);
  const users = useApi('/users', { role, q: search, page, limit: LIMIT });

  async function remove(account) {
    await api(`/users/${account.id}`, { method: 'DELETE' });
    toast(`${account.name}'s account was deleted.`);
    users.reload();
  }

  return (
    <section>
      <PageHeader icon="user" title="Accounts">
        <button type="button" className="btn btn-primary d-flex align-items-center gap-2" onClick={() => setDialog({ type: 'form' })}>
          <Icon name="plus" size={18} />
          Create account
        </button>
      </PageHeader>

      <Tabs
        label="Role"
        value={role}
        options={[
          ['', 'Everyone'],
          ['student', 'Students'],
          ['operator', 'Operators'],
          ['super_admin', 'Super Admins'],
        ]}
        onChange={(r) => {
          setRole(r);
          setPage(1);
        }}
      />

      <div className="input-group mb-3 search-box">
        <span className="input-group-text bg-white">
          <Icon name="search" size={18} />
        </span>
        <input
          type="search"
          className="form-control border-start-0"
          placeholder="Name or email"
          aria-label="Search accounts"
          value={q}
          onChange={(e) => {
            setQ(e.target.value);
            setPage(1);
          }}
        />
      </div>

      <ErrorNote error={users.error} onRetry={users.reload} />
      {!users.data && users.loading && <Loading />}
      {users.data && users.data.items.length === 0 && <Empty title="No accounts found." />}
      {users.data && users.data.items.length > 0 && (
        <div className="card panel">
          <div className="table-responsive">
            <table className="table table-hover align-middle mb-0">
              <thead>
                <tr>
                  <th scope="col">Name</th>
                  <th scope="col">Email</th>
                  <th scope="col">Role</th>
                  <th scope="col">Created</th>
                  <th scope="col">
                    <span className="visually-hidden">Actions</span>
                  </th>
                </tr>
              </thead>
              <tbody>
                {users.data.items.map((u) => (
                  <tr key={u.id}>
                    <td className="fw-semibold">{u.name}</td>
                    <td>{u.email}</td>
                    <td>
                      <span className={`role role-${u.role}`}>{ROLE_LABELS[u.role]}</span>
                    </td>
                    <td className="text-nowrap">{formatDate(u.createdAt)}</td>
                    <td className="text-end">
                      {u.role !== 'super_admin' && u.id !== me.id && (
                        <div className="btn-group btn-group-sm">
                          <button type="button" className="btn btn-light border" onClick={() => setDialog({ type: 'form', account: u })}>
                            Edit
                          </button>
                          <button
                            type="button"
                            className="btn btn-light border text-danger"
                            onClick={() => setDialog({ type: 'delete', account: u })}
                          >
                            Delete
                          </button>
                        </div>
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}
      {users.data && <Pager page={page} limit={LIMIT} total={users.data.total} onPage={setPage} />}

      {dialog?.type === 'form' && (
        <AccountForm
          account={dialog.account}
          onClose={() => setDialog(null)}
          onSaved={(saved, created) => {
            toast(created ? `Account for ${saved.name} created.` : `${saved.name}'s account was saved.`);
            setDialog(null);
            users.reload();
          }}
        />
      )}
      {dialog?.type === 'delete' && (
        <ConfirmDialog
          title="Delete this account?"
          tone="danger"
          confirmLabel="Delete account"
          onConfirm={() => remove(dialog.account)}
          onClose={() => setDialog(null)}
        >
          <p className="mb-0">
            <strong>{dialog.account.name}</strong> will no longer be able to sign in. Their loan history is kept. Accounts with books still
            out or unpaid penalties can't be deleted.
          </p>
        </ConfirmDialog>
      )}
    </section>
  );
}

function AccountForm({ account, onClose, onSaved }) {
  const [values, setValues] = useState({
    name: account?.name ?? '',
    email: account?.email ?? '',
    role: account?.role ?? 'student',
    password: '',
  });
  const [errors, setErrors] = useState({});
  const [message, setMessage] = useState(null);
  const [busy, setBusy] = useState(false);

  const set = (name) => (e) => setValues((v) => ({ ...v, [name]: e.target.value }));
  const control = (name, base = 'form-control') => `${base}${errors[name] ? ' is-invalid' : ''}`;

  async function submit(e) {
    e.preventDefault();
    setBusy(true);
    setErrors({});
    setMessage(null);
    const body = { ...values };
    if (account && !body.password) delete body.password;
    try {
      const saved = account ? await api(`/users/${account.id}`, { method: 'PATCH', body }) : await api('/users', { method: 'POST', body });
      onSaved(saved, !account);
    } catch (err) {
      setErrors(err.fields);
      if (Object.keys(err.fields).length === 0) setMessage(err.message);
      setBusy(false);
    }
  }

  return (
    <Modal
      title={account ? `Edit ${account.name}` : 'Create an account'}
      onClose={onClose}
      footer={
        <>
          <button type="button" className="btn btn-light border" onClick={onClose}>
            Cancel
          </button>
          <button type="submit" form="account-form" className="btn btn-primary" disabled={busy}>
            {busy ? 'Saving…' : account ? 'Save account' : 'Create account'}
          </button>
        </>
      }
    >
      <form id="account-form" onSubmit={submit} noValidate>
        <Field label="Full name" id="u-name" error={errors.name}>
          <input id="u-name" className={control('name')} value={values.name} onChange={set('name')} />
        </Field>
        <Field label="Email" id="u-email" error={errors.email}>
          <input id="u-email" type="email" className={control('email')} value={values.email} onChange={set('email')} />
        </Field>
        <Field label="Role" id="u-role" error={errors.role}>
          <select id="u-role" className={control('role', 'form-select')} value={values.role} onChange={set('role')}>
            <option value="student">Student</option>
            <option value="operator">Operator</option>
          </select>
        </Field>
        <Field
          label={account ? 'New password' : 'Password'}
          id="u-password"
          error={errors.password}
          hint={account ? 'Leave empty to keep the current password' : 'At least 8 characters'}
        >
          <input
            id="u-password"
            type="password"
            className={control('password')}
            autoComplete="new-password"
            value={values.password}
            onChange={set('password')}
          />
        </Field>
        {message && (
          <div className="alert alert-danger mb-0" role="alert">
            {message}
          </div>
        )}
      </form>
    </Modal>
  );
}
