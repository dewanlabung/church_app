import { useState } from 'react';
import { useParams } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { api } from '@/hooks/useApi';
import { useAuthStore } from '@/stores/authStore';

interface Community {
  id: number;
  name: string;
  about: string;
  slug: string;
  type: 'public' | 'private' | 'hidden';
  member_count: number;
  is_member: boolean;
  is_owner: boolean;
  created_at: string;
}

export default function CommunityPage() {
  const { slug } = useParams<{ slug?: string }>();
  return slug ? <CommunityDetail slug={slug} /> : <CommunityList />;
}

function CommunityList() {
  const { isAuthenticated } = useAuthStore();
  const qc = useQueryClient();
  const [showCreate, setShowCreate] = useState(false);
  const [form, setForm] = useState({ name: '', about: '', type: 'public' });

  const { data: communities = [], isLoading } = useQuery<Community[]>({
    queryKey: ['communities'],
    queryFn: () => api.get('/communities'),
  });

  const createMutation = useMutation({
    mutationFn: () => api.post('/communities', form),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['communities'] }); setForm({ name: '', about: '', type: 'public' }); setShowCreate(false); },
  });

  const joinMutation = useMutation({
    mutationFn: (id: number) => api.post(`/communities/${id}/join`),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['communities'] }),
  });

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h1 className="text-xl font-bold">👥 Communities</h1>
        {isAuthenticated && (
          <button onClick={() => setShowCreate(!showCreate)}
            className="px-3 py-1.5 rounded-lg bg-[var(--color-primary)] text-white text-sm font-medium hover:opacity-90">
            + Create
          </button>
        )}
      </div>

      {showCreate && (
        <div className="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-2xl p-4 space-y-3">
          <h2 className="font-semibold">New Community</h2>
          <input value={form.name} onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))} placeholder="Community name"
            className="w-full px-3 py-2 rounded-lg border border-[var(--color-border)] bg-[var(--color-background)] text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]" />
          <textarea value={form.about} onChange={(e) => setForm((f) => ({ ...f, about: e.target.value }))} placeholder="What's this community about?" rows={3}
            className="w-full px-3 py-2 rounded-lg border border-[var(--color-border)] bg-[var(--color-background)] text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] resize-none" />
          <select value={form.type} onChange={(e) => setForm((f) => ({ ...f, type: e.target.value }))}
            className="w-full px-3 py-2 rounded-lg border border-[var(--color-border)] bg-[var(--color-background)] text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
            <option value="public">Public — anyone can join</option>
            <option value="private">Private — requires approval</option>
            <option value="hidden">Hidden — invite only</option>
          </select>
          <div className="flex gap-2 justify-end">
            <button onClick={() => setShowCreate(false)} className="px-3 py-1.5 text-sm rounded-lg border border-[var(--color-border)] hover:bg-[var(--color-surface-alt)]">Cancel</button>
            <button onClick={() => createMutation.mutate()} disabled={!form.name.trim() || createMutation.isPending}
              className="px-4 py-1.5 text-sm rounded-lg bg-[var(--color-primary)] text-white disabled:opacity-50 font-medium">
              {createMutation.isPending ? 'Creating…' : 'Create'}
            </button>
          </div>
        </div>
      )}

      {isLoading ? <CommunitySkeleton /> : (communities as Community[]).length === 0 ? (
        <div className="text-center py-16 text-[var(--color-muted)]">
          <p className="text-4xl mb-3">👥</p>
          <p className="font-medium">No communities yet. Create the first one!</p>
        </div>
      ) : (
        <div className="space-y-3">
          {(communities as Community[]).map((c) => (
            <CommunityCard key={c.id} community={c} onJoin={() => isAuthenticated && joinMutation.mutate(c.id)} />
          ))}
        </div>
      )}
    </div>
  );
}

function CommunityDetail({ slug }: { slug: string }) {
  const { data: community, isLoading } = useQuery<Community>({
    queryKey: ['community', slug],
    queryFn: () => api.get(`/communities/${slug}`),
  });
  const qc = useQueryClient();
  const joinMutation = useMutation({
    mutationFn: () => api.post(`/communities/${community?.id}/join`),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['community', slug] }),
  });

  if (isLoading) return <CommunitySkeleton />;
  if (!community) return <div className="p-8 text-center text-[var(--color-muted)]">Community not found.</div>;

  return (
    <div className="space-y-4 max-w-2xl">
      <div className="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-2xl p-5 space-y-3">
        <div className="flex items-start justify-between gap-4">
          <div className="w-14 h-14 rounded-2xl bg-[var(--color-primary)]/20 flex items-center justify-center text-2xl shrink-0">👥</div>
          <div className="flex-1">
            <h1 className="text-xl font-bold">{community.name}</h1>
            <p className="text-xs text-[var(--color-muted)] mt-0.5 capitalize">{community.type} community · {community.member_count} members</p>
          </div>
          {!community.is_member && (
            <button onClick={() => joinMutation.mutate()} disabled={joinMutation.isPending}
              className="shrink-0 px-4 py-1.5 rounded-lg bg-[var(--color-primary)] text-white text-sm font-medium disabled:opacity-50">
              {community.type === 'private' ? 'Request to Join' : 'Join'}
            </button>
          )}
          {community.is_member && <span className="shrink-0 px-3 py-1.5 rounded-lg bg-green-100 text-green-700 text-xs font-medium">✓ Member</span>}
        </div>
        {community.about && <p className="text-sm text-[var(--color-muted)] leading-relaxed">{community.about}</p>}
      </div>

      {community.is_member ? (
        <div className="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-2xl p-8 text-center text-[var(--color-muted)]">
          <p className="text-2xl mb-2">💬</p>
          <p className="text-sm">Community feed coming soon.</p>
        </div>
      ) : (
        <div className="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-2xl p-8 text-center text-[var(--color-muted)]">
          <p className="text-sm">Join this community to see posts and participate.</p>
        </div>
      )}
    </div>
  );
}

function CommunityCard({ community: c, onJoin }: { community: Community; onJoin: () => void }) {
  return (
    <div className="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-2xl p-4 flex items-center gap-4">
      <div className="w-12 h-12 rounded-xl bg-[var(--color-primary)]/20 flex items-center justify-center text-xl shrink-0">👥</div>
      <div className="flex-1 min-w-0">
        <a href={`/c/${c.slug}`} className="font-semibold text-sm hover:text-[var(--color-primary)]">{c.name}</a>
        <p className="text-xs text-[var(--color-muted)] truncate">{c.about || 'No description'}</p>
        <p className="text-xs text-[var(--color-muted)]">{c.member_count} members · <span className="capitalize">{c.type}</span></p>
      </div>
      {c.is_member
        ? <span className="text-xs text-green-600 font-medium shrink-0">✓ Joined</span>
        : <button onClick={onJoin} className="shrink-0 px-3 py-1.5 rounded-lg bg-[var(--color-primary)] text-white text-xs font-medium hover:opacity-90">
            {c.type === 'private' ? 'Request' : 'Join'}
          </button>
      }
    </div>
  );
}

function CommunitySkeleton() {
  return (
    <div className="space-y-3">
      {[1,2,3].map((i) => (
        <div key={i} className="bg-[var(--color-surface)] rounded-2xl p-4 animate-pulse flex gap-4">
          <div className="w-12 h-12 rounded-xl bg-[var(--color-surface-alt)]" />
          <div className="flex-1 space-y-2"><div className="h-3 w-1/2 bg-[var(--color-surface-alt)] rounded" /><div className="h-2 w-3/4 bg-[var(--color-surface-alt)] rounded" /></div>
        </div>
      ))}
    </div>
  );
}
