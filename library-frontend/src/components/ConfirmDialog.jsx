import { useState } from 'react';
import Modal from './Modal.jsx';

export default function ConfirmDialog({ title, children, confirmLabel, tone = 'primary', onConfirm, onClose }) {
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState(null);

  async function run() {
    setBusy(true);
    setError(null);
    try {
      await onConfirm();
      onClose();
    } catch (e) {
      setError(e);
      setBusy(false);
    }
  }

  return (
    <Modal
      title={title}
      onClose={onClose}
      footer={
        <>
          <button type="button" className="btn btn-light border" onClick={onClose}>
            Cancel
          </button>
          <button type="button" className={`btn btn-${tone}`} onClick={run} disabled={busy}>
            {busy && <span className="spinner-border spinner-border-sm me-2" aria-hidden="true" />}
            {busy ? 'Working…' : confirmLabel}
          </button>
        </>
      }
    >
      {children}
      {error && (
        <div className="alert alert-danger mt-3 mb-0" role="alert">
          {error.message}
        </div>
      )}
    </Modal>
  );
}
