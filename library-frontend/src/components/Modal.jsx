import { useEffect, useRef } from 'react';

export default function Modal({ title, onClose, children, footer, size }) {
  const dialog = useRef(null);

  useEffect(() => {
    const previous = document.activeElement;
    const first = dialog.current?.querySelector('input, select, textarea, .modal-footer button.btn-primary, .modal-footer button');
    (first ?? dialog.current)?.focus();
    document.body.classList.add('modal-open');

    const onKey = (e) => e.key === 'Escape' && onClose();
    document.addEventListener('keydown', onKey);

    return () => {
      document.removeEventListener('keydown', onKey);
      document.body.classList.remove('modal-open');
      previous?.focus?.();
    };
  }, [onClose]);

  return (
    <>
      <div className="modal-backdrop fade show" />
      <div
        className="modal fade show d-block"
        tabIndex={-1}
        role="dialog"
        aria-modal="true"
        aria-label={title}
        onMouseDown={(e) => e.target === e.currentTarget && onClose()}
      >
        <div className={`modal-dialog modal-dialog-centered${size ? ` modal-${size}` : ''}`} ref={dialog} tabIndex={-1}>
          <div className="modal-content">
            <div className="modal-header">
              <h2 className="modal-title fs-5">{title}</h2>
              <button type="button" className="btn-close" aria-label="Close" onClick={onClose} />
            </div>
            <div className="modal-body">{children}</div>
            {footer && <div className="modal-footer">{footer}</div>}
          </div>
        </div>
      </div>
    </>
  );
}
