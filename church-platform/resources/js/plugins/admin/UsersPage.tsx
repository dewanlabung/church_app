import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { api } from '@/hooks/useApi';

interface User {
  id: number;
  name: string;
  email: string;
  avatar: string | null;
  user_type: string;
  created_at: string;
  email_verified_at: string | null;
}

interface UsersResponse {
  data: User[];
  total: number;
  current_page: number;
  last_page: number;
  per_page: number;
}

const ROLES = ['general_user', 'musician', 'counsellor', 'church_admin', 'super_admin'];
const ROLE_COLORS: Record<string, string> = {
  super_admin: 'bg-purple-100 text-purple-700',
  church_admin: 'bg-blue-100 text-blue-700',
  counsellor: 'bg-teal-100 text-teal-700',
  musician: 'bg-yellow-100 text-yellow-700',
  general_user: 'bg-gray-100 text-gray-600',
};

export default function UsersPage() {
  const qc = useQueryClient();
  const [search, setSearch] = useState('');
  const [roleFilter, setRoleFilter] = useState('');
  const [page, setPage] = useState(1);
  const [feedback, setFeedback] = useState<{ type: 'ok' | 'err'; msg: string } | null>(null);
  const [confirmDelete, setConfirmDelete] = useState<User | null>(null);

  const flash = (type: 'ok' | 'err', msg: string) => {
    setFeedback({ type, msg });
    setTimeout(() => setFeedback(null), 4000);
  };

  const params = new URLSearchParams({ page: String(page), per_page: '20' });
  if (search) params.set('search', search);
  if (roleFilter) params.set('role', roleFilter);

  const { data, isLoading } = useQuery<UsersResponse>({
    queryKey: ['admin-users', page, search, roleFilter],
    queryFn: () => api.get(`/admin/users?${params.toString()}`),
    placeholderData: (prev) => prev,
  });

  const roleMutation = useMutation({
    mutationFn: ({ id, role }: { id: number; role: string }) =>
      api.put(`/admin/users/${id}/assign-role`, { role }),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['admin-users'] });
      flash('ok', 'Role updated.');
    },
    onError: (e: any) => flash('err', e.message ?? 'Failed to update role.'),
  });

  const deleteMutation = useMutation({
    mutationFn: (id: number) => api.delete(`/admin/users/${id}`),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['admin-users'] });
      setConfirmDelete(null);
      flash('ok', 'User deleted.');
    },
    onError: (e: any) => flash('err', e.message ?? 'Failed to delete user.'),
  });

  const users = data?.data ?? [];
  const totalPages = data?.last_page ?? 1;

  return (
    <div className="space-y-4">
      <h1 className="text-2xl font-bold">User Management</h1>

      {feedback && (
        <div className={`rounded-lg px-4 py-3 text-sm ${feedback.type === 'ok' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-600 border border-red-200'}`}>
          {feedback.msg}
        </div>
      )}

      {/* Filters */}
      <div className="flex flex-col sm:flex-row gap-2">
        <input
          value={search}
          onChange={(e) => { setSearch(e.target.value); setPage(1); }}
          placeholder="Search by name or email…"
          className="flex-1 px-3 py-2 rounded-lg border border-[var(--color-border)] bg-[var(--color-surface)] text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]"
        />
        <select
          value={roleFilter}
          onChange={(e) => { setRoleFilter(e.target.value); setPage(1); }}
          className="px-3 py-2 rounded-lg border border-[var(--color-border)] bg-[var(--color-surface)] text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]"
        >
          <option value="">All roles</option>
          {ROLES.map((r) => (
            <option key={r} value={r}>{r.replace(/_/g, ' ')}</option>
          ))}
        </select>
      </div>

      {/* Table */}
      <div className="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-2xl overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-[var(--color-border)] text-left text-xs text-[var(--color-muted)] bg-[var(--color-surface-alt)]">
                <th className="px-4 py-3 font-semibold">User</th>
                <th className="px-4 py-3 font-semibold">Role</th>
                <th className="px-4 py-3 font-semibold hidden sm:table-cell">Joined</th>
                <th className="px-4 py-3 font-semibold hidden sm:table-cell">Verified</th>
                <th className="px-4 py-3 font-semibold w-10"></th>
              </tr>
            </thead>
            <tbody className="divide-y divide-[var(--color-border)]">
              {isLoading ? (
                Array.from({ length: 5 }).map((_, i) => (
                  <tr key={i}>
                    <td className="px-4 py-3" colSpan={5}>
                      <div className="h-8 bg-[var(--color-surface-alt)] rounded animate-pulse" />
                    </td>
                  </tr>
                ))
              ) : users.length === 0 ? (
                <tr>
                  <td colSpan={5} className="text-center py-10 text-[var(--color-muted)]">No users found.</td>
                </tr>
              ) : users.map((user) => (
                <tr key={user.id} className="hover:bg-[var(--color-surface-alt)] transition-colors">
                  <td className="px-4 py-3">
                    <div className="flex items-center gap-3">
                      {user.avatar
                        ? <img src={user.avatar} className="w-8 h-8 rounded-full object-cover shrink-0" alt={user.name} />
                        : <div className="w-8 h-8 rounded-full bg-[var(--color-primary)] flex items-center justify-center text-white text-xs font-bold shrink-0">{user.name?.[0]?.toUpperCase()}</div>
                      }
                      <div className="min-w-0">
                        <p className="font-medium truncate">{user.name}</p>
                        <p className="text-xs text-[var(--color-muted)] truncate">{user.email}</p>
                      </div>
                    </div>
                  </td>
                  <td className="px-4 py-3">
                    <select
                      value={user.user_type}
                      onChange={(e) => roleMutation.mutate({ id: user.id, role: e.target.value })}
                      disabled={roleMutation.isPending && (roleMutation.variables as any)?.id === user.id}
                      className={`text-xs px-2 py-1 rounded-full font-medium border-0 focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] cursor-pointer ${ROLE_COLORS[user.user_type] ?? 'bg-gray-100 text-gray-600'}`}
                    >
                      {ROLES.map((r) => (
                        <option key={r} value={r}>{r.replace(/_/g, ' ')}</option>
                      ))}
                    </select>
                  </td>
                  <td className="px-4 py-3 text-xs text-[var(--color-muted)] hidden sm:table-cell">
                    {new Date(user.created_at).toLocaleDateString()}
                  </td>
                  <td className="px-4 py-3 hidden sm:table-cell">
                    {user.email_verified_at
                      ? <span className="text-xs text-green-600">✓ Verified</span>
                      : <span className="text-xs text-yellow-600">Pending</span>
                    }
                  </td>
                  <td className="px-4 py-3">
                    <button onClick={() => setConfirmDelete(user)}
                      className="text-xs text-red-400 hover:text-red-600 transition-colors">✕</button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>

        {/* Pagination */}
        {totalPages > 1 && (
          <div className="flex items-center justify-between px-4 py-3 border-t border-[var(--color-border)]">
            <p className="text-xs text-[var(--color-muted)]">
              Page {page} of {totalPages} · {data?.total ?? 0} users total
            </p>
            <div className="flex gap-1">
              <button onClick={() => setPage((p) => Math.max(1, p - 1))} disabled={page === 1}
                className="px-3 py-1 rounded-lg border border-[var(--color-border)] text-xs disabled:opacity-40 hover:bg-[var(--color-surface-alt)]">← Prev</button>
              {Array.from({ length: Math.min(5, totalPages) }, (_, i) => {
                const start = Math.max(1, Math.min(page - 2, totalPages - 4));
                const p = start + i;
                return (
                  <button key={p} onClick={() => setPage(p)}
                    className={`px-3 py-1 rounded-lg text-xs ${p === page ? 'bg-[var(--color-primary)] text-white' : 'border border-[var(--color-border)] hover:bg-[var(--color-surface-alt)]'}`}>
                    {p}
                  </button>
                );
              })}
              <button onClick={() => setPage((p) => Math.min(totalPages, p + 1))} disabled={page === totalPages}
                className="px-3 py-1 rounded-lg border border-[var(--color-border)] text-xs disabled:opacity-40 hover:bg-[var(--color-surface-alt)]">Next →</button>
            </div>
          </div>
        )}
      </div>

      {/* Delete confirm dialog */}
      {confirmDelete && (
        <div className="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">
          <div className="bg-[var(--color-surface)] rounded-2xl p-6 max-w-sm w-full shadow-xl space-y-4">
            <h3 className="font-semibold">Delete User</h3>
            <p className="text-sm text-[var(--color-muted)]">
              Delete <strong>{confirmDelete.name}</strong>? This will permanently remove their account and data.
            </p>
            <div className="flex gap-2 justify-end">
              <button onClick={() => setConfirmDelete(null)}
                className="px-4 py-2 text-sm rounded-lg border border-[var(--color-border)] hover:bg-[var(--color-surface-alt)]">Cancel</button>
              <button onClick={() => deleteMutation.mutate(confirmDelete.id)} disabled={deleteMutation.isPending}
                className="px-4 py-2 text-sm rounded-lg bg-red-600 text-white font-medium disabled:opacity-50">
                {deleteMutation.isPending ? 'Deleting…' : 'Delete'}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
