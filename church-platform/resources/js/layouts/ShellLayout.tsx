import { Outlet, NavLink, useNavigate } from 'react-router-dom';
import { useAuthStore } from '@/stores/authStore';
import { useSettingsStore } from '@/stores/settingsStore';
import { useThemeStore } from '@/stores/themeStore';
import { useLogout } from '@/hooks/useAuth';

const navItems = [
  { to: '/',             icon: '🏠', label: 'Home' },
  { to: '/prayer',       icon: '🙏', label: 'Prayer' },
  { to: '/events',       icon: '📅', label: 'Events' },
  { to: '/bible-studies',icon: '📖', label: 'Bible' },
  { to: '/communities',  icon: '👥', label: 'Groups' },
];

export default function ShellLayout() {
  const { user, isAuthenticated } = useAuthStore();
  const settings = useSettingsStore((s) => s.settings);
  const { colorMode, setColorMode } = useThemeStore();
  const logout = useLogout();
  const navigate = useNavigate();

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
            <NavLink
              key={item.to}
              to={item.to}
              end={item.to === '/'}
              className={({ isActive }) =>
                `px-3 py-2 rounded-lg text-sm font-medium transition-colors ${
                  isActive
                    ? 'bg-[var(--color-primary)] text-white'
                    : 'hover:bg-[var(--color-surface-alt)]'
                }`
              }
            >
              {item.icon} {item.label}
            </NavLink>
          ))}
        </nav>

        <div className="flex items-center gap-2 ml-auto">
          {/* Dark mode toggle */}
          <button
            onClick={() => setColorMode(isDark ? 'light' : 'dark')}
            className="p-2 rounded-full hover:bg-[var(--color-surface-alt)] transition-colors"
            aria-label="Toggle theme"
          >
            {isDark ? '☀️' : '🌙'}
          </button>

          {isAuthenticated ? (
            <div className="flex items-center gap-2">
              {user?.avatar
                ? <img src={user.avatar} className="w-8 h-8 rounded-full" alt={user.name} />
                : <div className="w-8 h-8 rounded-full bg-[var(--color-primary)] flex items-center justify-center text-white text-sm font-bold">
                    {user?.name?.[0]?.toUpperCase()}
                  </div>
              }
              <button
                onClick={() => navigate('/profile')}
                className="text-sm font-medium hidden md:block hover:text-[var(--color-primary)]"
              >
                {user?.name}
              </button>
              {user && ['super_admin', 'church_admin'].includes(user.user_type) && (
                <NavLink to="/admin" className="text-xs px-2 py-1 rounded bg-[var(--color-accent)] text-white">
                  Admin
                </NavLink>
              )}
              <button onClick={() => logout.mutate()} className="text-sm text-red-500 hover:underline hidden md:block">
                Sign out
              </button>
            </div>
          ) : (
            <div className="flex gap-2">
              <NavLink to="/login"    className="text-sm px-3 py-1.5 rounded border border-[var(--color-primary)] text-[var(--color-primary)] hover:bg-[var(--color-primary)] hover:text-white transition-colors">Login</NavLink>
              <NavLink to="/register" className="text-sm px-3 py-1.5 rounded bg-[var(--color-primary)] text-white hover:opacity-90 transition-opacity">Register</NavLink>
            </div>
          )}
        </div>
      </header>

      {/* ── Main content ── */}
      <div className="flex flex-1 max-w-7xl mx-auto w-full gap-6 px-4 py-6">
        {/* Left sidebar (desktop) */}
        <aside className="hidden lg:block w-56 shrink-0">
          <nav className="space-y-1">
            {navItems.map((item) => (
              <NavLink
                key={item.to}
                to={item.to}
                end={item.to === '/'}
                className={({ isActive }) =>
                  `flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium w-full transition-colors ${
                    isActive
                      ? 'bg-[var(--color-primary)] text-white'
                      : 'hover:bg-[var(--color-surface-alt)]'
                  }`
                }
              >
                <span>{item.icon}</span> {item.label}
              </NavLink>
            ))}
          </nav>
        </aside>

        {/* Page content */}
        <main className="flex-1 min-w-0">
          <Outlet />
        </main>

        {/* Right panel placeholder (desktop) */}
        <aside className="hidden xl:block w-72 shrink-0 space-y-4">
          {/* DailyVerse widget, upcoming events, etc. rendered by plugins */}
          <div id="right-panel-widgets" />
        </aside>
      </div>

      {/* ── Bottom nav (mobile) ── */}
      <nav className="md:hidden fixed bottom-0 inset-x-0 bg-[var(--color-surface)] border-t border-[var(--color-border)] flex z-50">
        {navItems.map((item) => (
          <NavLink
            key={item.to}
            to={item.to}
            end={item.to === '/'}
            className={({ isActive }) =>
              `flex-1 flex flex-col items-center py-2 text-xs gap-0.5 transition-colors ${
                isActive ? 'text-[var(--color-primary)]' : 'text-[var(--color-muted)]'
              }`
            }
          >
            <span className="text-lg">{item.icon}</span>
            {item.label}
          </NavLink>
        ))}
      </nav>

      {/* Bottom spacer for mobile nav */}
      <div className="md:hidden h-16" />
    </div>
  );
}
