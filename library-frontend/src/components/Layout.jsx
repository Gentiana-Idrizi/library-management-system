import { useEffect, useState } from 'react';
import { api } from '../api/client.js';
import { useAuth } from '../context/AuthContext.jsx';
import { ROLE_LABELS } from '../utils/format.js';
import { Link } from '../utils/router.jsx';
import Icon from './Icon.jsx';

export function navFor(auth) {
  if (auth.isStudent) {
    return [
      ['/catalog', 'Find a book', 'search'],
      ['/my-books', 'My books', 'bookmark'],
      ['/history', 'History', 'history'],
      ['/penalties', 'Penalties', 'wallet'],
    ];
  }
  const items = [
    ['/loans', 'Loans & returns', 'swap'],
    ['/catalog', 'Inventory', 'shelf'],
    ['/penalties', 'Penalties', 'wallet'],
    ['/students', 'Students', 'users'],
  ];
  if (auth.isAdmin) {
    items.push(['/users', 'Accounts', 'user'], ['/settings', 'Settings', 'settings'], ['/logs', 'System log', 'list']);
  }
  return items;
}

function initials(name) {
  return name
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0].toUpperCase())
    .join('');
}

function useUnreadCount(path) {
  const [count, setCount] = useState(0);

  useEffect(() => {
    let alive = true;
    const load = () =>
      api('/notifications', { query: { unread: 1 } })
        .then((r) => alive && setCount(r.unreadCount))
        .catch(() => {});

    load();
    const timer = setInterval(load, 60000);
    return () => {
      alive = false;
      clearInterval(timer);
    };
  }, [path]);

  return count;
}

export default function Layout({ path, children }) {
  const auth = useAuth();
  const unread = useUnreadCount(path);
  const [menuOpen, setMenuOpen] = useState(false);
  const items = navFor(auth);

  useEffect(() => setMenuOpen(false), [path]);

  return (
    <div className="app">
      <aside className={`sidebar${menuOpen ? ' is-open' : ''}`}>
        <div className="sidebar-brand">
          <span className="brand-logo" aria-hidden="true">
            <Icon name="book" size={22} />
          </span>
          <div>
            <p className="brand-name">Library</p>
            <p className="brand-sub">Management System</p>
          </div>
        </div>

        <div className="sidebar-user">
          <span className="avatar">{initials(auth.user.name)}</span>
          <div className="text-truncate">
            <p className="sidebar-user-name text-truncate">{auth.user.name}</p>
            <p className="sidebar-user-role">{ROLE_LABELS[auth.user.role]}</p>
          </div>
        </div>

        <nav aria-label="Main">
          <p className="sidebar-heading">Menu</p>
          <ul className="nav flex-column sidebar-nav">
            {items.map(([to, label, icon]) => {
              const active = path === to || path.startsWith(`${to}/`);
              return (
                <li className="nav-item" key={to}>
                  <Link to={to} className={`nav-link${active ? ' active' : ''}`} aria-current={active ? 'page' : undefined}>
                    <Icon name={icon} />
                    {label}
                  </Link>
                </li>
              );
            })}
          </ul>
        </nav>
      </aside>

      {menuOpen && <div className="sidebar-backdrop d-lg-none" onClick={() => setMenuOpen(false)} />}

      <div className="app-main">
        <header className="topbar">
          <button
            type="button"
            className="btn btn-link text-body p-1 d-lg-none"
            aria-label="Open menu"
            aria-expanded={menuOpen}
            onClick={() => setMenuOpen((open) => !open)}
          >
            <Icon name="menu" size={24} />
          </button>

          <div className="ms-auto d-flex align-items-center gap-2 gap-sm-3">
            <Link to="/notifications" className="topbar-link" aria-label={`Notifications, ${unread} unread`}>
              <Icon name="bell" />
              <span className="d-none d-sm-inline">Notifications</span>
              {unread > 0 && <span className="badge rounded-pill bg-primary">{unread}</span>}
            </Link>
            <button type="button" className="btn btn-outline-primary d-flex align-items-center gap-2" onClick={auth.logout}>
              <Icon name="logout" size={18} />
              Sign out
            </button>
          </div>
        </header>

        <main className="app-content">{children}</main>
      </div>
    </div>
  );
}
