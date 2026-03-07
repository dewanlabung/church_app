import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { api } from '@/hooks/useApi';
import { useAuthStore } from '@/stores/authStore';

interface Event {
  id: number;
  title: string;
  description: string;
  location: string;
  online_url: string | null;
  start_at: string;
  end_at: string;
  rsvp_status: 'going' | 'interested' | 'not_going' | null;
  going_count: number;
  interested_count: number;
  organizer_name: string;
  organizer_avatar: string | null;
}

const RSVP_OPTIONS = [
  { key: 'going',     emoji: '✅', label: 'Going' },
  { key: 'interested',emoji: '⭐', label: 'Interested' },
  { key: 'not_going', emoji: '❌', label: "Can't go" },
];

export default function EventsPage() {
  const { isAuthenticated, hasRole } = useAuthStore();
  const qc = useQueryClient();
  const [showCreate, setShowCreate] = useState(false);
  const [selected, setSelected] = useState<Event | null>(null);
  const [form, setForm] = useState({ title: '', description: '', location: '', online_url: '', start_at: '', end_at: '' });

  const { data: events = [], isLoading } = useQuery<Event[]>({
    queryKey: ['events'],
    queryFn: () => api.get('/events'),
  });

  const createMutation = useMutation({
    mutationFn: () => api.post('/events', form),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['events'] }); setForm({ title: '', description: '', location: '', online_url: '', start_at: '', end_at: '' }); setShowCreate(false); },
  });

  const rsvpMutation = useMutation({
    mutationFn: ({ id, status }: { id: number; status: string }) => api.post(`/events/${id}/rsvp`, { status }),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['events'] }),
  });

  const canCreate = isAuthenticated && hasRole(['super_admin', 'church_admin', 'musician']);

  if (selected) {
    return <EventDetail event={selected} onBack={() => setSelected(null)}
      onRsvp={(status) => rsvpMutation.mutate({ id: selected.id, status })} />;
  }

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h1 className="text-xl font-bold">📅 Events</h1>
        {canCreate && (
          <button onClick={() => setShowCreate(!showCreate)}
            className="px-3 py-1.5 rounded-lg bg-[var(--color-primary)] text-white text-sm font-medium hover:opacity-90">
            + Create Event
          </button>
        )}
      </div>

      {showCreate && (
        <div className="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-2xl p-4 space-y-3">
          <h2 className="font-semibold">New Event</h2>
          {(['title', 'description', 'location', 'online_url'] as const).map((field) => (
            <div key={field}>
              <label className="block text-xs font-medium mb-1 capitalize text-[var(--color-muted)]">{field.replace('_', ' ')}</label>
              {field === 'description'
                ? <textarea value={form[field]} onChange={(e) => setForm((f) => ({ ...f, [field]: e.target.value }))} rows={3}
                    className="w-full px-3 py-2 rounded-lg border border-[var(--color-border)] bg-[var(--color-background)] text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] resize-none" />
                : <input value={form[field]} onChange={(e) => setForm((f) => ({ ...f, [field]: e.target.value }))}
                    className="w-full px-3 py-2 rounded-lg border border-[var(--color-border)] bg-[var(--color-background)] text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]" />
              }
            </div>
          ))}
          <div className="grid grid-cols-2 gap-3">
            {(['start_at', 'end_at'] as const).map((field) => (
              <div key={field}>
                <label className="block text-xs font-medium mb-1 text-[var(--color-muted)] capitalize">{field.replace('_', ' ')}</label>
                <input type="datetime-local" value={form[field]} onChange={(e) => setForm((f) => ({ ...f, [field]: e.target.value }))}
                  className="w-full px-3 py-2 rounded-lg border border-[var(--color-border)] bg-[var(--color-background)] text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]" />
              </div>
            ))}
          </div>
          <div className="flex gap-2 justify-end">
            <button onClick={() => setShowCreate(false)} className="px-3 py-1.5 text-sm rounded-lg border border-[var(--color-border)] hover:bg-[var(--color-surface-alt)]">Cancel</button>
            <button onClick={() => createMutation.mutate()} disabled={!form.title || !form.start_at || createMutation.isPending}
              className="px-4 py-1.5 text-sm rounded-lg bg-[var(--color-primary)] text-white disabled:opacity-50 font-medium">
              {createMutation.isPending ? 'Creating…' : 'Create Event'}
            </button>
          </div>
        </div>
      )}

      {isLoading ? <EventsSkeleton /> : (events as Event[]).length === 0 ? (
        <div className="text-center py-16 text-[var(--color-muted)]">
          <p className="text-4xl mb-3">📅</p>
          <p className="font-medium">No upcoming events.</p>
        </div>
      ) : (
        <div className="space-y-3">
          {(events as Event[]).map((ev) => (
            <EventCard key={ev.id} event={ev} onOpen={() => setSelected(ev)}
              onRsvp={(status) => isAuthenticated && rsvpMutation.mutate({ id: ev.id, status })} />
          ))}
        </div>
      )}
    </div>
  );
}

function EventCard({ event: ev, onOpen, onRsvp }: { event: Event; onOpen: () => void; onRsvp: (s: string) => void }) {
  const start = new Date(ev.start_at);
  return (
    <div className="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-2xl p-4 space-y-3">
      <div className="flex gap-4">
        <div className="shrink-0 w-12 text-center bg-[var(--color-primary)]/10 rounded-xl py-2 px-1">
          <p className="text-xs font-semibold text-[var(--color-primary)] uppercase">{start.toLocaleString('default', { month: 'short' })}</p>
          <p className="text-xl font-bold text-[var(--color-primary)] leading-none">{start.getDate()}</p>
        </div>
        <div className="flex-1 min-w-0">
          <button onClick={onOpen} className="font-semibold text-sm hover:text-[var(--color-primary)] text-left">{ev.title}</button>
          <p className="text-xs text-[var(--color-muted)] mt-0.5">{start.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })} · {ev.location || (ev.online_url ? 'Online' : 'TBD')}</p>
          <p className="text-xs text-[var(--color-muted)]">✅ {ev.going_count} going · ⭐ {ev.interested_count} interested</p>
        </div>
      </div>
      <div className="flex gap-2 flex-wrap">
        {RSVP_OPTIONS.map((r) => (
          <button key={r.key} onClick={() => onRsvp(r.key)}
            className={`flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium transition-colors ${ev.rsvp_status === r.key ? 'bg-[var(--color-primary)] text-white' : 'bg-[var(--color-surface-alt)] hover:bg-[var(--color-surface-alt)]'}`}>
            {r.emoji} {r.label}
          </button>
        ))}
      </div>
    </div>
  );
}

function EventDetail({ event: ev, onBack, onRsvp }: { event: Event; onBack: () => void; onRsvp: (s: string) => void }) {
  const start = new Date(ev.start_at);
  const end = ev.end_at ? new Date(ev.end_at) : null;
  return (
    <div className="space-y-4 max-w-2xl">
      <button onClick={onBack} className="text-sm text-[var(--color-muted)] hover:text-[var(--color-primary)]">← Back to Events</button>
      <div className="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-2xl p-5 space-y-4">
        <h1 className="text-xl font-bold">{ev.title}</h1>
        <div className="space-y-2 text-sm text-[var(--color-muted)]">
          <p>📅 {start.toLocaleDateString()} {start.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}{end ? ` – ${end.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}` : ''}</p>
          {ev.location && <p>📍 {ev.location}</p>}
          {ev.online_url && <p>🔗 <a href={ev.online_url} target="_blank" rel="noreferrer" className="text-[var(--color-primary)] hover:underline">{ev.online_url}</a></p>}
          <p>✅ {ev.going_count} going · ⭐ {ev.interested_count} interested</p>
        </div>
        {ev.description && <p className="text-sm whitespace-pre-wrap leading-relaxed">{ev.description}</p>}
        <div className="flex gap-2 flex-wrap pt-2 border-t border-[var(--color-border)]">
          {RSVP_OPTIONS.map((r) => (
            <button key={r.key} onClick={() => onRsvp(r.key)}
              className={`flex items-center gap-2 px-3 py-1.5 rounded-full text-sm font-medium transition-colors ${ev.rsvp_status === r.key ? 'bg-[var(--color-primary)] text-white' : 'bg-[var(--color-surface-alt)] hover:bg-[var(--color-primary)] hover:text-white'}`}>
              {r.emoji} {r.label}
            </button>
          ))}
        </div>
      </div>
    </div>
  );
}

function EventsSkeleton() {
  return (
    <div className="space-y-3">
      {[1,2,3].map((i) => (
        <div key={i} className="bg-[var(--color-surface)] rounded-2xl p-4 animate-pulse flex gap-4">
          <div className="w-12 h-14 rounded-xl bg-[var(--color-surface-alt)]" />
          <div className="flex-1 space-y-2"><div className="h-3 w-3/4 bg-[var(--color-surface-alt)] rounded" /><div className="h-2 w-1/2 bg-[var(--color-surface-alt)] rounded" /></div>
        </div>
      ))}
    </div>
  );
}
