import { useMutation, useQueryClient } from '@tanstack/react-query';
import { useNavigate } from 'react-router-dom';
import { api } from '@/hooks/useApi';
import { useAuthStore } from '@/stores/authStore';

export function useLogin() {
  const { setAuth } = useAuthStore();
  const qc = useQueryClient();

  return useMutation({
    mutationFn: (body: { email: string; password: string }) =>
      api.post<{ token: string; user: any }>('/auth/login', body),
    onSuccess: ({ token, user }) => {
      setAuth(user, token);
      qc.invalidateQueries({ queryKey: ['me'] });
    },
  });
}

export function useRegister() {
  const { setAuth } = useAuthStore();

  return useMutation({
    mutationFn: (body: { name: string; email: string; password: string; password_confirmation: string }) =>
      api.post<{ token: string; user: any }>('/auth/register', body),
    onSuccess: ({ token, user }) => {
      if (token) setAuth(user, token);
    },
  });
}

export function useLogout() {
  const { logout } = useAuthStore();
  const navigate = useNavigate();
  const qc = useQueryClient();

  return useMutation({
    mutationFn: () => api.post('/auth/logout'),
    onSettled: () => {
      logout();
      qc.clear();
      navigate('/login');
    },
  });
}

export function useUpdateProfile() {
  const { updateUser } = useAuthStore();
  const qc = useQueryClient();

  return useMutation({
    mutationFn: (body: Record<string, unknown>) => api.put<{ data: any }>('/auth/profile', body),
    onSuccess: ({ data }) => {
      updateUser(data);
      qc.invalidateQueries({ queryKey: ['me'] });
    },
  });
}
