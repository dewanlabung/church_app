import { useState } from 'react';
import { NavLink, useNavigate } from 'react-router-dom';
import { useRegister } from '@/hooks/useAuth';
import { useSettingsStore } from '@/stores/settingsStore';

export default function RegisterPage() {
  const settings = useSettingsStore((s) => s.settings);
  const [form, setForm] = useState({ name: '', email: '', password: '', password_confirmation: '' });
  const register = useRegister();
  const navigate = useNavigate();

  if (settings && !settings.registration_open) {
    return (
      <div className="text-center py-8">
        <p className="text-lg font-medium">Registration is currently closed.</p>
        <NavLink to="/login" className="text-[var(--color-primary)] mt-4 inline-block hover:underline">
          ← Back to login
        </NavLink>
      </div>
    );
  }

  const set = (k: keyof typeof form) => (e: React.ChangeEvent<HTMLInputElement>) =>
    setForm((f) => ({ ...f, [k]: e.target.value }));

  const submit = (e: React.FormEvent) => {
    e.preventDefault();
    register.mutate(form, {
      onSuccess: ({ token }) => {
        navigate(token ? '/' : '/login?verify=1');
      },
    });
  };

  return (
    <>
      <h2 className="text-2xl font-bold text-center mb-6">Create account</h2>
      <form onSubmit={submit} className="space-y-4">
        {(['name', 'email', 'password', 'password_confirmation'] as const).map((field) => (
          <div key={field}>
            <label className="block text-sm font-medium mb-1 capitalize">
              {field.replace('_', ' ')}
            </label>
            <input
              type={field.includes('password') ? 'password' : field === 'email' ? 'email' : 'text'}
              value={form[field]}
              onChange={set(field)}
              required
              className="w-full px-3 py-2 rounded-lg border border-[var(--color-border)] bg-[var(--color-background)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]"
            />
          </div>
        ))}

        {register.error && (
          <p className="text-red-500 text-sm">{(register.error as any)?.message ?? 'Registration failed.'}</p>
        )}

        <button
          type="submit" disabled={register.isPending}
          className="w-full py-2.5 rounded-lg bg-[var(--color-primary)] text-white font-semibold hover:opacity-90 disabled:opacity-50 transition-opacity"
        >
          {register.isPending ? 'Creating account…' : 'Create account'}
        </button>
      </form>

      <p className="text-center text-sm mt-6 text-[var(--color-muted)]">
        Already have an account?{' '}
        <NavLink to="/login" className="text-[var(--color-primary)] font-medium hover:underline">
          Sign in
        </NavLink>
      </p>
    </>
  );
}
