import { create } from 'zustand';
import { persist } from 'zustand/middleware';

export interface AuthUser {
  id: number;
  name: string;
  email: string;
  avatar: string | null;
  bio: string | null;
  user_type: 'super_admin' | 'church_admin' | 'counsellor' | 'musician' | 'general_user';
  roles: string[];
  permissions: string[];
}

interface AuthState {
  user: AuthUser | null;
  token: string | null;
  isAuthenticated: boolean;
  setAuth: (user: AuthUser, token: string) => void;
  updateUser: (user: Partial<AuthUser>) => void;
  logout: () => void;
  hasRole: (role: string | string[]) => boolean;
  hasPermission: (perm: string) => boolean;
}

export const useAuthStore = create<AuthState>()(
  persist(
    (set, get) => ({
      user: null,
      token: null,
      isAuthenticated: false,

      setAuth: (user, token) =>
        set({ user, token, isAuthenticated: true }),

      updateUser: (partial) =>
        set((state) => ({
          user: state.user ? { ...state.user, ...partial } : null,
        })),

      logout: () => set({ user: null, token: null, isAuthenticated: false }),

      hasRole: (role) => {
        const { user } = get();
        if (!user) return false;
        const roles = Array.isArray(role) ? role : [role];
        return roles.some((r) => user.roles.includes(r));
      },

      hasPermission: (perm) => {
        const { user } = get();
        if (!user) return false;
        if (user.roles.includes('super_admin')) return true;
        return user.permissions.includes(perm);
      },
    }),
    {
      name: 'church-auth',
      partialize: (state) => ({ user: state.user, token: state.token, isAuthenticated: state.isAuthenticated }),
    }
  )
);
