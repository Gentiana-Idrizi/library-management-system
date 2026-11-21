import { useState } from 'react';
import { api } from '../api/client.js';
import Modal from './Modal.jsx';
import { Field } from './ui.jsx';

export default function BookForm({ book, categories, onClose, onSaved }) {
  const [values, setValues] = useState({
    title: book?.title ?? '',
    author: book?.author ?? '',
    isbn: book?.isbn ?? '',
    category: book?.category ?? '',
    stock: book?.stock ?? 1,
  });
  const [errors, setErrors] = useState({});
  const [message, setMessage] = useState(null);
  const [busy, setBusy] = useState(false);

  const set = (name) => (e) => setValues((v) => ({ ...v, [name]: e.target.value }));
  const control = (name) => `form-control${errors[name] ? ' is-invalid' : ''}`;

  async function submit(e) {
    e.preventDefault();
    setBusy(true);
    setErrors({});
    setMessage(null);
    const body = { ...values, stock: values.stock === '' ? null : Number(values.stock) };
    try {
      const saved = book ? await api(`/books/${book.id}`, { method: 'PUT', body }) : await api('/books', { method: 'POST', body });
      onSaved(saved, !book);
    } catch (err) {
      setErrors(err.fields);
      if (Object.keys(err.fields).length === 0) setMessage(err.message);
      setBusy(false);
    }
  }

  return (
    <Modal
      title={book ? 'Edit book' : 'Add a book'}
      onClose={onClose}
      footer={
        <>
          <button type="button" className="btn btn-light border" onClick={onClose}>
            Cancel
          </button>
          <button type="submit" form="book-form" className="btn btn-primary" disabled={busy}>
            {busy ? 'Saving…' : book ? 'Save book' : 'Add book'}
          </button>
        </>
      }
    >
      <form id="book-form" onSubmit={submit} noValidate>
        <Field label="Title" id="b-title" error={errors.title}>
          <input id="b-title" className={control('title')} value={values.title} onChange={set('title')} />
        </Field>
        <Field label="Author" id="b-author" error={errors.author}>
          <input id="b-author" className={control('author')} value={values.author} onChange={set('author')} />
        </Field>
        <div className="row">
          <div className="col-sm-7">
            <Field label="ISBN" id="b-isbn" error={errors.isbn} hint="10 or 13 digits, hyphens are fine">
              <input id="b-isbn" className={control('isbn')} value={values.isbn} onChange={set('isbn')} inputMode="numeric" />
            </Field>
          </div>
          <div className="col-sm-5">
            <Field label="Copies on the shelf" id="b-stock" error={errors.stock} hint="Not counting copies on loan">
              <input id="b-stock" type="number" min="0" className={control('stock')} value={values.stock} onChange={set('stock')} />
            </Field>
          </div>
        </div>
        <Field label="Category" id="b-category" error={errors.category}>
          <input id="b-category" list="category-list" className={control('category')} value={values.category} onChange={set('category')} />
          <datalist id="category-list">
            {categories.map((c) => (
              <option key={c} value={c} />
            ))}
          </datalist>
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
