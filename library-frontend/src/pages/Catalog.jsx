import { useState } from 'react';
import { api } from '../api/client.js';
import { useAuth } from '../context/AuthContext.jsx';
import ConfirmDialog from '../components/ConfirmDialog.jsx';
import Icon from '../components/Icon.jsx';
import { useToast } from '../components/Toast.jsx';
import { Availability, Empty, ErrorNote, Loading, PageHeader, Pager } from '../components/ui.jsx';
import { addDays, formatDate, money, plural } from '../utils/format.js';
import { useApi } from '../hooks/useApi.js';
import { useDebounced } from '../hooks/useDebounced.js';
import BookForm from '../components/BookForm.jsx';

const LIMIT = 15;

export default function Catalog() {
  const { isStudent, isStaff, refresh } = useAuth();
  const toast = useToast();
  const [q, setQ] = useState('');
  const [category, setCategory] = useState('');
  const [availableOnly, setAvailableOnly] = useState(false);
  const [page, setPage] = useState(1);
  const [dialog, setDialog] = useState(null);
  const search = useDebounced(q);

  const books = useApi('/books', { q: search, category, available: availableOnly ? 1 : '', page, limit: LIMIT });
  const categories = useApi('/books/categories');
  const settings = useApi('/settings');

  const resetPage = (setter) => (value) => {
    setter(value);
    setPage(1);
  };

  async function borrow(book) {
    const result = await api('/loans', { method: 'POST', body: { bookId: book.id } });
    toast(result.message);
    books.reload();
    refresh();
  }

  async function remove(book) {
    await api(`/books/${book.id}`, { method: 'DELETE' });
    toast(`"${book.title}" was removed from the catalogue.`);
    books.reload();
  }

  const intro =
    isStudent && settings.data
      ? `Borrowing costs ${money(settings.data.borrowFee)} per book. You keep it for ${plural(settings.data.borrowPeriodDays, 'day')} and can have up to ${plural(settings.data.borrowLimit, 'book')} at a time.`
      : null;

  return (
    <section>
      <PageHeader icon={isStudent ? 'search' : 'shelf'} title={isStudent ? 'Find a book' : 'Inventory'} intro={intro}>
        {isStaff && (
          <button type="button" className="btn btn-primary d-flex align-items-center gap-2" onClick={() => setDialog({ type: 'add' })}>
            <Icon name="plus" size={18} />
            Add book
          </button>
        )}
      </PageHeader>

      <div className="card panel mb-3">
        <div className="card-body">
          <div className="row g-2 align-items-center">
            <div className="col-md-6">
              <div className="input-group">
                <span className="input-group-text bg-white">
                  <Icon name="search" size={18} />
                </span>
                <input
                  type="search"
                  className="form-control border-start-0"
                  placeholder="Title, author or ISBN"
                  aria-label="Search books"
                  value={q}
                  onChange={(e) => resetPage(setQ)(e.target.value)}
                />
              </div>
            </div>
            <div className="col-sm-6 col-md-3">
              <select
                className="form-select"
                aria-label="Category"
                value={category}
                onChange={(e) => resetPage(setCategory)(e.target.value)}
              >
                <option value="">All categories</option>
                {(categories.data?.items ?? []).map((c) => (
                  <option key={c}>{c}</option>
                ))}
              </select>
            </div>
            <div className="col-sm-6 col-md-3">
              <div className="form-check mb-0">
                <input
                  id="available-only"
                  type="checkbox"
                  className="form-check-input"
                  checked={availableOnly}
                  onChange={(e) => resetPage(setAvailableOnly)(e.target.checked)}
                />
                <label htmlFor="available-only" className="form-check-label">
                  Only available
                </label>
              </div>
            </div>
          </div>
        </div>
      </div>

      <ErrorNote error={books.error} onRetry={books.reload} />
      {!books.data && books.loading && <Loading />}

      {books.data && books.data.items.length === 0 && (
        <Empty title="No books match this search.">Try a shorter title, another category, or clear the filters.</Empty>
      )}

      {books.data && books.data.items.length > 0 && (
        <div className="card panel">
          <ul className="list-group list-group-flush" aria-busy={books.loading}>
            {books.data.items.map((book) => (
              <li key={book.id} className={`list-group-item book-row${book.available ? '' : ' is-out'}`}>
                <div className="min-w-0">
                  <h2 className="book-title">{book.title}</h2>
                  <p className="book-author">{book.author}</p>
                  <p className="book-meta">
                    {book.category}
                    <span className="book-meta-sep" aria-hidden="true" />
                    ISBN {book.isbn}
                  </p>
                </div>
                <div className="d-flex flex-wrap align-items-center gap-2 gap-md-3 flex-shrink-0">
                  <Availability book={book} />
                  {isStudent && (
                    <button
                      type="button"
                      className="btn btn-sm btn-primary px-3"
                      disabled={!book.available}
                      onClick={() => setDialog({ type: 'borrow', book })}
                    >
                      Borrow
                    </button>
                  )}
                  {isStaff && (
                    <div className="btn-group btn-group-sm">
                      <button type="button" className="btn btn-light border" onClick={() => setDialog({ type: 'edit', book })}>
                        Edit
                      </button>
                      <button
                        type="button"
                        className="btn btn-light border text-danger"
                        onClick={() => setDialog({ type: 'delete', book })}
                      >
                        Delete
                      </button>
                    </div>
                  )}
                </div>
              </li>
            ))}
          </ul>
        </div>
      )}

      {books.data && <Pager page={page} limit={LIMIT} total={books.data.total} onPage={setPage} />}

      {dialog?.type === 'borrow' && (
        <ConfirmDialog
          title="Borrow this book?"
          confirmLabel={`Borrow and pay ${money(settings.data?.borrowFee)}`}
          onConfirm={() => borrow(dialog.book)}
          onClose={() => setDialog(null)}
        >
          <p className="dialog-book">{dialog.book.title}</p>
          <dl className="facts mb-0">
            <dt>Borrow fee</dt>
            <dd>{money(settings.data?.borrowFee)}, charged now</dd>
            <dt>Return by</dt>
            <dd>{settings.data ? formatDate(addDays(settings.data.borrowPeriodDays)) : '…'}</dd>
            <dt>If late</dt>
            <dd>{money(settings.data?.penaltyRate)} for every day after the return date</dd>
          </dl>
        </ConfirmDialog>
      )}

      {dialog?.type === 'delete' && (
        <ConfirmDialog
          title="Delete this book?"
          tone="danger"
          confirmLabel="Delete book"
          onConfirm={() => remove(dialog.book)}
          onClose={() => setDialog(null)}
        >
          <p className="mb-0">
            <strong>{dialog.book.title}</strong> will no longer appear in the catalogue. Its past loans stay in the history. Books with
            copies still on loan can't be deleted.
          </p>
        </ConfirmDialog>
      )}

      {(dialog?.type === 'add' || dialog?.type === 'edit') && (
        <BookForm
          book={dialog.book}
          categories={categories.data?.items ?? []}
          onClose={() => setDialog(null)}
          onSaved={(book, created) => {
            toast(created ? `"${book.title}" was added.` : `"${book.title}" was saved.`);
            setDialog(null);
            books.reload();
            categories.reload();
          }}
        />
      )}
    </section>
  );
}
