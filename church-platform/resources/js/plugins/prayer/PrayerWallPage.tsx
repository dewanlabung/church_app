import { useState } from 'react';
import { useInfiniteQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { api } from '@/hooks/useApi';
import { useAuthStore } from '@/stores/authStore';

interface PrayerRequest {
  id: number;
  user_id: number;
  name: string;
  avatar: string | null;
  body: string;
  is_anonymous: boolean;
  support_count: number;
  user_supported: boolean;
  created_at: string;
}
interface Page { data: PrayerRequest[]; next_cursor: string | null; has_more: boolean; }

export default function PrayerWallPage() {
  const { isAuthenticated, user } = useAuthStore();
  const qc = useQueryClient();
  const [showForm, setShowForm] = useState(false);
  const [form, setForm] = useState({ body: '', is_anonymous: false });

  const { data, fetchNextPage, hasNextPage, isFetchingNextPage, isLoading } = useInfiniteQuery<Page>({
    queryKey: ['prayer-requests'],
    queryFn: ({ pageParam }) => api.get(`/prayer${pageParam ? `?cursor=${pageParam}` : ''}`),
    getNextPageParam: (last) => last.next_cursor ?? undefined,
    initialPageParam: undefined,
  });

  const createMutation = useMutation({
    mutationFn: () => api.post('/prayer', form),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['prayer-requests'] }); setForm({ body: '', is_anonymous: false }); setShowForm(false); },
  });

  const supportMutation = useMutation({
    mutationFn: (id: number) => api.post(`/prayer/${id}/support`),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['prayer-requests'] }),
  });

  const requests = data?.pages.flatMap((p) => p.data) ?? [];

  return (
    <div className="space-y-4 max-w-2xl">
      <div className="flex items-center justify-between">
        <h1 className="text-xl font-bold">🙏 Prayer Wall</h1>
        {isAuthenticated && (
          <button onClick={() => setShowForm(!showForm)}
            className="px-3 py-1.5 rounded-lg bg-[var(--color-primary)] text-white text-sm font-medium hover:opacity-90 transition-opacity">
            + Share Prayer
          </button>
        )}
      </div>

      {showForm && (
        <div className="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-2xl p-4 space-y-3">
          <textarea
            value={form.body}
            onChange={(e) => setForm((f) => ({ ...f, body: e.target.value }))}
            placeholder="Share your prayer request with the community…"
            rows={4}
            className="w-full px-3 py-2 rounded-lg border border-[var(--color-border)] bg-[var(--color-background)] text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] resize-none"
          />
          <label className="flex items-center gap-2 text-sm cursor-pointer">
            <input type="checkbox" checked={form.is_anonymous} onChange={(e) => setForm((f) => ({ ...f, is_anonymous: e.target.checked }))}
              className="rounded" />
            Post anonymously
          </label>
          <div className="flex gap-2 justify-end">
            <button onClick={() => setShowForm(false)} className="px-3 py-1.5 text-sm rounded-lg border border-[var(--color-border)] hover:bg-[var(--color-surface-alt)]">Cancel</button>
            <button onClick={() => createMutation.mutate()} disabled={!form.body.trim() || createMutation.isPending}
              className="px-4 py-1.5 text-sm rounded-lg bg-[var(--color-primary)] text-white disabled:opacity-50 font-medium">
              {createMutation.isPending ? 'Posting…' : 'Post Prayer'}
            </button>
          </div>
        </div>
      )}

      {isLoading ? (
        <PrayerSkeleton />
      ) : requests.length === 0 ? (
        <div className="text-center py-16 text-[var(--color-muted)]">
          <p className="text-4xl mb-3">🙏</p>
          <p className="font-medium">No prayer requests yet. Be the first to share.</p>
        </div>
      ) : (
        <>
          {requests.map((req) => (
            <PrayerCard key={req.id} request={req} onSupport={() => isAuthenticated && supportMutation.mutate(req.id)} />
          ))}
          {hasNextPage && (
            <button onClick={() => fetchNextPage()} disabled={isFetchingNextPage}
              className="w-full py-3 rounded-xl bg-[var(--color-surface)] hover:bg-[var(--color-surface-alt)] text-sm font-medium disabled:opacity-50">
              {isFetchingNextPage ? 'Loading…' : 'Load more'}
            </button>
          )}
        </>
      )}
    </div>
  );
}

function PrayerCard({ request: req, onSupport }: { request: PrayerRequest; onSupport: () => void }) {
  const displayName = req.is_anonymous ? 'Anonymous' : req.name;
  const displayAvatar = req.is_anonymous ? null : req.avatar;
  return (
    <div className="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-2xl p-4 space-y-3">
      <div className="flex items-center gap-3">
        {displayAvatar
          ? <img src={displayAvatar} className="w-9 h-9 rounded-full" alt={displayName} />
          : <div className="w-9 h-9 rounded-full bg-[var(--color-primary)]/20 flex items-center justify-center text-lg">🙏</div>
        }
        <div>
          <p className="font-semibold text-sm">{displayName}</p>
          <p className="text-xs text-[var(--color-muted)]">{new Date(req.created_at).toLocaleDateString()}</p>
        </div>
      </div>
      <p className="text-sm whitespace-pre-wrap leading-relaxed">{req.body}</p>
      <div className="pt-2 border-t border-[var(--color-border)]">
        <button onClick={onSupport}
          className={`flex items-center gap-2 px-3 py-1.5 rounded-full text-sm font-medium transition-colors ${req.user_supported ? 'bg-[var(--color-primary)] text-white' : 'bg-[var(--color-surface-alt)] hover:bg-[var(--color-primary)] hover:text-white'}`}>
          🙏 {req.user_supported ? 'Praying' : 'I\'m praying for you'} · {req.support_count}
        </button>
      </div>
    </div>
  );
}

function PrayerSkeleton() {
  return (
    <div className="space-y-4">
      {[1,2,3].map((i) => (
        <div key={i} className="bg-[var(--color-surface)] rounded-2xl p-4 space-y-3 animate-pulse">
          <div className="flex gap-3"><div className="w-9 h-9 rounded-full bg-[var(--color-surface-alt)]" /><div className="space-y-1"><div className="h-3 w-24 bg-[var(--color-surface-alt)] rounded" /><div className="h-2 w-16 bg-[var(--color-surface-alt)] rounded" /></div></div>
          <div className="space-y-2"><div className="h-3 w-full bg-[var(--color-surface-alt)] rounded" /><div className="h-3 w-4/5 bg-[var(--color-surface-alt)] rounded" /></div>
        </div>
      ))}
    </div>
  );
}
