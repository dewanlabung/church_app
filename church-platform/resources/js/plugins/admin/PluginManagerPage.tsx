import { useRef, useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { api } from '@/hooks/useApi';

interface Plugin {
  slug: string;
  name: string;
  version: string;
  description: string;
  author: string;
  category: string;
  enabled: boolean;
  can_disable: boolean;
  can_remove: boolean;
}

const CATEGORY_ORDER = ['Core', 'Social', 'Church', 'Content', 'Admin', 'Utility'];

export default function PluginManagerPage() {
  const qc = useQueryClient();
  const [feedback, setFeedback] = useState<{ type: 'ok' | 'err'; msg: string } | null>(null);
  const fileRef = useRef<HTMLInputElement>(null);

  const { data, isLoading } = useQuery<{ plugins: Plugin[] }>({
    queryKey: ['admin-plugins'],
    queryFn: () => api.get('/admin/plugins'),
  });

  const flash = (type: 'ok' | 'err', msg: string) => {
    setFeedback({ type, msg });
    setTimeout(() => setFeedback(null), 4000);
  };

  const enableMutation = useMutation({
    mutationFn: (slug: string) => api.post(`/admin/plugins/${slug}/enable`),
    onSuccess: (_, slug) => { qc.invalidateQueries({ queryKey: ['admin-plugins'] }); flash('ok', `${slug} enabled.`); },
    onError: (e: any) => flash('err', e.message ?? 'Failed'),
  });

  const disableMutation = useMutation({
    mutationFn: (slug: string) => api.post(`/admin/plugins/${slug}/disable`),
    onSuccess: (_, slug) => { qc.invalidateQueries({ queryKey: ['admin-plugins'] }); flash('ok', `${slug} disabled.`); },
    onError: (e: any) => flash('err', e.message ?? 'Failed'),
  });

  const removeMutation = useMutation({
    mutationFn: (slug: string) => api.delete(`/admin/plugins/${slug}`),
    onSuccess: (_, slug) => { qc.invalidateQueries({ queryKey: ['admin-plugins'] }); flash('ok', `${slug} removed.`); },
    onError: (e: any) => flash('err', e.message ?? 'Failed'),
  });

  const installMutation = useMutation({
    mutationFn: (fd: FormData) => api.postForm('/admin/plugins/install', fd),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['admin-plugins'] }); flash('ok', 'Plugin installed. Enable it below.'); },
    onError: (e: any) => flash('err', e.message ?? 'Install failed'),
  });

  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;
    const fd = new FormData();
    fd.append('plugin', file);
    installMutation.mutate(fd);
    e.target.value = '';
  };

  const plugins = data?.plugins ?? [];
  const grouped: Record<string, Plugin[]> = {};
  for (const p of plugins) {
    const cat = p.category || 'Other';
    if (!grouped[cat]) grouped[cat] = [];
    grouped[cat].push(p);
  }
  const sortedCategories = [...CATEGORY_ORDER.filter((c) => grouped[c]), ...Object.keys(grouped).filter((c) => !CATEGORY_ORDER.includes(c))];

  const isBusy = (slug: string) =>
    (enableMutation.isPending && enableMutation.variables === slug) ||
    (disableMutation.isPending && disableMutation.variables === slug);

  return (
    <div className="space-y-5">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold">Plugin Manager</h1>
        <button onClick={() => fileRef.current?.click()} disabled={installMutation.isPending}
          className="px-3 py-1.5 rounded-lg bg-[var(--color-primary)] text-white text-sm font-medium hover:opacity-90 disabled:opacity-50">
          {installMutation.isPending ? 'Installing…' : '+ Install Plugin'}
        </button>
        <input ref={fileRef} type="file" accept=".zip" className="hidden" onChange={handleFileChange} />
      </div>

      {feedback && (
        <div className={`rounded-lg px-4 py-3 text-sm ${feedback.type === 'ok' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-600 border border-red-200'}`}>
          {feedback.msg}
        </div>
      )}

      {isLoading ? (
        <div className="space-y-3">{[1,2,3].map((i) => (
          <div key={i} className="bg-[var(--color-surface)] rounded-2xl p-4 animate-pulse h-16" />
        ))}</div>
      ) : (
        sortedCategories.map((category) => (
          <div key={category} className="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-2xl overflow-hidden">
            <div className="px-5 py-2.5 bg-[var(--color-surface-alt)] border-b border-[var(--color-border)]">
              <span className="text-xs font-semibold uppercase tracking-wider text-[var(--color-muted)]">{category}</span>
            </div>
            <div className="divide-y divide-[var(--color-border)]">
              {grouped[category].map((plugin) => (
                <div key={plugin.slug} className="flex items-center gap-4 px-5 py-4">
                  <div className="flex-1 min-w-0">
                    <div className="flex items-center gap-2">
                      <span className="text-sm font-semibold">{plugin.name}</span>
                      <span className="text-[10px] text-[var(--color-muted)] bg-[var(--color-surface-alt)] px-1.5 py-0.5 rounded">v{plugin.version}</span>
                      {!plugin.can_disable && (
                        <span className="text-[10px] text-blue-600 bg-blue-50 px-1.5 py-0.5 rounded font-medium">Core</span>
                      )}
                    </div>
                    {plugin.description && <p className="text-xs text-[var(--color-muted)] mt-0.5 truncate">{plugin.description}</p>}
                  </div>

                  <div className="flex items-center gap-3 shrink-0">
                    {!plugin.enabled && plugin.can_remove && (
                      <button onClick={() => { if (confirm(`Remove plugin "${plugin.name}"? This cannot be undone.`)) removeMutation.mutate(plugin.slug); }}
                        className="text-xs text-red-500 hover:underline">Remove</button>
                    )}
                    {/* Toggle switch */}
                    <button
                      onClick={() => plugin.enabled ? disableMutation.mutate(plugin.slug) : enableMutation.mutate(plugin.slug)}
                      disabled={!plugin.can_disable || isBusy(plugin.slug)}
                      className={`relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none ${plugin.enabled ? 'bg-[var(--color-primary)]' : 'bg-gray-300'} ${!plugin.can_disable ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'}`}
                      title={!plugin.can_disable ? 'Core plugin — cannot be disabled' : plugin.enabled ? 'Disable' : 'Enable'}
                    >
                      <span className={`inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform ${plugin.enabled ? 'translate-x-6' : 'translate-x-1'}`} />
                    </button>
                  </div>
                </div>
              ))}
            </div>
          </div>
        ))
      )}
    </div>
  );
}
