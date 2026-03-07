import { useState, useCallback } from 'react';
import { useInfiniteQuery } from '@tanstack/react-query';
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
}

interface FeedPage {
  data: Post[];
  next_cursor: string | null;
  has_more: boolean;
}

const FILTERS = [
  { key: 'all',    label: '🌐 All' },
  { key: 'prayer', label: '🙏 Prayer' },
  { key: 'bible',  label: '📖 Bible' },
  { key: 'events', label: '📅 Events' },
  { key: 'church', label: '⛪ Church' },
];

export default function TimelinePage() {
  const { isAuthenticated } = useAuthStore();
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
          <p className="text-xs font-semibold uppercase tracking-wider text-[var(--color-muted)] mb-1">
            📖 Verse of the Day
          </p>
          <p className="text-sm italic">"{(verse as any).text}"</p>
          <p className="text-xs text-[var(--color-muted)] mt-1">— {(verse as any).reference}</p>
        </div>
      )}

      {/* Feed filters */}
      <div className="flex gap-2 overflow-x-auto pb-1 scrollbar-none">
        {FILTERS.map((f) => (
          <button
            key={f.key}
            onClick={() => setFilter(f.key)}
            className={`px-3 py-1.5 rounded-full text-sm font-medium whitespace-nowrap transition-colors ${
              filter === f.key
                ? 'bg-[var(--color-primary)] text-white'
                : 'bg-[var(--color-surface)] hover:bg-[var(--color-surface-alt)]'
            }`}
          >
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
            <button
              onClick={() => fetchNextPage()}
              disabled={isFetchingNextPage}
              className="w-full py-3 rounded-xl bg-[var(--color-surface)] hover:bg-[var(--color-surface-alt)] text-sm font-medium transition-colors disabled:opacity-50"
            >
              {isFetchingNextPage ? 'Loading…' : 'Load more'}
            </button>
          )}
        </>
      )}
    </div>
  );
}

function PostCard({ post }: { post: Post }) {
  const ago = new Date(post.created_at).toLocaleDateString();
  return (
    <div className="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-2xl p-4 space-y-3">
      <div className="flex items-center gap-3">
        {post.avatar
          ? <img src={post.avatar} className="w-10 h-10 rounded-full" alt={post.name} />
          : <div className="w-10 h-10 rounded-full bg-[var(--color-primary)] flex items-center justify-center text-white font-bold">
              {post.name?.[0]?.toUpperCase()}
            </div>
        }
        <div>
          <p className="font-semibold text-sm">{post.name}</p>
          <p className="text-xs text-[var(--color-muted)]">{ago} · <span className="capitalize">{post.type.replace('_', ' ')}</span></p>
        </div>
      </div>
      {post.body && <p className="text-sm whitespace-pre-wrap">{post.body}</p>}
      <div className="flex gap-4 text-xs text-[var(--color-muted)] pt-1 border-t border-[var(--color-border)]">
        <button className="hover:text-[var(--color-primary)] transition-colors">👍 {post.reactions_count}</button>
        <button className="hover:text-[var(--color-primary)] transition-colors">💬 {post.comments_count}</button>
        <button className="hover:text-[var(--color-primary)] transition-colors">↗️ Share</button>
      </div>
    </div>
  );
}

function FeedSkeleton() {
  return (
    <div className="space-y-4">
      {[1, 2, 3].map((i) => (
        <div key={i} className="bg-[var(--color-surface)] rounded-2xl p-4 space-y-3 animate-pulse">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-full bg-[var(--color-surface-alt)]" />
            <div className="space-y-1">
              <div className="h-3 w-24 bg-[var(--color-surface-alt)] rounded" />
              <div className="h-2 w-16 bg-[var(--color-surface-alt)] rounded" />
            </div>
          </div>
          <div className="space-y-2">
            <div className="h-3 w-full bg-[var(--color-surface-alt)] rounded" />
            <div className="h-3 w-4/5 bg-[var(--color-surface-alt)] rounded" />
          </div>
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
