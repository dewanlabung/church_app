import { useQuery } from '@tanstack/react-query';
import { NavLink } from 'react-router-dom';
import { api } from '@/hooks/useApi';

interface UserListResp { data: { id: number; name: string; email: string; user_type: string; created_at: string }[]; total: number; }
interface PluginListResp { plugins: { slug: string; enabled: boolean }[]; }
interface ThemeListResp { themes: { slug: string; name: string; active: boolean }[]; }

const ROLE_COLORS: Record<string, string> = {
  super_admin: 'bg-purple-100 text-purple-700',
  church_admin: 'bg-blue-100 text-blue-700',
  counsellor: 'bg-teal-100 text-teal-700',
  musician: 'bg-yellow-100 text-yellow-700',
  general_user: 'bg-gray-100 text-gray-600',
};

export default function AdminDashboard() {
  const { data: usersResp } = useQuery<UserListResp>({
    queryKey: ['admin-users'],
    queryFn: () => api.get('/admin/users?per_page=5'),
  });
  const { data: pluginsResp } = useQuery<PluginListResp>({
    queryKey: ['admin-plugins'],
    queryFn: () => api.get('/admin/plugins'),
  });
  const { data: themesResp } = useQuery<ThemeListResp>({
    queryKey: ['admin-themes'],
    queryFn: () => api.get('/admin/themes'),
  });

  const totalUsers = usersResp?.total ?? '—';
  const activePlugins = pluginsResp?.plugins?.filter((p) => p.enabled).length ?? '—';
  const totalThemes = themesResp?.themes?.length ?? '—';
  const recentUsers = usersResp?.data ?? [];

  const stats = [
    { label: 'Total Users', value: totalUsers, icon: '👥', to: '/admin/users', color: 'text-blue-600' },
    { label: 'Active Plugins', value: activePlugins, icon: '🔌', to: '/admin/plugins', color: 'text-green-600' },
    { label: 'Installed Themes', value: totalThemes, icon: '🎨', to: '/admin/themes', color: 'text-purple-600' },
    { label: 'Posts Today', value: '—', icon: '📝', to: '/', color: 'text-orange-600' },
  ];

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-bold">Dashboard</h1>

      {/* Stat cards */}
      <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
        {stats.map((s) => (
          <NavLink key={s.label} to={s.to}
            className="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-2xl p-4 hover:border-[var(--color-primary)] transition-colors">
            <div className="text-2xl mb-2">{s.icon}</div>
            <div className={`text-2xl font-bold ${s.color}`}>{String(s.value)}</div>
            <div className="text-xs text-[var(--color-muted)] mt-0.5">{s.label}</div>
          </NavLink>
        ))}
      </div>

      {/* Quick nav */}
      <div className="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-2xl p-5">
        <h2 className="font-semibold text-sm mb-3">Quick Navigation</h2>
        <div className="grid grid-cols-2 sm:grid-cols-4 gap-2">
          {[
            { to: '/admin/plugins', icon: '🔌', label: 'Plugins' },
            { to: '/admin/themes', icon: '🎨', label: 'Themes' },
            { to: '/admin/users', icon: '👥', label: 'Users' },
            { to: '/admin/settings/general', icon: '⚙️', label: 'Settings' },
          ].map((item) => (
            <NavLink key={item.to} to={item.to}
              className="flex items-center gap-2 px-3 py-2.5 rounded-xl border border-[var(--color-border)] text-sm font-medium hover:bg-[var(--color-surface-alt)] hover:border-[var(--color-primary)] transition-colors">
              <span>{item.icon}</span>{item.label}
            </NavLink>
          ))}
        </div>
      </div>

      {/* Recent users */}
      <div className="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-2xl overflow-hidden">
        <div className="px-5 py-3 border-b border-[var(--color-border)] flex items-center justify-between">
          <h2 className="font-semibold text-sm">Recent Users</h2>
          <NavLink to="/admin/users" className="text-xs text-[var(--color-primary)] hover:underline">View all</NavLink>
        </div>
        {recentUsers.length === 0 ? (
          <div className="py-8 text-center text-[var(--color-muted)] text-sm">No users yet.</div>
        ) : (
          <div className="divide-y divide-[var(--color-border)]">
            {recentUsers.map((u) => (
              <div key={u.id} className="flex items-center gap-3 px-5 py-3">
                <div className="w-8 h-8 rounded-full bg-[var(--color-primary)] flex items-center justify-center text-white text-sm font-bold shrink-0">
                  {u.name?.[0]?.toUpperCase()}
                </div>
                <div className="flex-1 min-w-0">
                  <p className="text-sm font-medium truncate">{u.name}</p>
                  <p className="text-xs text-[var(--color-muted)] truncate">{u.email}</p>
                </div>
                <span className={`text-[10px] px-2 py-0.5 rounded-full font-medium shrink-0 capitalize ${ROLE_COLORS[u.user_type] ?? 'bg-gray-100 text-gray-600'}`}>
                  {u.user_type?.replace('_', ' ')}
                </span>
                <span className="text-xs text-[var(--color-muted)] shrink-0 hidden sm:block">
                  {new Date(u.created_at).toLocaleDateString()}
                </span>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}
