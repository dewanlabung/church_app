import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { api } from '@/hooks/useApi';
import { useAuthStore } from '@/stores/authStore';

interface BibleStudy {
  id: number;
  title: string;
  description: string;
  scripture_reference: string;
  start_at: string;
  member_count: number;
  is_member: boolean;
  organizer_name: string;
}

export default function BibleStudyPage() {
  const { isAuthenticated, hasRole } = useAuthStore();
  const qc = useQueryClient();
  const [showCreate, setShowCreate] = useState(false);
  const [form, setForm] = useState({ title: '', description: '', scripture_reference: '', start_at: '' });

  const { data: studies = [], isLoading } = useQuery<BibleStudy[]>({
    queryKey: ['bible-studies'],
    queryFn: () => api.get('/bible-studies'),
  });

  const createMutation = useMutation({
    mutationFn: () => api.post('/bible-studies', form),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['bible-studies'] }); setForm({ title: '', description: '', scripture_reference: '', start_at: '' }); setShowCreate(false); },
  });

  const joinMutation = useMutation({
    mutationFn: (id: number) => api.post(`/bible-studies/${id}/join`),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['bible-studies'] }),
  });

  const canCreate = isAuthenticated && hasRole(['super_admin', 'church_admin']);

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h1 className="text-xl font-bold">📖 Bible Studies</h1>
        {canCreate && (
          <button onClick={() => setShowCreate(!showCreate)}
            className="px-3 py-1.5 rounded-lg bg-[var(--color-primary)] text-white text-sm font-medium hover:opacity-90">
            + Create Study
          </button>
        )}
      </div>

      {showCreate && (
        <div className="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-2xl p-4 space-y-3">
          <h2 className="font-semibold">New Bible Study</h2>
          <input value={form.title} onChange={(e) => setForm((f) => ({ ...f, title: e.target.value }))} placeholder="Study title"
            className="w-full px-3 py-2 rounded-lg border border-[var(--color-border)] bg-[var(--color-background)] text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]" />
          <input value={form.scripture_reference} onChange={(e) => setForm((f) => ({ ...f, scripture_reference: e.target.value }))} placeholder="Scripture reference (e.g. John 3:16)"
            className="w-full px-3 py-2 rounded-lg border border-[var(--color-border)] bg-[var(--color-background)] text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]" />
          <textarea value={form.description} onChange={(e) => setForm((f) => ({ ...f, description: e.target.value }))} placeholder="Study description…" rows={3}
            className="w-full px-3 py-2 rounded-lg border border-[var(--color-border)] bg-[var(--color-background)] text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] resize-none" />
          <div>
            <label className="block text-xs font-medium mb-1 text-[var(--color-muted)]">Start date & time</label>
            <input type="datetime-local" value={form.start_at} onChange={(e) => setForm((f) => ({ ...f, start_at: e.target.value }))}
              className="w-full px-3 py-2 rounded-lg border border-[var(--color-border)] bg-[var(--color-background)] text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]" />
          </div>
          <div className="flex gap-2 justify-end">
            <button onClick={() => setShowCreate(false)} className="px-3 py-1.5 text-sm rounded-lg border border-[var(--color-border)] hover:bg-[var(--color-surface-alt)]">Cancel</button>
            <button onClick={() => createMutation.mutate()} disabled={!form.title || createMutation.isPending}
              className="px-4 py-1.5 text-sm rounded-lg bg-[var(--color-primary)] text-white disabled:opacity-50 font-medium">
              {createMutation.isPending ? 'Creating…' : 'Create Study'}
            </button>
          </div>
        </div>
      )}

      {isLoading ? <StudySkeleton /> : (studies as BibleStudy[]).length === 0 ? (
        <div className="text-center py-16 text-[var(--color-muted)]">
          <p className="text-4xl mb-3">📖</p>
          <p className="font-medium">No Bible studies yet.</p>
        </div>
      ) : (
        <div className="space-y-3">
          {(studies as BibleStudy[]).map((s) => (
            <StudyCard key={s.id} study={s} onJoin={() => isAuthenticated && joinMutation.mutate(s.id)} />
          ))}
        </div>
      )}
    </div>
  );
}

function StudyCard({ study: s, onJoin }: { study: BibleStudy; onJoin: () => void }) {
  const start = s.start_at ? new Date(s.start_at) : null;
  return (
    <div className="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-2xl p-4 space-y-2">
      <div className="flex items-start justify-between gap-3">
        <div className="flex-1">
          <h3 className="font-semibold text-sm">{s.title}</h3>
          {s.scripture_reference && (
            <p className="text-xs font-medium text-[var(--color-primary)] mt-0.5">📖 {s.scripture_reference}</p>
          )}
          {start && <p className="text-xs text-[var(--color-muted)]">📅 {start.toLocaleDateString()} {start.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</p>}
          <p className="text-xs text-[var(--color-muted)]">{s.member_count} members · by {s.organizer_name}</p>
        </div>
        {s.is_member
          ? <span className="shrink-0 text-xs text-green-600 font-medium">✓ Joined</span>
          : <button onClick={onJoin} className="shrink-0 px-3 py-1.5 rounded-lg bg-[var(--color-primary)] text-white text-xs font-medium hover:opacity-90">Join</button>
        }
      </div>
      {s.description && <p className="text-sm text-[var(--color-muted)] line-clamp-2">{s.description}</p>}
    </div>
  );
}

function StudySkeleton() {
  return (
    <div className="space-y-3">
      {[1,2,3].map((i) => (
        <div key={i} className="bg-[var(--color-surface)] rounded-2xl p-4 animate-pulse space-y-2">
          <div className="h-3 w-3/4 bg-[var(--color-surface-alt)] rounded" />
          <div className="h-2 w-1/2 bg-[var(--color-surface-alt)] rounded" />
        </div>
      ))}
    </div>
  );
}
