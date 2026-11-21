import { createContext, useCallback, useContext, useState } from 'react';

const ToastContext = createContext(() => {});

const TONES = {
  ok: 'toast-ok',
  warn: 'toast-warn',
  bad: 'toast-bad',
};

export function ToastProvider({ children }) {
  const [toasts, setToasts] = useState([]);

  const show = useCallback((message, tone = 'ok') => {
    const id = Math.random().toString(36).slice(2);
    setToasts((list) => [...list, { id, message, tone }]);
    setTimeout(() => setToasts((list) => list.filter((t) => t.id !== id)), tone === 'bad' ? 7000 : 4500);
  }, []);

  const dismiss = (id) => setToasts((list) => list.filter((t) => t.id !== id));

  return (
    <ToastContext.Provider value={show}>
      {children}
      <div className="toast-container position-fixed bottom-0 end-0 p-3" role="status" aria-live="polite">
        {toasts.map((t) => (
          <div key={t.id} className={`toast show align-items-center ${TONES[t.tone]}`}>
            <div className="d-flex">
              <div className="toast-body">{t.message}</div>
              <button type="button" className="btn-close btn-close-white me-2 m-auto" aria-label="Close" onClick={() => dismiss(t.id)} />
            </div>
          </div>
        ))}
      </div>
    </ToastContext.Provider>
  );
}

export function useToast() {
  return useContext(ToastContext);
}
