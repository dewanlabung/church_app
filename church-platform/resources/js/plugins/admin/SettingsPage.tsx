import { useState, useEffect } from 'react';
import { useQuery, useMutation } from '@tanstack/react-query';
import { NavLink, useParams, Outlet, Routes, Route, Navigate } from 'react-router-dom';
import { api } from '@/hooks/useApi';

// ─── shared helpers ──────────────────────────────────────────────────────────

function useSettings(namespace?: string) {
  const key = namespace ?? 'general';
  const q = useQuery<Record<string, any>>({
    queryKey: ['settings', key],
    queryFn: () => api.get(`/settings/${key}`),
  });
  const mut = useMutation({
    mutationFn: (vals: Record<string, any>) => api.put(`/settings/${key}`, vals),
  });
  return { q, mut };
}

function Input({ label, name, value, onChange, type = 'text', placeholder }: {
  label: string; name: string; value: string; onChange: (v: string) => void;
  type?: string; placeholder?: string;
}) {
  return (
    <div>
      <label className="block text-xs font-medium mb-1 text-[var(--color-muted)]">{label}</label>
      <input type={type} name={name} value={value} onChange={(e) => onChange(e.target.value)} placeholder={placeholder}
        className="w-full px-3 py-2 rounded-lg border border-[var(--color-border)] bg-[var(--color-background)] text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]" />
    </div>
  );
}

function Toggle({ label, desc, name, value, onChange }: {
  label: string; desc?: string; name: string; value: boolean; onChange: (v: boolean) => void;
}) {
  return (
    <div className="flex items-center justify-between py-2">
      <div>
        <p className="text-sm font-medium">{label}</p>
        {desc && <p className="text-xs text-[var(--color-muted)]">{desc}</p>}
      </div>
      <button onClick={() => onChange(!value)} name={name}
        className={`relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors ${value ? 'bg-[var(--color-primary)]' : 'bg-gray-300'}`}>
        <span className={`inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform ${value ? 'translate-x-6' : 'translate-x-1'}`} />
      </button>
    </div>
  );
}

function SaveBar({ onSave, saving, saved }: { onSave: () => void; saving: boolean; saved: boolean }) {
  return (
    <div className="flex items-center justify-between pt-4 border-t border-[var(--color-border)]">
      {saved && <p className="text-xs text-green-600 font-medium">✓ Saved</p>}
      {!saved && <span />}
      <button onClick={onSave} disabled={saving}
        className="px-4 py-2 rounded-lg bg-[var(--color-primary)] text-white text-sm font-medium disabled:opacity-50">
        {saving ? 'Saving…' : 'Save Settings'}
      </button>
    </div>
  );
}

function SettingsCard({ title, children }: { title?: string; children: React.ReactNode }) {
  return (
    <div className="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-2xl p-5 space-y-4">
      {title && <h2 className="font-semibold text-sm">{title}</h2>}
      {children}
    </div>
  );
}

// ─── Tab sections ─────────────────────────────────────────────────────────────

function GeneralTab() {
  const { q, mut } = useSettings('general');
  const raw = q.data ?? {};
  const [v, setV] = useState<Record<string, string>>({});
  const [saved, setSaved] = useState(false);
  useEffect(() => { if (q.data) setV(q.data); }, [q.data]);
  const set = (k: string) => (val: string) => setV((p) => ({ ...p, [k]: val }));
  const save = () => mut.mutate(v, { onSuccess: () => { setSaved(true); setTimeout(() => setSaved(false), 3000); } });

  return (
    <SettingsCard title="General Settings">
      <Input label="App Name" name="app_name" value={v.app_name ?? ''} onChange={set('app_name')} />
      <Input label="App Tagline" name="app_tagline" value={v.app_tagline ?? ''} onChange={set('app_tagline')} placeholder="Connecting the church community" />
      <Input label="App URL" name="app_url" value={v.app_url ?? ''} onChange={set('app_url')} placeholder="https://yoursite.com" />
      <div className="grid grid-cols-2 gap-3">
        <Input label="Timezone" name="timezone" value={v.timezone ?? 'UTC'} onChange={set('timezone')} placeholder="UTC" />
        <Input label="Date Format" name="date_format" value={v.date_format ?? 'M j, Y'} onChange={set('date_format')} placeholder="M j, Y" />
      </div>
      <Input label="Logo (Light mode URL)" name="logo_light" value={v.logo_light ?? ''} onChange={set('logo_light')} />
      <Input label="Logo (Dark mode URL)" name="logo_dark" value={v.logo_dark ?? ''} onChange={set('logo_dark')} />
      <Input label="Favicon URL" name="favicon" value={v.favicon ?? ''} onChange={set('favicon')} />
      <SaveBar onSave={save} saving={mut.isPending} saved={saved} />
    </SettingsCard>
  );
}

function AppearanceTab() {
  const { q, mut } = useSettings('appearance');
  const [v, setV] = useState<Record<string, string>>({});
  const [saved, setSaved] = useState(false);
  useEffect(() => { if (q.data) setV(q.data); }, [q.data]);
  const set = (k: string) => (val: string) => setV((p) => ({ ...p, [k]: val }));
  const save = () => mut.mutate(v, { onSuccess: () => { setSaved(true); setTimeout(() => setSaved(false), 3000); } });

  const colors = [
    { key: 'color_primary', label: 'Primary Color' },
    { key: 'color_accent', label: 'Accent Color' },
    { key: 'color_background', label: 'Background Color' },
    { key: 'color_surface', label: 'Surface Color' },
  ];

  return (
    <div className="space-y-4">
      <SettingsCard title="Colors">
        <div className="grid grid-cols-2 gap-4">
          {colors.map(({ key, label }) => (
            <div key={key}>
              <label className="block text-xs font-medium mb-1 text-[var(--color-muted)]">{label}</label>
              <div className="flex items-center gap-2">
                <input type="color" value={v[key] ?? '#4F46E5'} onChange={(e) => set(key)(e.target.value)}
                  className="w-10 h-9 p-0.5 rounded border border-[var(--color-border)] cursor-pointer" />
                <input value={v[key] ?? '#4F46E5'} onChange={(e) => set(key)(e.target.value)}
                  className="flex-1 px-3 py-2 rounded-lg border border-[var(--color-border)] bg-[var(--color-background)] text-sm font-mono focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]" />
              </div>
            </div>
          ))}
        </div>
        <SaveBar onSave={save} saving={mut.isPending} saved={saved} />
      </SettingsCard>

      <SettingsCard title="Custom CSS">
        <div>
          <label className="block text-xs font-medium mb-1 text-[var(--color-muted)]">Custom CSS</label>
          <textarea value={v.custom_css ?? ''} onChange={(e) => set('custom_css')(e.target.value)} rows={8}
            placeholder="/* Add custom CSS overrides here */"
            className="w-full px-3 py-2 rounded-lg border border-[var(--color-border)] bg-[var(--color-background)] text-sm font-mono focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] resize-y" />
        </div>
        <SaveBar onSave={save} saving={mut.isPending} saved={saved} />
      </SettingsCard>
    </div>
  );
}

function AuthTab() {
  const { q, mut } = useSettings('auth');
  const [v, setV] = useState<Record<string, any>>({});
  const [saved, setSaved] = useState(false);
  useEffect(() => { if (q.data) setV(q.data); }, [q.data]);
  const setB = (k: string) => (val: boolean) => setV((p) => ({ ...p, [k]: val }));
  const save = () => mut.mutate(v, { onSuccess: () => { setSaved(true); setTimeout(() => setSaved(false), 3000); } });

  return (
    <SettingsCard title="Authentication Settings">
      <Toggle label="Registration Open" desc="Allow new users to register" name="auth_registration_open" value={!!v.auth_registration_open} onChange={setB('auth_registration_open')} />
      <Toggle label="Email Confirmation Required" desc="Users must verify email before login" name="auth_email_confirmation" value={!!v.auth_email_confirmation} onChange={setB('auth_email_confirmation')} />
      <Toggle label="Single Device Login" desc="Log out other sessions on login" name="auth_single_device_login" value={!!v.auth_single_device_login} onChange={setB('auth_single_device_login')} />
      <div>
        <label className="block text-xs font-medium mb-1 text-[var(--color-muted)]">Minimum Password Length</label>
        <input type="number" min={6} max={32} value={v.auth_password_min_length ?? 8}
          onChange={(e) => setV((p) => ({ ...p, auth_password_min_length: parseInt(e.target.value) }))}
          className="w-24 px-3 py-2 rounded-lg border border-[var(--color-border)] bg-[var(--color-background)] text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]" />
      </div>
      <SaveBar onSave={save} saving={mut.isPending} saved={saved} />
    </SettingsCard>
  );
}

function EmailTab() {
  const { q, mut } = useSettings('email');
  const [v, setV] = useState<Record<string, string>>({});
  const [saved, setSaved] = useState(false);
  useEffect(() => { if (q.data) setV(q.data); }, [q.data]);
  const set = (k: string) => (val: string) => setV((p) => ({ ...p, [k]: val }));
  const save = () => mut.mutate(v, { onSuccess: () => { setSaved(true); setTimeout(() => setSaved(false), 3000); } });

  return (
    <SettingsCard title="SMTP / Email Settings">
      <div className="grid grid-cols-2 gap-3">
        <div className="col-span-2">
          <Input label="SMTP Host" name="mail_host" value={v.mail_host ?? ''} onChange={set('mail_host')} placeholder="mail.yourdomain.com" />
        </div>
        <Input label="SMTP Port" name="mail_port" value={v.mail_port ?? '465'} onChange={set('mail_port')} placeholder="465" />
        <div>
          <label className="block text-xs font-medium mb-1 text-[var(--color-muted)]">Encryption</label>
          <select value={v.mail_encryption ?? 'ssl'} onChange={(e) => set('mail_encryption')(e.target.value)}
            className="w-full px-3 py-2 rounded-lg border border-[var(--color-border)] bg-[var(--color-background)] text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
            <option value="ssl">SSL</option><option value="tls">TLS</option><option value="">None</option>
          </select>
        </div>
        <Input label="SMTP Username" name="mail_username" value={v.mail_username ?? ''} onChange={set('mail_username')} />
        <Input label="SMTP Password" name="mail_password" type="password" value={v.mail_password ?? ''} onChange={set('mail_password')} />
        <Input label="From Name" name="mail_from_name" value={v.mail_from_name ?? ''} onChange={set('mail_from_name')} placeholder="Church Platform" />
        <Input label="From Email" name="mail_from_address" value={v.mail_from_address ?? ''} onChange={set('mail_from_address')} placeholder="noreply@yoursite.com" />
      </div>
      <SaveBar onSave={save} saving={mut.isPending} saved={saved} />
    </SettingsCard>
  );
}

function StorageTab() {
  const { q, mut } = useSettings('storage');
  const [v, setV] = useState<Record<string, string>>({});
  const [saved, setSaved] = useState(false);
  useEffect(() => { if (q.data) setV(q.data); }, [q.data]);
  const set = (k: string) => (val: string) => setV((p) => ({ ...p, [k]: val }));
  const save = () => mut.mutate(v, { onSuccess: () => { setSaved(true); setTimeout(() => setSaved(false), 3000); } });

  return (
    <div className="space-y-4">
      <SettingsCard title="Storage Driver">
        <div>
          <label className="block text-xs font-medium mb-1 text-[var(--color-muted)]">Driver</label>
          <select value={v.storage_driver ?? 'local'} onChange={(e) => set('storage_driver')(e.target.value)}
            className="w-full px-3 py-2 rounded-lg border border-[var(--color-border)] bg-[var(--color-background)] text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
            <option value="local">Local (cPanel)</option>
            <option value="s3">Amazon S3</option>
            <option value="r2">Cloudflare R2</option>
            <option value="do_spaces">DigitalOcean Spaces</option>
          </select>
        </div>
        {v.storage_driver && v.storage_driver !== 'local' && (
          <>
            <Input label="Bucket / Space Name" name="s3_bucket" value={v.s3_bucket ?? ''} onChange={set('s3_bucket')} />
            <Input label="Region" name="s3_region" value={v.s3_region ?? ''} onChange={set('s3_region')} placeholder="us-east-1" />
            <Input label="Access Key ID" name="s3_key" value={v.s3_key ?? ''} onChange={set('s3_key')} />
            <Input label="Secret Access Key" name="s3_secret" type="password" value={v.s3_secret ?? ''} onChange={set('s3_secret')} />
            <Input label="Custom Endpoint (optional)" name="s3_endpoint" value={v.s3_endpoint ?? ''} onChange={set('s3_endpoint')} placeholder="https://..." />
          </>
        )}
        <div className="grid grid-cols-2 gap-3">
          <Input label="Max Image Upload (MB)" name="max_upload_image" value={v.max_upload_image ?? '5'} onChange={set('max_upload_image')} />
          <Input label="Max Video Upload (MB)" name="max_upload_video" value={v.max_upload_video ?? '50'} onChange={set('max_upload_video')} />
        </div>
        <SaveBar onSave={save} saving={mut.isPending} saved={saved} />
      </SettingsCard>
    </div>
  );
}

function SeoTab() {
  const { q, mut } = useSettings('seo');
  const [v, setV] = useState<Record<string, string>>({});
  const [saved, setSaved] = useState(false);
  useEffect(() => { if (q.data) setV(q.data); }, [q.data]);
  const set = (k: string) => (val: string) => setV((p) => ({ ...p, [k]: val }));
  const save = () => mut.mutate(v, { onSuccess: () => { setSaved(true); setTimeout(() => setSaved(false), 3000); } });

  return (
    <SettingsCard title="SEO Settings">
      <Input label="Meta Title Template" name="meta_title_template" value={v.meta_title_template ?? '{page} | {app_name}'} onChange={set('meta_title_template')} placeholder="{page} | {app_name}" />
      <div>
        <label className="block text-xs font-medium mb-1 text-[var(--color-muted)]">Default Meta Description</label>
        <textarea value={v.meta_description ?? ''} onChange={(e) => set('meta_description')(e.target.value)} rows={3}
          placeholder="Describe your church community platform..."
          className="w-full px-3 py-2 rounded-lg border border-[var(--color-border)] bg-[var(--color-background)] text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] resize-none" />
      </div>
      <Input label="Default OG Image URL" name="og_image" value={v.og_image ?? ''} onChange={set('og_image')} />
      <div>
        <label className="block text-xs font-medium mb-1 text-[var(--color-muted)]">Robots.txt Content</label>
        <textarea value={v.robots_txt ?? 'User-agent: *\nAllow: /'} onChange={(e) => set('robots_txt')(e.target.value)} rows={5}
          className="w-full px-3 py-2 rounded-lg border border-[var(--color-border)] bg-[var(--color-background)] text-sm font-mono focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] resize-y" />
      </div>
      <SaveBar onSave={save} saving={mut.isPending} saved={saved} />
    </SettingsCard>
  );
}

function GdprTab() {
  const { q, mut } = useSettings('gdpr');
  const [v, setV] = useState<Record<string, any>>({});
  const [saved, setSaved] = useState(false);
  useEffect(() => { if (q.data) setV(q.data); }, [q.data]);
  const setB = (k: string) => (val: boolean) => setV((p) => ({ ...p, [k]: val }));
  const set = (k: string) => (val: string) => setV((p) => ({ ...p, [k]: val }));
  const save = () => mut.mutate(v, { onSuccess: () => { setSaved(true); setTimeout(() => setSaved(false), 3000); } });

  return (
    <SettingsCard title="GDPR / Privacy Settings">
      <Toggle label="Cookie Consent Banner" desc="Show cookie notice to visitors" name="cookie_banner_enabled" value={!!v.cookie_banner_enabled} onChange={setB('cookie_banner_enabled')} />
      <Toggle label="Allow Account Deletion" desc="Let users delete their own accounts" name="allow_account_deletion" value={!!v.allow_account_deletion} onChange={setB('allow_account_deletion')} />
      <Input label="Privacy Policy URL" name="privacy_policy_url" value={v.privacy_policy_url ?? ''} onChange={set('privacy_policy_url')} placeholder="https://yoursite.com/privacy" />
      <Input label="Terms of Service URL" name="terms_url" value={v.terms_url ?? ''} onChange={set('terms_url')} placeholder="https://yoursite.com/terms" />
      <SaveBar onSave={save} saving={mut.isPending} saved={saved} />
    </SettingsCard>
  );
}

function SystemTab() {
  const [status, setStatus] = useState<Record<string, string>>({});

  const runCommand = async (label: string, endpoint: string) => {
    setStatus((s) => ({ ...s, [label]: 'running' }));
    try {
      await api.post(endpoint);
      setStatus((s) => ({ ...s, [label]: 'done' }));
      setTimeout(() => setStatus((s) => ({ ...s, [label]: '' })), 4000);
    } catch {
      setStatus((s) => ({ ...s, [label]: 'error' }));
    }
  };

  const actions = [
    { label: 'Clear Cache', endpoint: '/admin/system/cache-clear', icon: '🧹' },
    { label: 'Run Migrations', endpoint: '/admin/system/migrate', icon: '🗄️' },
    { label: 'Optimize', endpoint: '/admin/system/optimize', icon: '⚡' },
  ];

  return (
    <div className="space-y-4">
      <SettingsCard title="System Actions">
        <div className="space-y-3">
          {actions.map(({ label, endpoint, icon }) => (
            <div key={label} className="flex items-center justify-between py-2 border-b border-[var(--color-border)] last:border-0">
              <div>
                <p className="text-sm font-medium">{icon} {label}</p>
              </div>
              <button onClick={() => runCommand(label, endpoint)}
                disabled={status[label] === 'running'}
                className={`px-4 py-1.5 text-xs rounded-lg font-medium disabled:opacity-50 transition-colors ${
                  status[label] === 'done' ? 'bg-green-100 text-green-700' :
                  status[label] === 'error' ? 'bg-red-100 text-red-600' :
                  'bg-[var(--color-primary)] text-white hover:opacity-90'
                }`}>
                {status[label] === 'running' ? 'Running…' : status[label] === 'done' ? '✓ Done' : status[label] === 'error' ? '✗ Failed' : 'Run'}
              </button>
            </div>
          ))}
        </div>
      </SettingsCard>

      <SettingsCard title="Environment Info">
        <div className="grid grid-cols-2 gap-2 text-sm">
          {[
            ['PHP Version', (window as any).__PHP_VERSION__ ?? '—'],
            ['Laravel', (window as any).__LARAVEL_VERSION__ ?? '—'],
            ['Queue Driver', 'database'],
            ['Cache Driver', 'file'],
            ['Broadcast Driver', 'workerman'],
            ['Session Driver', 'file'],
          ].map(([k, v]) => (
            <div key={k} className="flex gap-2">
              <span className="text-[var(--color-muted)] min-w-0">{k}:</span>
              <span className="font-mono font-medium">{v}</span>
            </div>
          ))}
        </div>
      </SettingsCard>
    </div>
  );
}

// ─── Tabs definition ──────────────────────────────────────────────────────────

const TABS = [
  { slug: 'general',    label: '⚙️  General',    component: GeneralTab },
  { slug: 'appearance', label: '🎨  Appearance',  component: AppearanceTab },
  { slug: 'auth',       label: '🔐  Auth',         component: AuthTab },
  { slug: 'email',      label: '📧  Email',        component: EmailTab },
  { slug: 'storage',    label: '💾  Storage',      component: StorageTab },
  { slug: 'seo',        label: '🔍  SEO',          component: SeoTab },
  { slug: 'gdpr',       label: '🛡️  GDPR',         component: GdprTab },
  { slug: 'system',     label: '🖥️  System',       component: SystemTab },
];

// ─── Root ─────────────────────────────────────────────────────────────────────

export default function SettingsPage() {
  const { tab } = useParams<{ tab: string }>();
  const current = TABS.find((t) => t.slug === tab) ?? TABS[0];
  const ActiveTab = current.component;

  return (
    <div className="flex gap-5 items-start">
      {/* Sidebar */}
      <nav className="w-44 shrink-0 bg-[var(--color-surface)] border border-[var(--color-border)] rounded-2xl overflow-hidden">
        {TABS.map((t) => (
          <NavLink key={t.slug} to={`/admin/settings/${t.slug}`}
            className={({ isActive }) =>
              `flex items-center gap-2 px-4 py-2.5 text-sm transition-colors border-l-2 ${isActive ? 'border-[var(--color-primary)] bg-[var(--color-primary)]/5 text-[var(--color-primary)] font-medium' : 'border-transparent hover:bg-[var(--color-surface-alt)] text-[var(--color-muted)]'}`
            }>
            {t.label}
          </NavLink>
        ))}
      </nav>

      {/* Content */}
      <div className="flex-1 min-w-0">
        <ActiveTab />
      </div>
    </div>
  );
}
