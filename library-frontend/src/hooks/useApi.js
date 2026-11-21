import { useCallback, useEffect, useRef, useState } from 'react';
import { api } from '../api/client.js';

export function useApi(path, query, deps = []) {
  const [state, setState] = useState({ data: null, error: null, loading: true });
  const latest = useRef(0);
  const key = JSON.stringify([path, query]);

  const load = useCallback(() => {
    if (!path) return Promise.resolve();
    const id = ++latest.current;
    setState((s) => ({ ...s, loading: true, error: null }));
    return api(path, { query })
      .then((data) => id === latest.current && setState({ data, error: null, loading: false }))
      .catch((error) => id === latest.current && setState((s) => ({ ...s, error, loading: false })));
  }, [key]);

  useEffect(() => {
    load();
  }, [load, ...deps]);

  return { ...state, reload: load };
}
