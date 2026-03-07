import { useState } from 'react';
import { NavLink } from 'react-router-dom';
import { useLogin } from '@/hooks/useAuth';
import { useNavigate } from 'react-router-dom';

export default function LoginPage() {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const login = useLogin();
  const navigate = useNavigate();

  const submit = (e: React.FormEvent) => {
    e.preventDefault();
    login.mutate({ email, password }, { onSuccess: () => navigate('/') });
  };

  return (
    <>
      <h2 className="text-2xl font-bold text-center mb-6">Welcome back</h2>
      <form onSubmit={submit} className="space-y-4">
        <div>
          <label className="block text-sm font-medium mb-1">Email</label>
          <input
            type="email" value={email} onChange={(e) => setEmail(e.target.value)}
            required autoFocus
            className="w-full px-3 py-2 rounded-lg border border-[var(--color-border)] bg-[var(--color-background)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]"
          />
        </div>
        <div>
          <label className="block text-sm font-medium mb-1">Password</label>
          <input
            type="password" value={password} onChange={(e) => setPassword(e.target.value)}
            required
            className="w-full px-3 py-2 rounded-lg border border-[var(--color-border)] bg-[var(--color-background)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]"
          />
        </div>

        {login.error && (
          <p className="text-red-500 text-sm">{(login.error as any)?.message ?? 'Login failed.'}</p>
        )}

        <button
          type="submit" disabled={login.isPending}
          className="w-full py-2.5 rounded-lg bg-[var(--color-primary)] text-white font-semibold hover:opacity-90 disabled:opacity-50 transition-opacity"
        >
          {login.isPending ? 'Signing in…' : 'Sign in'}
        </button>
      </form>

      <p className="text-center text-sm mt-6 text-[var(--color-muted)]">
        No account?{' '}
        <NavLink to="/register" className="text-[var(--color-primary)] font-medium hover:underline">
          Register
        </NavLink>
      </p>
    </>
  );
}
