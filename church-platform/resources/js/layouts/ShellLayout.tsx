import { useState, useRef, useEffect } from 'react';
import { Outlet, NavLink, useNavigate } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { useAuthStore } from '@/stores/authStore';
import { useSettingsStore } from '@/stores/settingsStore';
import { useThemeStore } from '@/stores/themeStore';
import { useLogout } from '@/hooks/useAuth';
import { useNotifications, useNotificationStore } from '@/hooks/useNotifications';
import { api } from '@/hooks/useApi';

const navItems = [
  { to: '/',              icon: '🏠', label: 'Home' },
  { to: '/search',        icon: '🔍', label: 'Search' },
  { to: '/prayer',        icon: '🙏', label: 'Prayer' },
  { to: '/events',        icon: '📅', label: 'Events' },
  { to: '/bible-studies', icon: '📖', label: 'Bible' },
  { to: '/communities',   icon: '👥', label: 'Groups' },
  { to: '/chat',          icon: '💬', label: 'Chat' },
];

export default function ShellLayout() {
  const { user, isAuthenticated } = useAuthStore();
  const settings = useSettingsStore((s) => s.settings);
  const { colorMode, setColorMode } = useThemeStore();
  const logout = useLogout();
  const navigate = useNavigate();

  // Subscribe to real-time notifications from Workerman socket
  useNotifications();

  const isDark = colorMode === 'dark' || (colorMode === 'system' &&
    window.matchMedia('(prefers-color-scheme: dark)').matches);

  return (
    <div className="min-h-screen bg-[var(--color-background)] text-[var(--color-text)] flex flex-col">
      {/* ── Top Nav ── */}
      <header className="sticky top-0 z-50 bg-[var(--color-surface)] border-b border-[var(--color-border)] h-14 flex items-center px-4 gap-4">
        <NavLink to="/" className="font-bold text-[var(--color-primary)] text-lg shrink-0">
          {settings?.logo_light
            ? <img src={settings.logo_light} alt={settings.app_name} className="h-8" />
            : settings?.app_name ?? 'Church Platform'}
        </NavLink>

        {/* Desktop nav */}
        <nav className="hidden md:flex items-center gap-1 flex-1">
          {navItems.map((item) => (
            <NavLink key={item.to} to={item.to} end={item.to === '/'}
              className={({ isActive }) =>
                `px-3 py-2 rounded-lg text-sm font-medium transition-colors ${isActive ? 'bg-[var(--color-primary)] text-white' : 'hover:bg-[var(--color-surface-alt)]'}`
              }>{item.icon} {item.label}</NavLink>
          ))}
        </nav>

        <div className="flex items-center gap-2 ml-auto">
          {/* Dark mode toggle */}
          <button onClick={() => setColorMode(isDark ? 'light' : 'dark')}
            className="p-2 rounded-full hover:bg-[var(--color-surface-alt)] transition-colors" aria-label="Toggle theme">
            {isDark ? '☀️' : '🌙'}
          </button>

          {isAuthenticated && <NotificationBell />}

          {isAuthenticated ? (
            <div className="flex items-center gap-2">
              {user?.avatar
                ? <img src={user.avatar} className="w-8 h-8 rounded-full cursor-pointer" alt={user.name} onClick={() => navigate('/profile')} />
                : <div className="w-8 h-8 rounded-full bg-[var(--color-primary)] flex items-center justify-center text-white text-sm font-bold cursor-pointer" onClick={() => navigate('/profile')}>
                    {user?.name?.[0]?.toUpperCase()}
                  </div>
              }
              <button onClick={() => navigate('/profile')} className="text-sm font-medium hidden md:block hover:text-[var(--color-primary)]">{user?.name}</button>
              {user && ['super_admin', 'church_admin'].includes(user.user_type) && (
                <NavLink to="/admin" className="text-xs px-2 py-1 rounded bg-[var(--color-accent)] text-white">Admin</NavLink>
              )}
              <button onClick={() => logout.mutate()} className="text-sm text-red-500 hover:underline hidden md:block">Sign out</button>
            </div>
          ) : (
            <div className="flex gap-2">
              <NavLink to="/login" className="text-sm px-3 py-1.5 rounded border border-[var(--color-primary)] text-[var(--color-primary)] hover:bg-[var(--color-primary)] hover:text-white transition-colors">Login</NavLink>
              <NavLink to="/register" className="text-sm px-3 py-1.5 rounded bg-[var(--color-primary)] text-white hover:opacity-90 transition-opacity">Register</NavLink>
            </div>
          )}
        </div>
      </header>

      {/* ── Main content ── */}
      <div className="flex flex-1 max-w-7xl mx-auto w-full gap-6 px-4 py-6">
        <aside className="hidden lg:block w-56 shrink-0">
          <nav className="space-y-1">
            {navItems.map((item) => (
              <NavLink key={item.to} to={item.to} end={item.to === '/'}
                className={({ isActive }) =>
                  `flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium w-full transition-colors ${isActive ? 'bg-[var(--color-primary)] text-white' : 'hover:bg-[var(--color-surface-alt)]'}`
                }><span>{item.icon}</span> {item.label}</NavLink>
            ))}
          </nav>
        </aside>

        <main className="flex-1 min-w-0"><Outlet /></main>

        <aside className="hidden xl:block w-72 shrink-0 space-y-4">
          <div id="right-panel-widgets" />
        </aside>
      </div>

      {/* ── Bottom nav (mobile) ── */}
      <nav className="md:hidden fixed bottom-0 inset-x-0 bg-[var(--color-surface)] border-t border-[var(--color-border)] flex z-50">
        {navItems.map((item) => (
          <NavLink key={item.to} to={item.to} end={item.to === '/'}
            className={({ isActive }) =>
              `flex-1 flex flex-col items-center py-2 text-xs gap-0.5 transition-colors ${isActive ? 'text-[var(--color-primary)]' : 'text-[var(--color-muted)]'}`
            }><span className="text-lg">{item.icon}</span>{item.label}</NavLink>
        ))}
      </nav>

      <div className="md:hidden h-16" />
    </div>
  );
}

// ── Notification Bell ──────────────────────────────────────────────────────────

interface Notification {
  id: number;
  type: string;
  message: string;
  read_at: string | null;
  created_at: string;
}

function NotificationBell() {
  const [open, setOpen] = useState(false);
  const { unread, reset } = useNotificationStore();
  const ref = useRef<HTMLDivElement>(null);

  const { data: notifications = [], refetch } = useQuery<Notification[]>({
    queryKey: ['notifications'],
    queryFn: () => api.get('/notifications'),
    enabled: false, // only fetch when bell opened
  });

  const handleOpen = () => {
    setOpen((o) => !o);
    if (!open) { refetch(); reset(); }
  };

  // Close on outside click
  useEffect(() => {
    function handler(e: MouseEvent) {
      if (ref.current && !ref.current.contains(e.target as Node)) setOpen(false);
    }
    document.addEventListener('mousedown', handler);
    return () => document.removeEventListener('mousedown', handler);
  }, []);

  return (
    <div className="relative" ref={ref}>
      <button onClick={handleOpen}
        className="p-2 rounded-full hover:bg-[var(--color-surface-alt)] transition-colors relative" aria-label="Notifications">
        🔔
        {unread > 0 && (
          <span className="absolute -top-0.5 -right-0.5 w-4 h-4 rounded-full bg-red-500 text-white text-[10px] flex items-center justify-center font-bold">
            {unread > 9 ? '9+' : unread}
          </span>
        )}
      </button>

      {open && (
        <div className="absolute right-0 top-full mt-2 w-80 bg-[var(--color-surface)] border border-[var(--color-border)] rounded-2xl shadow-lg overflow-hidden z-50">
          <div className="px-4 py-3 border-b border-[var(--color-border)] flex items-center justify-between">
            <p className="font-semibold text-sm">Notifications</p>
            <button onClick={() => setOpen(false)} className="text-[var(--color-muted)] hover:text-[var(--color-text)] text-xs">Close</button>
          </div>
          <div className="max-h-80 overflow-y-auto divide-y divide-[var(--color-border)]">
            {(notifications as Notification[]).length === 0 ? (
              <div className="py-8 text-center text-[var(--color-muted)] text-sm">No notifications yet.</div>
            ) : (
              (notifications as Notification[]).map((n) => (
                <div key={n.id} className={`px-4 py-3 text-sm ${n.read_at ? '' : 'bg-[var(--color-primary)]/5'}`}>
                  <p>{n.message}</p>
                  <p className="text-xs text-[var(--color-muted)] mt-0.5">{new Date(n.created_at).toLocaleDateString()}</p>
                </div>
              ))
            )}
          </div>
          <div className="px-4 py-2 border-t border-[var(--color-border)] flex justify-between items-center">
            <NavLink to="/notifications/preferences" onClick={() => setOpen(false)} className="text-xs text-[var(--color-primary)] hover:underline">
              Preferences
            </NavLink>
          </div>
        </div>
      )}
    </div>
  );
}
