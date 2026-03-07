import { useState, useEffect } from 'react';
import { useSearchParams, NavLink } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { api } from '@/hooks/useApi';

type ResultType = 'all' | 'posts' | 'users' | 'communities' | 'churches';

interface SearchResults {
  posts:       { id: number; body: string; user: { name: string; avatar: string | null }; created_at: string }[];
  users:       { id: number; name: string; avatar: string | null; user_type: string; bio: string | null }[];
  communities: { id: number; slug: string; name: string; about: string | null; member_count: number }[];
  churches:    { id: number; slug: string; name: string; about: string | null; follower_count: number }[];
}

const TABS: { key: ResultType; label: string }[] = [
  { key: 'all', label: 'All' },
  { key: 'posts', label: 'Posts' },
  { key: 'users', label: 'People' },
  { key: 'communities', label: 'Communities' },
  { key: 'churches', label: 'Churches' },
];

function Avatar({ name, avatar, size = 10 }: { name: string; avatar: string | null; size?: number }) {
  if (avatar) return <img src={avatar} className={`w-${size} h-${size} rounded-full object-cover shrink-0`} alt={name} />;
  return (
    <div className={`w-${size} h-${size} rounded-full bg-[var(--color-primary)] flex items-center justify-center text-white text-sm font-bold shrink-0`}>
      {name?.[0]?.toUpperCase()}
    </div>
  );
}

export default function SearchPage() {
  const [searchParams, setSearchParams] = useSearchParams();
  const [inputVal, setInputVal] = useState(searchParams.get('q') ?? '');
  const [type, setType] = useState<ResultType>((searchParams.get('type') as ResultType) ?? 'all');

  const q = searchParams.get('q') ?? '';

  useEffect(() => {
    setInputVal(q);
    setType((searchParams.get('type') as ResultType) ?? 'all');
  }, [searchParams]);

  const { data, isLoading, isFetching } = useQuery<SearchResults>({
    queryKey: ['search', q, type],
    queryFn: () => api.get(`/search?q=${encodeURIComponent(q)}&type=${type}`),
    enabled: q.trim().length >= 2,
    placeholderData: (prev) => prev,
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (inputVal.trim()) {
      setSearchParams({ q: inputVal.trim(), type });
    }
  };

  const posts       = data?.posts       ?? [];
  const users       = data?.users       ?? [];
  const communities = data?.communities ?? [];
  const churches    = data?.churches    ?? [];
  const totalCount  = posts.length + users.length + communities.length + churches.length;

  return (
    <div className="space-y-4 max-w-2xl">
      {/* Search bar */}
      <form onSubmit={handleSubmit} className="flex gap-2">
        <input
          value={inputVal}
          onChange={(e) => setInputVal(e.target.value)}
          placeholder="Search people, posts, communities…"
          className="flex-1 px-4 py-2.5 rounded-xl border border-[var(--color-border)] bg-[var(--color-surface)] text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]"
        />
        <button type="submit" className="px-4 py-2.5 rounded-xl bg-[var(--color-primary)] text-white text-sm font-medium hover:opacity-90">
          Search
        </button>
      </form>

      {/* Type tabs */}
      {q && (
        <div className="flex gap-1 overflow-x-auto pb-1">
          {TABS.map((tab) => (
            <button key={tab.key}
              onClick={() => { setType(tab.key); setSearchParams({ q, type: tab.key }); }}
              className={`px-3 py-1.5 rounded-lg text-sm font-medium whitespace-nowrap transition-colors ${type === tab.key ? 'bg-[var(--color-primary)] text-white' : 'bg-[var(--color-surface)] border border-[var(--color-border)] hover:border-[var(--color-primary)] text-[var(--color-muted)]'}`}>
              {tab.label}
            </button>
          ))}
        </div>
      )}

      {/* Results */}
      {!q || q.trim().length < 2 ? (
        <div className="text-center py-16 text-[var(--color-muted)]">
          <p className="text-4xl mb-3">🔍</p>
          <p className="text-sm">Enter at least 2 characters to search</p>
        </div>
      ) : isLoading ? (
        <div className="space-y-3">
          {[1,2,3,4].map((i) => (
            <div key={i} className="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-2xl p-4 animate-pulse">
              <div className="flex items-center gap-3">
                <div className="w-10 h-10 rounded-full bg-[var(--color-surface-alt)]" />
                <div className="flex-1 space-y-2">
                  <div className="h-3 w-32 bg-[var(--color-surface-alt)] rounded" />
                  <div className="h-2 w-48 bg-[var(--color-surface-alt)] rounded" />
                </div>
              </div>
            </div>
          ))}
        </div>
      ) : totalCount === 0 ? (
        <div className="text-center py-16 text-[var(--color-muted)]">
          <p className="text-3xl mb-3">😔</p>
          <p className="font-medium">No results for "{q}"</p>
          <p className="text-sm mt-1">Try different keywords</p>
        </div>
      ) : (
        <div className="space-y-4">
          {isFetching && <div className="text-xs text-center text-[var(--color-muted)]">Searching…</div>}

          {/* People */}
          {(type === 'all' || type === 'users') && users.length > 0 && (
            <section>
              <h2 className="text-xs font-semibold uppercase tracking-wider text-[var(--color-muted)] mb-2 px-1">People</h2>
              <div className="space-y-2">
                {users.map((u) => (
                  <NavLink key={u.id} to={`/profile/${u.id}`}
                    className="flex items-center gap-3 bg-[var(--color-surface)] border border-[var(--color-border)] rounded-2xl p-4 hover:border-[var(--color-primary)] transition-colors">
                    <Avatar name={u.name} avatar={u.avatar} />
                    <div className="flex-1 min-w-0">
                      <p className="font-medium text-sm">{u.name}</p>
                      {u.bio && <p className="text-xs text-[var(--color-muted)] truncate">{u.bio}</p>}
                    </div>
                    <span className="text-[10px] bg-[var(--color-surface-alt)] text-[var(--color-muted)] px-2 py-0.5 rounded-full capitalize shrink-0">
                      {u.user_type?.replace('_', ' ')}
                    </span>
                  </NavLink>
                ))}
              </div>
            </section>
          )}

          {/* Posts */}
          {(type === 'all' || type === 'posts') && posts.length > 0 && (
            <section>
              <h2 className="text-xs font-semibold uppercase tracking-wider text-[var(--color-muted)] mb-2 px-1">Posts</h2>
              <div className="space-y-2">
                {posts.map((p) => (
                  <NavLink key={p.id} to={`/post/${p.id}`}
                    className="flex gap-3 bg-[var(--color-surface)] border border-[var(--color-border)] rounded-2xl p-4 hover:border-[var(--color-primary)] transition-colors">
                    <Avatar name={p.user?.name ?? '?'} avatar={p.user?.avatar ?? null} size={8} />
                    <div className="flex-1 min-w-0">
                      <p className="text-xs text-[var(--color-muted)] mb-1">{p.user?.name}</p>
                      <p className="text-sm line-clamp-2">{p.body}</p>
                    </div>
                  </NavLink>
                ))}
              </div>
            </section>
          )}

          {/* Communities */}
          {(type === 'all' || type === 'communities') && communities.length > 0 && (
            <section>
              <h2 className="text-xs font-semibold uppercase tracking-wider text-[var(--color-muted)] mb-2 px-1">Communities</h2>
              <div className="space-y-2">
                {communities.map((c) => (
                  <NavLink key={c.id} to={`/community/${c.slug}`}
                    className="flex items-center gap-3 bg-[var(--color-surface)] border border-[var(--color-border)] rounded-2xl p-4 hover:border-[var(--color-primary)] transition-colors">
                    <div className="w-10 h-10 rounded-xl bg-gradient-to-br from-[var(--color-primary)] to-[var(--color-accent)] flex items-center justify-center text-white text-lg shrink-0">🏘️</div>
                    <div className="flex-1 min-w-0">
                      <p className="font-medium text-sm">{c.name}</p>
                      {c.about && <p className="text-xs text-[var(--color-muted)] truncate">{c.about}</p>}
                    </div>
                    <span className="text-xs text-[var(--color-muted)] shrink-0">{c.member_count} members</span>
                  </NavLink>
                ))}
              </div>
            </section>
          )}

          {/* Churches */}
          {(type === 'all' || type === 'churches') && churches.length > 0 && (
            <section>
              <h2 className="text-xs font-semibold uppercase tracking-wider text-[var(--color-muted)] mb-2 px-1">Churches</h2>
              <div className="space-y-2">
                {churches.map((c) => (
                  <NavLink key={c.id} to={`/@${c.slug}`}
                    className="flex items-center gap-3 bg-[var(--color-surface)] border border-[var(--color-border)] rounded-2xl p-4 hover:border-[var(--color-primary)] transition-colors">
                    <div className="w-10 h-10 rounded-xl bg-gradient-to-br from-orange-400 to-red-500 flex items-center justify-center text-white text-lg shrink-0">⛪</div>
                    <div className="flex-1 min-w-0">
                      <p className="font-medium text-sm">{c.name}</p>
                      {c.about && <p className="text-xs text-[var(--color-muted)] truncate">{c.about}</p>}
                    </div>
                    <span className="text-xs text-[var(--color-muted)] shrink-0">{c.follower_count} followers</span>
                  </NavLink>
                ))}
              </div>
            </section>
          )}
        </div>
      )}
    </div>
  );
}
