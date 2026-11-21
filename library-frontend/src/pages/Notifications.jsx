import { api } from '../api/client.js';
import { Empty, ErrorNote, Loading, PageHeader } from '../components/ui.jsx';
import { formatDateTime } from '../utils/format.js';
import { useApi } from '../hooks/useApi.js';

const TYPE_LABELS = {
  overdue: 'Overdue book',
  pending_penalty: 'Penalty to pay',
  low_stock: 'Low stock',
  out_of_stock: 'Out of stock',
};

export default function Notifications() {
  const list = useApi('/notifications');
  const items = list.data?.items ?? [];

  async function markRead(id) {
    await api(`/notifications/${id}/read`, { method: 'POST' });
    list.reload();
  }

  async function markAll() {
    await api('/notifications/read-all', { method: 'POST' });
    list.reload();
  }

  return (
    <section>
      <PageHeader icon="bell" title="Notifications">
        {list.data?.unreadCount > 0 && (
          <button type="button" className="btn btn-light border" onClick={markAll}>
            Mark all as read
          </button>
        )}
      </PageHeader>

      <ErrorNote error={list.error} onRetry={list.reload} />
      {!list.data && list.loading && <Loading />}
      {list.data && items.length === 0 && <Empty title="No notifications." />}

      <div className="d-grid gap-2 notification-list">
        {items.map((n) => (
          <div key={n.id} className={`card panel notification notification-${n.type}${n.isRead ? ' is-read' : ''}`}>
            <div className="card-body d-flex justify-content-between align-items-start gap-3">
              <div>
                <p className="fw-semibold mb-1">{TYPE_LABELS[n.type] ?? n.type}</p>
                <p className="mb-1">{n.message}</p>
                <p className="small text-secondary mb-0">{formatDateTime(n.createdAt)}</p>
              </div>
              {!n.isRead && (
                <button type="button" className="btn btn-sm btn-light border text-nowrap" onClick={() => markRead(n.id)}>
                  Mark as read
                </button>
              )}
            </div>
          </div>
        ))}
      </div>
    </section>
  );
}
