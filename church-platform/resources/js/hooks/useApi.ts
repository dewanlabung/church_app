import { useAuthStore } from '@/stores/authStore';
import { useThemeStore } from '@/stores/themeStore';
import { useSettingsStore } from '@/stores/settingsStore';
import { useQuery } from '@tanstack/react-query';

const API = import.meta.env.VITE_API_URL ?? '/api/v1';

function authHeaders() {
  const token = useAuthStore.getState().token;
  return {
    'Content-Type': 'application/json',
    ...(token ? { Authorization: `Bearer ${token}` } : {}),
  };
}

async function apiFetch<T>(path: string, init?: RequestInit): Promise<T> {
  const res = await fetch(`${API}${path}`, {
    ...init,
    headers: { ...authHeaders(), ...init?.headers },
  });
  if (!res.ok) {
    const err = await res.json().catch(() => ({ message: res.statusText }));
    throw Object.assign(new Error(err.message ?? 'Request failed'), { status: res.status, data: err });
  }
  return res.json();
}

export const api = {
  get: <T>(path: string) => apiFetch<T>(path),
  post: <T>(path: string, body?: unknown) =>
    apiFetch<T>(path, { method: 'POST', body: JSON.stringify(body) }),
  put: <T>(path: string, body?: unknown) =>
    apiFetch<T>(path, { method: 'PUT', body: JSON.stringify(body) }),
  delete: <T>(path: string) => apiFetch<T>(path, { method: 'DELETE' }),
  postForm: <T>(path: string, form: FormData) =>
    apiFetch<T>(path, {
      method: 'POST',
      body: form,
      headers: { Authorization: authHeaders().Authorization ?? '' },
    }),
};

// ── Auth ───────────────────────────────────────────────────────────────────

export const useMe = () =>
  useQuery({ queryKey: ['me'], queryFn: () => api.get<{ data: import('@/stores/authStore').AuthUser }>('/auth/me') });

export const useSettings = () =>
  useQuery({
    queryKey: ['app-settings'],
    queryFn: async () => {
      const data = await api.get<Record<string, unknown>>('/settings/general');
      useSettingsStore.getState().setSettings(data as any);
      return data;
    },
    staleTime: 1000 * 60 * 60, // 1 hour
  });

export const useThemeCss = () =>
  useQuery({
    queryKey: ['theme-css'],
    queryFn: async () => {
      const res = await fetch(`${API}/settings/theme/css`);
      const css = await res.text();
      useThemeStore.getState().setCssVars(css);
      return css;
    },
    staleTime: 1000 * 60 * 60,
  });

// ── Feed ───────────────────────────────────────────────────────────────────

export const feedKeys = {
  list: (filter: string) => ['feed', filter] as const,
};

// ── Verse ──────────────────────────────────────────────────────────────────

export const useTodayVerse = () =>
  useQuery({ queryKey: ['verse-today'], queryFn: () => api.get('/verses/today'), staleTime: 1000 * 60 * 30 });

// ── Greeting ──────────────────────────────────────────────────────────────

export const useGreeting = (enabled: boolean) =>
  useQuery({ queryKey: ['greeting'], queryFn: () => api.get('/greeting'), enabled });

// ── Notifications ──────────────────────────────────────────────────────────

export const useNotificationsQuery = (enabled: boolean) =>
  useQuery({
    queryKey: ['notifications'],
    queryFn: () => api.get('/notifications'),
    enabled,
    staleTime: 0,
  });
