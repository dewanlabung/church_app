import React, { useEffect } from 'react';
import ReactDOM from 'react-dom/client';
import { RouterProvider } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { router } from '@/router';
import { useThemeStore } from '@/stores/themeStore';
import { useThemeCss, useSettings } from '@/hooks/useApi';
import './bootstrap';

// ── Query client ──────────────────────────────────────────────────────────

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 1000 * 60 * 5,      // 5 min default stale time
      retry: (count, err: any) => err?.status !== 401 && count < 2,
    },
  },
});

// ── App shell ─────────────────────────────────────────────────────────────

function AppBootstrap() {
  // Load theme CSS and app settings on mount
  useThemeCss();
  useSettings();

  const applyTheme = useThemeStore((s) => s.applyTheme);
  useEffect(() => {
    applyTheme();
    const mq = window.matchMedia('(prefers-color-scheme: dark)');
    mq.addEventListener('change', applyTheme);
    return () => mq.removeEventListener('change', applyTheme);
  }, [applyTheme]);

  return <RouterProvider router={router} />;
}

// ── Mount ─────────────────────────────────────────────────────────────────

const root = document.getElementById('app');
if (root) {
  ReactDOM.createRoot(root).render(
    <React.StrictMode>
      <QueryClientProvider client={queryClient}>
        <AppBootstrap />
      </QueryClientProvider>
    </React.StrictMode>
  );
}
