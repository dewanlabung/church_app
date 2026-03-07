import { useRef } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { api } from '@/hooks/useApi';
import { useState } from 'react';

interface Theme {
  slug: string;
  name: string;
  version: string;
  description: string;
  author: string;
  active: boolean;
  screenshot: string | null;
  colors?: Record<string, string>;
}

export default function ThemesPage() {
  const qc = useQueryClient();
  const fileRef = useRef<HTMLInputElement>(null);
  const [feedback, setFeedback] = useState<{ type: 'ok' | 'err'; msg: string } | null>(null);

  const flash = (type: 'ok' | 'err', msg: string) => {
    setFeedback({ type, msg });
    setTimeout(() => setFeedback(null), 4000);
  };

  const { data, isLoading } = useQuery<{ themes: Theme[] }>({
    queryKey: ['admin-themes'],
    queryFn: () => api.get('/admin/themes'),
  });

  const activateMutation = useMutation({
    mutationFn: (slug: string) => api.post('/admin/themes/activate', { slug }),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['admin-themes'] }); flash('ok', 'Theme activated.'); },
    onError: (e: any) => flash('err', e.message ?? 'Failed'),
  });

  const removeMutation = useMutation({
    mutationFn: (slug: string) => api.delete(`/admin/themes/${slug}`),
    onSuccess: (_, slug) => { qc.invalidateQueries({ queryKey: ['admin-themes'] }); flash('ok', `Theme "${slug}" removed.`); },
    onError: (e: any) => flash('err', e.message ?? 'Cannot remove active/default theme'),
  });

  const installMutation = useMutation({
    mutationFn: (fd: FormData) => api.postForm('/admin/themes/install', fd),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['admin-themes'] }); flash('ok', 'Theme installed.'); },
    onError: (e: any) => flash('err', e.message ?? 'Install failed'),
  });

  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;
    const fd = new FormData();
    fd.append('theme', file);
    installMutation.mutate(fd);
    e.target.value = '';
  };

  const themes = data?.themes ?? [];

  return (
    <div className="space-y-5">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold">Themes</h1>
        <button onClick={() => fileRef.current?.click()} disabled={installMutation.isPending}
          className="px-3 py-1.5 rounded-lg bg-[var(--color-primary)] text-white text-sm font-medium hover:opacity-90 disabled:opacity-50">
          {installMutation.isPending ? 'Installing…' : '+ Install Theme'}
        </button>
        <input ref={fileRef} type="file" accept=".zip" className="hidden" onChange={handleFileChange} />
      </div>

      {feedback && (
        <div className={`rounded-lg px-4 py-3 text-sm ${feedback.type === 'ok' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-600 border border-red-200'}`}>
          {feedback.msg}
        </div>
      )}

      {isLoading ? (
        <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
          {[1,2,3].map((i) => <div key={i} className="bg-[var(--color-surface)] rounded-2xl overflow-hidden animate-pulse"><div className="h-36 bg-[var(--color-surface-alt)]" /><div className="p-4 space-y-2"><div className="h-3 w-24 bg-[var(--color-surface-alt)] rounded" /><div className="h-2 w-32 bg-[var(--color-surface-alt)] rounded" /></div></div>)}
        </div>
      ) : themes.length === 0 ? (
        <div className="text-center py-16 text-[var(--color-muted)]">
          <p className="text-4xl mb-3">🎨</p>
          <p className="font-medium">No themes installed.</p>
        </div>
      ) : (
        <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
          {themes.map((theme) => (
            <div key={theme.slug} className={`bg-[var(--color-surface)] border rounded-2xl overflow-hidden ${theme.active ? 'border-[var(--color-primary)] ring-2 ring-[var(--color-primary)]/20' : 'border-[var(--color-border)]'}`}>
              {/* Screenshot / color preview */}
              <div className="h-36 relative overflow-hidden bg-gradient-to-br from-[var(--color-primary)] to-[var(--color-accent)]">
                {theme.screenshot
                  ? <img src={theme.screenshot} className="w-full h-full object-cover" alt={theme.name} />
                  : (
                    <div className="w-full h-full flex items-center justify-center">
                      <div className="flex gap-2">
                        {Object.values(theme.colors ?? {}).slice(0, 4).map((color, i) => (
                          <div key={i} className="w-8 h-8 rounded-full border-2 border-white/50 shadow" style={{ backgroundColor: color }} />
                        ))}
                      </div>
                    </div>
                  )
                }
                {theme.active && (
                  <div className="absolute top-2 right-2 bg-[var(--color-primary)] text-white text-[10px] font-bold px-2 py-0.5 rounded-full">Active</div>
                )}
              </div>

              <div className="p-4 space-y-3">
                <div>
                  <div className="flex items-center gap-2">
                    <h3 className="font-semibold text-sm">{theme.name}</h3>
                    <span className="text-[10px] text-[var(--color-muted)] bg-[var(--color-surface-alt)] px-1.5 py-0.5 rounded">v{theme.version}</span>
                  </div>
                  {theme.description && <p className="text-xs text-[var(--color-muted)] mt-0.5 line-clamp-2">{theme.description}</p>}
                  {theme.author && <p className="text-[10px] text-[var(--color-muted)] mt-0.5">by {theme.author}</p>}
                </div>
                <div className="flex gap-2">
                  {!theme.active && (
                    <button onClick={() => activateMutation.mutate(theme.slug)}
                      disabled={activateMutation.isPending && activateMutation.variables === theme.slug}
                      className="flex-1 py-1.5 text-xs font-medium rounded-lg bg-[var(--color-primary)] text-white hover:opacity-90 disabled:opacity-50">
                      Activate
                    </button>
                  )}
                  {theme.active && (
                    <div className="flex-1 py-1.5 text-xs font-medium rounded-lg text-center text-green-600 bg-green-50">✓ Active</div>
                  )}
                  {!theme.active && (
                    <button onClick={() => { if (confirm(`Remove theme "${theme.name}"?`)) removeMutation.mutate(theme.slug); }}
                      className="px-3 py-1.5 text-xs rounded-lg border border-[var(--color-border)] text-red-500 hover:bg-red-50 hover:border-red-200">
                      Remove
                    </button>
                  )}
                </div>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
