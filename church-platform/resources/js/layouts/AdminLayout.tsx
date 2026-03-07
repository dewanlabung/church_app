import { Outlet, NavLink, useNavigate } from 'react-router-dom';
import { useAuthStore } from '@/stores/authStore';

const adminNav = [
  { to: '/admin',          label: '📊 Dashboard',    end: true },
  { to: '/admin/users',    label: '👤 Users' },
  { to: '/admin/plugins',  label: '🔌 Plugins' },
  { to: '/admin/themes',   label: '🎨 Themes' },
  { to: '/admin/settings', label: '⚙️ Settings' },
  { to: '/',               label: '← Back to Site', end: true },
];

export default function AdminLayout() {
  const { user } = useAuthStore();
  const navigate = useNavigate();

  return (
    <div className="min-h-screen bg-gray-950 text-gray-100 flex">
      {/* Sidebar */}
      <aside className="w-60 bg-gray-900 border-r border-gray-800 flex flex-col shrink-0">
        <div className="p-4 border-b border-gray-800">
          <p className="text-xs text-gray-400 uppercase tracking-wider">Admin Panel</p>
          <p className="font-semibold truncate mt-0.5">{user?.name}</p>
        </div>

        <nav className="flex-1 p-3 space-y-1">
          {adminNav.map((item) => (
            <NavLink
              key={item.to + item.label}
              to={item.to}
              end={'end' in item ? item.end : false}
              className={({ isActive }) =>
                `flex items-center px-3 py-2 rounded-lg text-sm transition-colors w-full ${
                  isActive && item.to !== '/'
                    ? 'bg-indigo-600 text-white'
                    : 'hover:bg-gray-800 text-gray-300'
                }`
              }
            >
              {item.label}
            </NavLink>
          ))}
        </nav>
      </aside>

      {/* Main */}
      <div className="flex-1 flex flex-col min-w-0">
        <header className="h-14 bg-gray-900 border-b border-gray-800 flex items-center px-6 gap-4">
          <h1 className="text-sm font-medium text-gray-300">Church Platform Admin</h1>
        </header>
        <main className="flex-1 p-6 overflow-auto">
          <Outlet />
        </main>
      </div>
    </div>
  );
}
