import { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';
import { api, setUnauthorizedHandler, tokenStore } from '../api/client.js';

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  const [ready, setReady] = useState(false);

  const logout = useCallback(() => {
    tokenStore.set(null);
    setUser(null);
  }, []);

  useEffect(() => {
    setUnauthorizedHandler(logout);
    if (!tokenStore.get()) {
      setReady(true);
      return;
    }
    api('/auth/me')
      .then(setUser)
      .catch(() => tokenStore.set(null))
      .finally(() => setReady(true));
  }, [logout]);

  const login = useCallback(async (email, password) => {
    const result = await api('/auth/login', { method: 'POST', body: { email, password } });
    tokenStore.set(result.token);
    const me = await api('/auth/me');
    setUser(me);
    return me;
  }, []);

  const refresh = useCallback(() => api('/auth/me').then(setUser), []);

  const value = useMemo(() => {
    const role = user?.role;
    return {
      user,
      ready,
      login,
      logout,
      refresh,
      isStudent: role === 'student',
      isStaff: role === 'operator' || role === 'super_admin',
      isAdmin: role === 'super_admin',
    };
  }, [user, ready, login, logout, refresh]);

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth() {
  return useContext(AuthContext);
}
