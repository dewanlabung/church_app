// Bootstrap: set CSRF token on all fetch requests via axios (optional)
// Using native fetch in this project — CSRF handled via Sanctum stateful cookies or Bearer token.
// This file is intentionally minimal.

import { useThemeStore } from '@/stores/themeStore';

// Apply saved theme immediately (before React renders) to avoid flash
try {
  const stored = JSON.parse(localStorage.getItem('church-theme') || '{}');
  const mode: string = stored?.state?.colorMode ?? 'system';
  const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
  const isDark = mode === 'dark' || (mode === 'system' && prefersDark);
  document.documentElement.setAttribute('data-theme', isDark ? 'dark' : 'light');
} catch {}
