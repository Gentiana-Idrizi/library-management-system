import { useState } from 'react';
import { useAuth } from '../context/AuthContext.jsx';
import Icon from '../components/Icon.jsx';

const DEMO = [
  ['Student', 'artabajrami@student.com', 'Student123!'],
  ['Operator', 'olta@operator.com', 'Operator123!'],
  ['Super Admin', 'ardian@admin.com', 'Admin123!'],
];

export default function Login() {
  const { login } = useAuth();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState(null);
  const [busy, setBusy] = useState(false);

  async function submit(e) {
    e.preventDefault();
    setBusy(true);
    setError(null);
    try {
      await login(email, password);
      window.location.hash = '/';
    } catch (err) {
      setError(err);
      setBusy(false);
    }
  }

  return (
    <div className="login-page">
      <div className="card login-card">
        <div className="card-body p-4 p-sm-5">
          <div className="d-flex align-items-center gap-3 mb-4">
            <span className="brand-logo brand-logo-lg" aria-hidden="true">
              <Icon name="book" size={26} />
            </span>
            <div>
              <h1 className="brand-name brand-name-lg mb-0">Library</h1>
              <p className="text-secondary mb-0">Sign in with your library account</p>
            </div>
          </div>

          <form onSubmit={submit} noValidate>
            <div className="mb-3">
              <label htmlFor="email" className="form-label">
                Email
              </label>
              <input
                id="email"
                type="email"
                className="form-control"
                autoComplete="username"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                required
              />
            </div>
            <div className="mb-3">
              <label htmlFor="password" className="form-label">
                Password
              </label>
              <input
                id="password"
                type="password"
                className="form-control"
                autoComplete="current-password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                required
              />
            </div>

            {error && (
              <div className="alert alert-danger py-2" role="alert">
                {error.message}
              </div>
            )}

            <button type="submit" className="btn btn-primary w-100 py-2" disabled={busy || !email || !password}>
              {busy && <span className="spinner-border spinner-border-sm me-2" aria-hidden="true" />}
              {busy ? 'Signing in…' : 'Sign in'}
            </button>
          </form>

          <div className="border-top mt-4 pt-3">
            <p className="small text-secondary mb-2">Demo accounts</p>
            <div className="d-flex flex-wrap gap-2">
              {DEMO.map(([label, demoEmail, demoPassword]) => (
                <button
                  key={demoEmail}
                  type="button"
                  className="btn btn-sm btn-light border"
                  onClick={() => {
                    setEmail(demoEmail);
                    setPassword(demoPassword);
                  }}
                >
                  {label}
                </button>
              ))}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
