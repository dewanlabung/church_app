import { useRef, useState } from 'react';
import { useMutation } from '@tanstack/react-query';
import { useAuthStore } from '@/stores/authStore';
import { api } from '@/hooks/useApi';
import { useNavigate } from 'react-router-dom';

export default function ProfilePage() {
  const { user, updateUser, logout } = useAuthStore();
  const navigate = useNavigate();

  const [form, setForm] = useState({
    name: user?.name ?? '',
    bio: (user as any)?.bio ?? '',
    phone: (user as any)?.phone ?? '',
  });

  const [pwForm, setPwForm] = useState({ current_password: '', password: '', password_confirmation: '' });
  const [showPw, setShowPw] = useState(false);
  const [showDelete, setShowDelete] = useState(false);
  const [successMsg, setSuccessMsg] = useState('');
  const [errorMsg, setErrorMsg] = useState('');
  const fileRef = useRef<HTMLInputElement>(null);

  const profileMutation = useMutation({
    mutationFn: () => api.put('/auth/profile', form),
    onSuccess: (data: any) => {
      updateUser(data.user ?? data);
      setSuccessMsg('Profile updated.');
      setErrorMsg('');
      setTimeout(() => setSuccessMsg(''), 3000);
    },
    onError: (err: any) => setErrorMsg(err.message ?? 'Failed to update profile.'),
  });

  const avatarMutation = useMutation({
    mutationFn: (fd: FormData) => api.postForm('/auth/profile/avatar', fd),
    onSuccess: (data: any) => { updateUser(data.user ?? data); setSuccessMsg('Avatar updated.'); setTimeout(() => setSuccessMsg(''), 3000); },
    onError: (err: any) => setErrorMsg(err.message ?? 'Failed to upload avatar.'),
  });

  const pwMutation = useMutation({
    mutationFn: () => api.post('/auth/change-password', pwForm),
    onSuccess: () => {
      setPwForm({ current_password: '', password: '', password_confirmation: '' });
      setShowPw(false);
      setSuccessMsg('Password changed.');
      setTimeout(() => setSuccessMsg(''), 3000);
    },
    onError: (err: any) => setErrorMsg(err.message ?? 'Failed to change password.'),
  });

  const deleteMutation = useMutation({
    mutationFn: () => api.delete('/auth/account'),
    onSuccess: () => { logout(); navigate('/login'); },
  });

  const handleAvatarChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;
    const fd = new FormData();
    fd.append('avatar', file);
    avatarMutation.mutate(fd);
  };

  return (
    <div className="max-w-xl space-y-6">
      <h1 className="text-xl font-bold">My Profile</h1>

      {successMsg && <div className="bg-green-50 text-green-700 border border-green-200 rounded-lg px-4 py-3 text-sm">{successMsg}</div>}
      {errorMsg && <div className="bg-red-50 text-red-600 border border-red-200 rounded-lg px-4 py-3 text-sm">{errorMsg}</div>}

      {/* Avatar */}
      <div className="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-2xl p-5">
        <h2 className="font-semibold text-sm mb-4">Profile Photo</h2>
        <div className="flex items-center gap-4">
          <button onClick={() => fileRef.current?.click()} className="relative group">
            {user?.avatar
              ? <img src={user.avatar} className="w-20 h-20 rounded-full object-cover" alt={user.name} />
              : <div className="w-20 h-20 rounded-full bg-[var(--color-primary)] flex items-center justify-center text-white text-2xl font-bold">{user?.name?.[0]?.toUpperCase()}</div>
            }
            <div className="absolute inset-0 rounded-full bg-black/30 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity text-white text-xs font-medium">Change</div>
          </button>
          <div>
            <p className="text-sm font-medium">{user?.name}</p>
            <p className="text-xs text-[var(--color-muted)]">{user?.email}</p>
            <p className="text-xs text-[var(--color-muted)] mt-0.5 capitalize">{user?.user_type?.replace('_', ' ')}</p>
          </div>
        </div>
        <input ref={fileRef} type="file" accept="image/*" className="hidden" onChange={handleAvatarChange} />
        {avatarMutation.isPending && <p className="text-xs text-[var(--color-muted)] mt-2">Uploading...</p>}
      </div>

      {/* Edit profile */}
      <div className="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-2xl p-5 space-y-4">
        <h2 className="font-semibold text-sm">Edit Profile</h2>
        <div>
          <label className="block text-xs font-medium mb-1 text-[var(--color-muted)]">Name</label>
          <input value={form.name} onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))}
            className="w-full px-3 py-2 rounded-lg border border-[var(--color-border)] bg-[var(--color-background)] text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]" />
        </div>
        <div>
          <label className="block text-xs font-medium mb-1 text-[var(--color-muted)]">Bio</label>
          <textarea value={form.bio} onChange={(e) => setForm((f) => ({ ...f, bio: e.target.value }))} rows={3}
            placeholder="Tell the community about yourself..."
            className="w-full px-3 py-2 rounded-lg border border-[var(--color-border)] bg-[var(--color-background)] text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] resize-none" />
        </div>
        <div>
          <label className="block text-xs font-medium mb-1 text-[var(--color-muted)]">Phone</label>
          <input value={form.phone} onChange={(e) => setForm((f) => ({ ...f, phone: e.target.value }))}
            placeholder="+1 234 567 8900"
            className="w-full px-3 py-2 rounded-lg border border-[var(--color-border)] bg-[var(--color-background)] text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]" />
        </div>
        <div className="flex justify-end">
          <button onClick={() => profileMutation.mutate()} disabled={profileMutation.isPending}
            className="px-4 py-2 rounded-lg bg-[var(--color-primary)] text-white text-sm font-medium disabled:opacity-50 hover:opacity-90">
            {profileMutation.isPending ? 'Saving…' : 'Save Changes'}
          </button>
        </div>
      </div>

      {/* Change password */}
      <div className="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-2xl p-5">
        <button onClick={() => setShowPw((v) => !v)} className="flex items-center justify-between w-full">
          <h2 className="font-semibold text-sm">Change Password</h2>
          <span className="text-[var(--color-muted)] text-xs">{showPw ? '▲ Hide' : '▼ Show'}</span>
        </button>
        {showPw && (
          <div className="mt-4 space-y-3">
            {(['current_password', 'password', 'password_confirmation'] as const).map((field) => (
              <div key={field}>
                <label className="block text-xs font-medium mb-1 text-[var(--color-muted)] capitalize">{field.replace(/_/g, ' ')}</label>
                <input type="password" value={pwForm[field]} onChange={(e) => setPwForm((f) => ({ ...f, [field]: e.target.value }))}
                  className="w-full px-3 py-2 rounded-lg border border-[var(--color-border)] bg-[var(--color-background)] text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]" />
              </div>
            ))}
            <div className="flex justify-end">
              <button onClick={() => pwMutation.mutate()} disabled={pwMutation.isPending || !pwForm.current_password || !pwForm.password}
                className="px-4 py-2 rounded-lg bg-[var(--color-primary)] text-white text-sm font-medium disabled:opacity-50">
                {pwMutation.isPending ? 'Changing…' : 'Change Password'}
              </button>
            </div>
          </div>
        )}
      </div>

      {/* Danger zone */}
      <div className="bg-[var(--color-surface)] border border-red-200 rounded-2xl p-5">
        <h2 className="font-semibold text-sm text-red-600 mb-2">Danger Zone</h2>
        {!showDelete ? (
          <button onClick={() => setShowDelete(true)} className="text-sm text-red-500 hover:underline">Delete my account</button>
        ) : (
          <div className="space-y-3">
            <p className="text-sm text-[var(--color-muted)]">This action is permanent. Your posts, comments, and data will be deleted.</p>
            <div className="flex gap-2">
              <button onClick={() => setShowDelete(false)} className="px-3 py-1.5 text-sm rounded-lg border border-[var(--color-border)] hover:bg-[var(--color-surface-alt)]">Cancel</button>
              <button onClick={() => deleteMutation.mutate()} disabled={deleteMutation.isPending}
                className="px-4 py-1.5 text-sm rounded-lg bg-red-600 text-white font-medium disabled:opacity-50">
                {deleteMutation.isPending ? 'Deleting…' : 'Yes, delete account'}
              </button>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
