import { useQuery, useMutation } from '@tanstack/react-query';
import { useState } from 'react';
import { api } from '@/hooks/useApi';

interface TypeMeta {
  label: string;
  channels: string[];
}

interface PrefsResponse {
  preferences: Record<string, Record<string, boolean>>;
  types: Record<string, TypeMeta>;
}

const CHANNEL_LABELS: Record<string, string> = {
  in_app: 'In-App',
  email:  'Email',
  push:   'Push',
};

export default function NotificationPreferencesPage() {
  const [saved, setSaved] = useState(false);
  const [prefs, setPrefs] = useState<Record<string, Record<string, boolean>> | null>(null);
  // Sync prefs from server on first load
  const [synced, setSynced] = useState(false);

  const { data, isLoading } = useQuery<PrefsResponse>({
    queryKey: ['notif-prefs'],
    queryFn: () => api.get('/notifications/preferences'),
  });

  const mutation = useMutation({
    mutationFn: () => api.put('/notifications/preferences', { preferences: prefs }),
    onSuccess: () => { setSaved(true); setTimeout(() => setSaved(false), 3000); },
  });

  // Sync server data once into local state for editing
  if (data && !synced) {
    setPrefs(data.preferences);
    setSynced(true);
  }

  const types = data?.types ?? {};
  const currentPrefs = prefs ?? data?.preferences ?? {};

  const toggle = (type: string, channel: string) => {
    setPrefs((prev) => {
      const base = prev ?? data?.preferences ?? {};
      return {
        ...base,
        [type]: { ...(base[type] ?? {}), [channel]: !base[type]?.[channel] },
      };
    });
  };

  if (isLoading) {
    return (
      <div className="space-y-4">
        <h1 className="text-2xl font-bold">Notification Preferences</h1>
        {[1,2,3,4].map((i) => (
          <div key={i} className="bg-[var(--color-surface)] rounded-2xl p-4 h-16 animate-pulse" />
        ))}
      </div>
    );
  }

  return (
    <div className="space-y-5 max-w-2xl">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold">Notification Preferences</h1>
        <button
          onClick={() => mutation.mutate()}
          disabled={mutation.isPending}
          className="px-4 py-2 rounded-lg bg-[var(--color-primary)] text-white text-sm font-medium disabled:opacity-50 hover:opacity-90"
        >
          {mutation.isPending ? 'Saving…' : 'Save'}
        </button>
      </div>

      {saved && (
        <div className="rounded-lg px-4 py-3 text-sm bg-green-50 text-green-700 border border-green-200">
          Preferences saved successfully.
        </div>
      )}

      <div className="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-2xl overflow-hidden">
        {/* Header row */}
        <div className="flex items-center px-5 py-3 bg-[var(--color-surface-alt)] border-b border-[var(--color-border)]">
          <div className="flex-1 text-xs font-semibold uppercase tracking-wider text-[var(--color-muted)]">Notification Type</div>
          {['in_app', 'email', 'push'].map((ch) => (
            <div key={ch} className="w-16 text-center text-xs font-semibold uppercase tracking-wider text-[var(--color-muted)]">
              {CHANNEL_LABELS[ch]}
            </div>
          ))}
        </div>

        <div className="divide-y divide-[var(--color-border)]">
          {Object.entries(types).map(([type, meta]) => (
            <div key={type} className="flex items-center px-5 py-3">
              <div className="flex-1 text-sm font-medium">{meta.label}</div>
              {['in_app', 'email', 'push'].map((ch) => {
                const isAvailable = meta.channels.includes(ch);
                const enabled     = isAvailable ? (currentPrefs[type]?.[ch] ?? true) : false;
                return (
                  <div key={ch} className="w-16 flex justify-center">
                    {isAvailable ? (
                      <button
                        onClick={() => toggle(type, ch)}
                        className={`relative inline-flex h-5 w-9 items-center rounded-full transition-colors focus:outline-none ${enabled ? 'bg-[var(--color-primary)]' : 'bg-gray-300'}`}
                        aria-label={`${meta.label} ${ch}`}
                      >
                        <span className={`inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition-transform ${enabled ? 'translate-x-4' : 'translate-x-0.5'}`} />
                      </button>
                    ) : (
                      <span className="text-[var(--color-muted)] text-xs">—</span>
                    )}
                  </div>
                );
              })}
            </div>
          ))}
        </div>
      </div>

      <p className="text-xs text-[var(--color-muted)]">
        In-App notifications appear in the bell icon. Email and Push notifications require your email address and browser permission respectively.
      </p>
    </div>
  );
}
