import { Outlet } from 'react-router-dom';
import { useSettingsStore } from '@/stores/settingsStore';

export default function AuthLayout() {
  const settings = useSettingsStore((s) => s.settings);

  return (
    <div className="min-h-screen bg-[var(--color-background)] flex flex-col items-center justify-center p-4">
      <div className="w-full max-w-md">
        {/* Logo */}
        <div className="text-center mb-8">
          {settings?.logo_light ? (
            <img src={settings.logo_light} alt={settings.app_name} className="h-12 mx-auto" />
          ) : (
            <h1 className="text-3xl font-bold text-[var(--color-primary)]">
              {settings?.app_name ?? 'Church Platform'}
            </h1>
          )}
          {settings?.app_tagline && (
            <p className="text-sm text-[var(--color-muted)] mt-1">{settings.app_tagline}</p>
          )}
        </div>

        {/* Card */}
        <div className="bg-[var(--color-surface)] rounded-2xl shadow-lg p-8">
          <Outlet />
        </div>
      </div>
    </div>
  );
}
