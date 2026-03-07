import { useEffect } from 'react';
import { create } from 'zustand';
import { useSocket } from './useSocket';

// ── Notification store ────────────────────────────────────────────────────────

interface NotificationPayload {
  id: number;
  type: string;
  message: string;
  data: Record<string, unknown>;
}

interface NotificationStore {
  unread: number;
  latest: NotificationPayload | null;
  increment: () => void;
  reset: () => void;
  setLatest: (n: NotificationPayload) => void;
}

export const useNotificationStore = create<NotificationStore>((set) => ({
  unread: 0,
  latest: null,
  increment: () => set((s) => ({ unread: s.unread + 1 })),
  reset:     () => set({ unread: 0 }),
  setLatest: (n) => set({ latest: n }),
}));

// ── Hook ──────────────────────────────────────────────────────────────────────

/**
 * Subscribes to incoming 'notification' events from the Workerman socket server
 * and updates the notification badge count in Zustand.
 *
 * Mount this once near the root of the authenticated UI (e.g. ShellLayout).
 */
export function useNotifications(): void {
  const { socket } = useSocket();
  const increment  = useNotificationStore((s) => s.increment);
  const setLatest  = useNotificationStore((s) => s.setLatest);

  useEffect(() => {
    if (!socket) return;

    function onMessage(raw: string) {
      try {
        const msg = JSON.parse(raw) as { type: string; data?: NotificationPayload };
        if (msg.type !== 'notification' || !msg.data) return;

        increment();
        setLatest(msg.data);
      } catch {
        // ignore malformed messages
      }
    }

    socket.on('message', onMessage);
    return () => { socket.off('message', onMessage); };
  }, [socket, increment, setLatest]);
}
