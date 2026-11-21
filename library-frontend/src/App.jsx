import { useEffect } from 'react';
import { useAuth } from './context/AuthContext.jsx';
import Layout, { navFor } from './components/Layout.jsx';
import { Empty, Loading } from './components/ui.jsx';
import Catalog from './pages/Catalog.jsx';
import History from './pages/History.jsx';
import Loans from './pages/Loans.jsx';
import Login from './pages/Login.jsx';
import Logs from './pages/Logs.jsx';
import MyBooks from './pages/MyBooks.jsx';
import Notifications from './pages/Notifications.jsx';
import Penalties from './pages/Penalties.jsx';
import Settings from './pages/Settings.jsx';
import StudentDetail from './pages/StudentDetail.jsx';
import Students from './pages/Students.jsx';
import Users from './pages/Users.jsx';
import { Link, match, navigate, useRoute } from './utils/router.jsx';

function pageFor(path, auth) {
  const common = {
    '/catalog': Catalog,
    '/penalties': Penalties,
    '/notifications': Notifications,
  };
  const student = { '/my-books': MyBooks, '/history': History };
  const staff = { '/loans': Loans, '/students': Students };
  const admin = { '/users': Users, '/settings': Settings, '/logs': Logs };

  const routes = {
    ...common,
    ...(auth.isStudent ? student : {}),
    ...(auth.isStaff ? staff : {}),
    ...(auth.isAdmin ? admin : {}),
  };
  if (routes[path]) {
    const Page = routes[path];
    return <Page />;
  }
  const studentRoute = auth.isStaff && match('/students/:id', path);
  if (studentRoute) return <StudentDetail key={studentRoute.id} id={studentRoute.id} />;
  return null;
}

export default function App() {
  const auth = useAuth();
  const path = useRoute();

  useEffect(() => {
    if (auth.user && (path === '/' || path === '/login')) navigate(navFor(auth)[0][0]);
  }, [auth, path]);

  if (!auth.ready) return <Loading />;
  if (!auth.user) return <Login />;

  if (path === '/' || path === '/login') return <Loading />;

  const page = pageFor(path, auth);
  return (
    <Layout path={path}>
      {page ?? (
        <Empty title="This page doesn't exist or isn't available for your account.">
          <Link to={navFor(auth)[0][0]}>Go to the start page</Link>
        </Empty>
      )}
    </Layout>
  );
}
