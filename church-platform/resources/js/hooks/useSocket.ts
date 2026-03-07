import { useEffect, useRef } from 'react';
import { io, Socket } from 'socket.io-client';
import { useAuthStore } from '@/stores/authStore';

const SOCKET_URL = import.meta.env.VITE_SOCKET_URL as string | undefined;

// Module-level singleton — one socket per browser session
let _socket: Socket | null = null;

function getSocket(token: string): Socket {
  if (_socket?.connected) return _socket;

  _socket = io(SOCKET_URL ?? 'ws://localhost:8080', {
    auth: { token },
    reconnectionDelay: 2000,
    reconnectionDelayMax: 30000,
    timeout: 10000,
    transports: ['websocket'],
  });

  // Authenticate after transport connects
  _socket.on('connect', () => {
    _socket?.emit('message', JSON.stringify({ type: 'auth', token }));
  });

  return _socket;
}

/**
 * Returns the shared socket instance for the authenticated user.
 * Connects on mount, disconnects when user logs out (token becomes null).
 */
export function useSocket(): { socket: Socket | null; connected: boolean } {
  const token = useAuthStore((s) => s.token);
  const connectedRef = useRef(false);

  useEffect(() => {
    if (!token || !SOCKET_URL) return;

    const sock = getSocket(token);
    connectedRef.current = sock.connected;

    const onConnect    = () => { connectedRef.current = true; };
    const onDisconnect = () => { connectedRef.current = false; };

    sock.on('connect', onConnect);
    sock.on('disconnect', onDisconnect);

    return () => {
      sock.off('connect', onConnect);
      sock.off('disconnect', onDisconnect);
    };
  }, [token]);

  // Disconnect and destroy socket when user logs out
  useEffect(() => {
    if (!token && _socket) {
      _socket.disconnect();
      _socket = null;
      connectedRef.current = false;
    }
  }, [token]);

  return { socket: _socket, connected: connectedRef.current };
}
