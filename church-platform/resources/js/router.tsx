import { lazy, Suspense } from 'react';
import { createBrowserRouter, Navigate } from 'react-router-dom';
import { useAuthStore } from '@/stores/authStore';
import ShellLayout from '@/layouts/ShellLayout';
import AuthLayout from '@/layouts/AuthLayout';
import AdminLayout from '@/layouts/AdminLayout';

// ── Lazy pages ────────────────────────────────────────────────────────────

const TimelinePage  = lazy(() => import('@/plugins/timeline/TimelinePage'));
const PostPage      = lazy(() => import('@/plugins/post/PostPage'));
const PrayerWall    = lazy(() => import('@/plugins/prayer/PrayerWallPage'));
const EventsPage    = lazy(() => import('@/plugins/events/EventsPage'));
const BibleStudyPage= lazy(() => import('@/plugins/bible-study/BibleStudyPage'));
const CommunityPage = lazy(() => import('@/plugins/community/CommunityPage'));
const ChurchPageView= lazy(() => import('@/plugins/church-page/ChurchPageView'));
const ProfilePage   = lazy(() => import('@/plugins/settings/ProfilePage'));

const LoginPage     = lazy(() => import('@/plugins/settings/LoginPage'));
const RegisterPage  = lazy(() => import('@/plugins/settings/RegisterPage'));

const AdminDashboard = lazy(() => import('@/plugins/admin/AdminDashboard'));
const PluginManager  = lazy(() => import('@/plugins/admin/PluginManagerPage'));
const ThemesPage     = lazy(() => import('@/plugins/admin/ThemesPage'));
const UsersAdmin     = lazy(() => import('@/plugins/admin/UsersPage'));
const SettingsAdmin  = lazy(() => import('@/plugins/admin/SettingsPage'));
const SearchPage     = lazy(() => import('@/plugins/search/SearchPage'));
const ChatPage       = lazy(() => import('@/plugins/chat/ChatPage'));
const NotifPrefsPage = lazy(() => import('@/plugins/settings/NotificationPreferencesPage'));

// ── Guards ────────────────────────────────────────────────────────────────

function RequireAuth({ children }: { children: React.ReactNode }) {
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated);
  return isAuthenticated ? <>{children}</> : <Navigate to="/login" replace />;
}

function RequireAdmin({ children }: { children: React.ReactNode }) {
  const { isAuthenticated, hasRole } = useAuthStore();
  if (!isAuthenticated) return <Navigate to="/login" replace />;
  if (!hasRole(['super_admin', 'church_admin'])) return <Navigate to="/" replace />;
  return <>{children}</>;
}

function GuestOnly({ children }: { children: React.ReactNode }) {
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated);
  return !isAuthenticated ? <>{children}</> : <Navigate to="/" replace />;
}

const load = (el: React.ReactNode) => (
  <Suspense fallback={<div className="flex h-screen items-center justify-center">Loading…</div>}>
    {el}
  </Suspense>
);

// ── Router ────────────────────────────────────────────────────────────────

export const router = createBrowserRouter([
  // Auth routes
  {
    element: <AuthLayout />,
    children: [
      { path: '/login',    element: <GuestOnly>{load(<LoginPage />)}</GuestOnly> },
      { path: '/register', element: <GuestOnly>{load(<RegisterPage />)}</GuestOnly> },
    ],
  },

  // Admin routes
  {
    path: '/admin',
    element: <RequireAdmin><AdminLayout /></RequireAdmin>,
    children: [
      { index: true,           element: load(<AdminDashboard />) },
      { path: 'plugins',       element: load(<PluginManager />) },
      { path: 'themes',        element: load(<ThemesPage />) },
      { path: 'users',         element: load(<UsersAdmin />) },
      { path: 'settings',      element: load(<SettingsAdmin />) },
      { path: 'settings/:tab', element: load(<SettingsAdmin />) },
    ],
  },

  // Main app shell
  {
    path: '/',
    element: <ShellLayout />,
    children: [
      { index: true,                      element: <RequireAuth>{load(<TimelinePage />)}</RequireAuth> },
      { path: 'post/:id',                 element: load(<PostPage />) },
      { path: 'prayer',                   element: load(<PrayerWall />) },
      { path: 'events',                   element: load(<EventsPage />) },
      { path: 'bible-studies',            element: load(<BibleStudyPage />) },
      { path: 'communities',              element: load(<CommunityPage />) },
      { path: 'c/:slug',                  element: load(<CommunityPage />) },
      { path: 'church/:slug',             element: load(<ChurchPageView />) },
      { path: 'search',                   element: load(<SearchPage />) },
      { path: 'profile',                  element: <RequireAuth>{load(<ProfilePage />)}</RequireAuth> },
      { path: 'notifications/preferences',element: <RequireAuth>{load(<NotifPrefsPage />)}</RequireAuth> },
      { path: 'chat',                     element: <RequireAuth>{load(<ChatPage />)}</RequireAuth> },
      { path: 'chat/:peerId',             element: <RequireAuth>{load(<ChatPage />)}</RequireAuth> },
      { path: '@:username',               element: load(<ProfilePage />) },
    ],
  },
]);
