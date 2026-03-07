import { useState, useRef } from 'react';
import { useInfiniteQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useNavigate } from 'react-router-dom';
import { api, useTodayVerse, useGreeting } from '@/hooks/useApi';
import { useAuthStore } from '@/stores/authStore';
import { useSettingsStore } from '@/stores/settingsStore';

interface Post {
  id: number;
  user_id: number;
  name: string;
  avatar: string | null;
  type: string;
  body: string;
  reactions_count: number;
  comments_count: number;
  created_at: string;
  feed_id: number;
  user_reaction: string | null;
}
interface FeedPage { data: Post[]; next_cursor: string | null; has_more: boolean; }

const FILTERS = [
  { key: 'all',    label: '🌐 All' },
  { key: 'prayer', label: '🙏 Prayer' },
  { key: 'bible',  label: '📖 Bible' },
  { key: 'events', label: '📅 Events' },
  { key: 'church', label: '⛪ Church' },
];

const POST_TYPES = [
  { key: 'text',            label: '✍️ Update' },
  { key: 'bible_verse',     label: '📖 Verse' },
  { key: 'prayer_request',  label: '🙏 Prayer Request' },
  { key: 'ask_question',    label: '❓ Question' },
  { key: 'blessing',        label: '👐 Blessing' },
];

const REACTIONS = [
  { key: 'like',  emoji: '👍' },
  { key: 'bless', emoji: '👐' },
  { key: 'amen',  emoji: '🙏' },
  { key: 'pray',  emoji: '💛' },
  { key: 'love',  emoji: '❤️' },
];

export default function TimelinePage() {
  const { isAuthenticated, user } = useAuthStore();
  const settings = useSettingsStore((s) => s.settings);
  const [filter, setFilter] = useState('all');

  const { data: verse } = useTodayVerse();
  const { data: greeting } = useGreeting(isAuthenticated && !!settings?.greeting_enabled);

  const {
    data, fetchNextPage, hasNextPage, isFetchingNextPage, isLoading,
  } = useInfiniteQuery<FeedPage>({
    queryKey: ['feed', filter],
    queryFn: ({ pageParam }) =>
      api.get(`/feed?filter=${filter}${pageParam ? `&cursor=${pageParam}` : ''}`),
    getNextPageParam: (last) => last.next_cursor ?? undefined,
    initialPageParam: undefined,
    enabled: isAuthenticated,
  });

  const posts = data?.pages.flatMap((p) => p.data) ?? [];

  return (
    <div className="space-y-4">
      {/* Greeting banner */}
      {greeting && (
        <div className="bg-[var(--color-primary)] text-white rounded-2xl p-4">
          <p className="font-semibold">{(greeting as any).message}</p>
        </div>
      )}

      {/* Daily Verse widget */}
      {settings?.daily_verse_enabled && verse && (
        <div className="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-2xl p-4">
          <p className="text-xs font-semibold uppercase tracking-wider text-[var(--color-muted)] mb-1">📖 Verse of the Day</p>
          <p className="text-sm italic">"{(verse as any).text}"</p>
          <p className="text-xs text-[var(--color-muted)] mt-1">— {(verse as any).reference}</p>
        </div>
      )}

      {/* Create post composer */}
      {isAuthenticated && <CreatePostComposer user={user} onCreated={() => {}} />}

      {/* Feed filters */}
      <div className="flex gap-2 overflow-x-auto pb-1 scrollbar-none">
        {FILTERS.map((f) => (
          <button key={f.key} onClick={() => setFilter(f.key)}
            className={`px-3 py-1.5 rounded-full text-sm font-medium whitespace-nowrap transition-colors ${filter === f.key ? 'bg-[var(--color-primary)] text-white' : 'bg-[var(--color-surface)] hover:bg-[var(--color-surface-alt)]'}`}>
            {f.label}
          </button>
        ))}
      </div>

      {/* Posts */}
      {isLoading ? (
        <FeedSkeleton />
      ) : posts.length === 0 ? (
        <EmptyFeed />
      ) : (
        <>
          {posts.map((post) => <PostCard key={post.feed_id ?? post.id} post={post} />)}
          {hasNextPage && (
            <button onClick={() => fetchNextPage()} disabled={isFetchingNextPage}
              className="w-full py-3 rounded-xl bg-[var(--color-surface)] hover:bg-[var(--color-surface-alt)] text-sm font-medium transition-colors disabled:opacity-50">
              {isFetchingNextPage ? 'Loading…' : 'Load more'}
            </button>
          )}
        </>
      )}
    </div>
  );
}

// ── Create Post Composer ──────────────────────────────────────────────────────

function CreatePostComposer({ user, onCreated }: { user: any; onCreated: () => void }) {
  const [open, setOpen] = useState(false);
  const [type, setType] = useState('text');
  const [body, setBody] = useState('');
  const qc = useQueryClient();

  const createMutation = useMutation({
    mutationFn: () => api.post('/posts', { type, body, privacy: 'public' }),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['feed'] });
      setBody('');
      setOpen(false);
    },
  });

  return (
    <div className="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-2xl p-3">
      {!open ? (
        <button onClick={() => setOpen(true)} className="flex items-center gap-3 w-full text-left">
          <AvatarIcon name={user?.name ?? ''} avatar={user?.avatar ?? null} size="sm" />
          <span className="flex-1 px-3 py-2 rounded-full bg-[var(--color-surface-alt)] text-sm text-[var(--color-muted)] hover:bg-[var(--color-border)] transition-colors">
            What's on your heart, {user?.name?.split(' ')[0]}?
          </span>
        </button>
      ) : (
        <div className="space-y-3">
          {/* Post type selector */}
          <div className="flex gap-1.5 flex-wrap">
            {POST_TYPES.map((t) => (
              <button key={t.key} onClick={() => setType(t.key)}
                className={`px-2.5 py-1 rounded-full text-xs font-medium transition-colors ${type === t.key ? 'bg-[var(--color-primary)] text-white' : 'bg-[var(--color-surface-alt)] hover:bg-[var(--color-border)]'}`}>
                {t.label}
              </button>
            ))}
          </div>
          <div className="flex gap-3">
            <AvatarIcon name={user?.name ?? ''} avatar={user?.avatar ?? null} size="sm" />
            <textarea
              value={body}
              onChange={(e) => setBody(e.target.value)}
              placeholder={type === 'prayer_request' ? 'Share your prayer request…' : type === 'bible_verse' ? 'Share a scripture…' : type === 'ask_question' ? 'Ask a question…' : 'What\'s on your heart?'}
              rows={3}
              autoFocus
              className="flex-1 px-3 py-2 rounded-xl border border-[var(--color-border)] bg-[var(--color-background)] text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] resize-none"
            />
          </div>
          <div className="flex gap-2 justify-end">
            <button onClick={() => { setOpen(false); setBody(''); }} className="px-3 py-1.5 text-sm rounded-lg border border-[var(--color-border)] hover:bg-[var(--color-surface-alt)]">Cancel</button>
            <button onClick={() => createMutation.mutate()} disabled={!body.trim() || createMutation.isPending}
              className="px-4 py-1.5 text-sm rounded-lg bg-[var(--color-primary)] text-white disabled:opacity-50 font-medium">
              {createMutation.isPending ? 'Posting…' : 'Post'}
            </button>
          </div>
        </div>
      )}
    </div>
  );
}

// ── Post Card ─────────────────────────────────────────────────────────────────

function PostCard({ post }: { post: Post }) {
  const navigate = useNavigate();
  const { isAuthenticated } = useAuthStore();
  const qc = useQueryClient();
  const ago = new Date(post.created_at).toLocaleDateString();

  const reactMutation = useMutation({
    mutationFn: (reaction: string) => api.post(`/posts/${post.id}/react`, { reaction }),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['feed'] }),
  });

  return (
    <div className="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-2xl p-4 space-y-3">
      <div className="flex items-center gap-3">
        <AvatarIcon name={post.name} avatar={post.avatar} />
        <div>
          <p className="font-semibold text-sm">{post.name}</p>
          <p className="text-xs text-[var(--color-muted)]">{ago} · <span className="capitalize">{post.type.replace('_', ' ')}</span></p>
        </div>
      </div>
      {post.body && <p className="text-sm whitespace-pre-wrap leading-relaxed">{post.body}</p>}
      <div className="flex gap-3 text-xs text-[var(--color-muted)] pt-1 border-t border-[var(--color-border)]">
        {/* Inline reactions */}
        <div className="flex gap-1">
          {REACTIONS.map((r) => (
            <button key={r.key} onClick={() => isAuthenticated && reactMutation.mutate(r.key)} disabled={!isAuthenticated}
              title={r.key}
              className={`px-1.5 py-0.5 rounded-full transition-colors text-base leading-none ${post.user_reaction === r.key ? 'bg-[var(--color-primary)]/20' : 'hover:bg-[var(--color-surface-alt)]'}`}>
              {r.emoji}
            </button>
          ))}
          {post.reactions_count > 0 && <span className="self-center ml-1">{post.reactions_count}</span>}
        </div>
        <button onClick={() => navigate(`/post/${post.id}`)} className="hover:text-[var(--color-primary)] transition-colors ml-auto">
          💬 {post.comments_count} Comments
        </button>
      </div>
    </div>
  );
}

// ── Shared ────────────────────────────────────────────────────────────────────

function AvatarIcon({ name, avatar, size = 'md' }: { name: string; avatar: string | null; size?: 'sm' | 'md' }) {
  const cls = size === 'sm' ? 'w-8 h-8 text-xs' : 'w-10 h-10 text-sm';
  return avatar
    ? <img src={avatar} className={`${cls} rounded-full object-cover shrink-0`} alt={name} />
    : <div className={`${cls} rounded-full bg-[var(--color-primary)] flex items-center justify-center text-white font-bold shrink-0`}>{name?.[0]?.toUpperCase()}</div>;
}

function FeedSkeleton() {
  return (
    <div className="space-y-4">
      {[1, 2, 3].map((i) => (
        <div key={i} className="bg-[var(--color-surface)] rounded-2xl p-4 space-y-3 animate-pulse">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-full bg-[var(--color-surface-alt)]" />
            <div className="space-y-1"><div className="h-3 w-24 bg-[var(--color-surface-alt)] rounded" /><div className="h-2 w-16 bg-[var(--color-surface-alt)] rounded" /></div>
          </div>
          <div className="space-y-2"><div className="h-3 w-full bg-[var(--color-surface-alt)] rounded" /><div className="h-3 w-4/5 bg-[var(--color-surface-alt)] rounded" /></div>
        </div>
      ))}
    </div>
  );
}

function EmptyFeed() {
  return (
    <div className="text-center py-16 text-[var(--color-muted)]">
      <p className="text-4xl mb-3">⛪</p>
      <p className="font-medium">No posts yet. Be the first to share!</p>
    </div>
  );
}
