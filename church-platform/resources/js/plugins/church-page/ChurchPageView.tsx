import { useParams } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { api } from '@/hooks/useApi';
import { useAuthStore } from '@/stores/authStore';

interface ChurchPage {
  id: number;
  name: string;
  slug: string;
  about: string;
  logo: string | null;
  banner: string | null;
  denomination: string | null;
  location: string | null;
  website: string | null;
  phone: string | null;
  email: string | null;
  founded_at: string | null;
  member_count: number;
  is_following: boolean;
  schedules: { day: string; time: string; label: string }[];
}

export default function ChurchPageView() {
  const { slug } = useParams<{ slug: string }>();
  const { isAuthenticated } = useAuthStore();
  const qc = useQueryClient();

  const { data: church, isLoading } = useQuery<ChurchPage>({
    queryKey: ['church-page', slug],
    queryFn: () => api.get(`/churches/${slug}`),
    enabled: !!slug,
  });

  const followMutation = useMutation({
    mutationFn: () => api.post(`/churches/${church?.id}/follow`),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['church-page', slug] }),
  });

  if (isLoading) return <ChurchSkeleton />;
  if (!church) return <div className="p-8 text-center text-[var(--color-muted)]">Church page not found.</div>;

  return (
    <div className="space-y-4 max-w-2xl">
      {/* Banner + Logo */}
      <div className="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-2xl overflow-hidden">
        <div className="h-32 bg-gradient-to-r from-[var(--color-primary)] to-[var(--color-accent)] relative">
          {church.banner && <img src={church.banner} className="w-full h-full object-cover" alt="" />}
          <div className="absolute -bottom-6 left-5">
            {church.logo
              ? <img src={church.logo} className="w-16 h-16 rounded-2xl border-4 border-[var(--color-surface)] object-cover" alt={church.name} />
              : <div className="w-16 h-16 rounded-2xl border-4 border-[var(--color-surface)] bg-white flex items-center justify-center text-2xl">⛪</div>
            }
          </div>
        </div>
        <div className="pt-8 px-5 pb-5 space-y-3">
          <div className="flex items-start justify-between gap-4">
            <div>
              <h1 className="text-xl font-bold">{church.name}</h1>
              {church.denomination && <p className="text-xs text-[var(--color-muted)]">{church.denomination}</p>}
              <p className="text-xs text-[var(--color-muted)]">{church.member_count} followers</p>
            </div>
            {isAuthenticated && (
              <button onClick={() => followMutation.mutate()} disabled={followMutation.isPending}
                className={`shrink-0 px-4 py-1.5 rounded-lg text-sm font-medium transition-colors ${church.is_following ? 'border border-[var(--color-border)] hover:bg-[var(--color-surface-alt)]' : 'bg-[var(--color-primary)] text-white hover:opacity-90'}`}>
                {church.is_following ? 'Following' : 'Follow'}
              </button>
            )}
          </div>
          {church.about && <p className="text-sm text-[var(--color-muted)] leading-relaxed">{church.about}</p>}
        </div>
      </div>

      {/* Info card */}
      <div className="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-2xl p-4 space-y-2">
        <h2 className="font-semibold text-sm">About</h2>
        <div className="space-y-1.5 text-sm text-[var(--color-muted)]">
          {church.location && <p>📍 {church.location}</p>}
          {church.phone && <p>📞 {church.phone}</p>}
          {church.email && <p>✉️ <a href={`mailto:${church.email}`} className="hover:text-[var(--color-primary)]">{church.email}</a></p>}
          {church.website && <p>🌐 <a href={church.website} target="_blank" rel="noreferrer" className="text-[var(--color-primary)] hover:underline">{church.website}</a></p>}
          {church.founded_at && <p>📖 Founded {new Date(church.founded_at).getFullYear()}</p>}
        </div>
      </div>

      {/* Service schedule */}
      {church.schedules && church.schedules.length > 0 && (
        <div className="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-2xl p-4 space-y-2">
          <h2 className="font-semibold text-sm">Service Times</h2>
          <div className="space-y-1">
            {church.schedules.map((s, i) => (
              <div key={i} className="flex items-center justify-between text-sm py-1 border-b border-[var(--color-border)] last:border-0">
                <span className="font-medium">{s.day} {s.label && `— ${s.label}`}</span>
                <span className="text-[var(--color-muted)]">{s.time}</span>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Posts placeholder */}
      <div className="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-2xl p-8 text-center text-[var(--color-muted)]">
        <p className="text-2xl mb-2">📢</p>
        <p className="text-sm">Church posts and announcements coming soon.</p>
      </div>
    </div>
  );
}

function ChurchSkeleton() {
  return (
    <div className="space-y-4">
      <div className="bg-[var(--color-surface)] rounded-2xl overflow-hidden animate-pulse">
        <div className="h-32 bg-[var(--color-surface-alt)]" />
        <div className="p-5 pt-8 space-y-2">
          <div className="h-4 w-48 bg-[var(--color-surface-alt)] rounded" />
          <div className="h-3 w-32 bg-[var(--color-surface-alt)] rounded" />
        </div>
      </div>
    </div>
  );
}
