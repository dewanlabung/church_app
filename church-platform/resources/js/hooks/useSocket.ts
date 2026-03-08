import { useEffect, useRef } from 'react';
import { useAuthStore } from '@/stores/authStore';

const SOCKET_URL = import.meta.env.VITE_SOCKET_URL as string | undefined;

// ── Module-level singleton ────────────────────────────────────────────────────

type EventHandler = (data: unknown) => void;

let _ws: WebSocket | null = null;
let _connected = false;
let _token: string | null = null;
let _reconnectTimer: ReturnType<typeof setTimeout> | null = null;
let _reconnectDelay = 2000; // starts at 2s, exponential up to 30s

// Simple event emitter keyed by message type
const _handlers = new Map<string, Set<EventHandler>>();

export function socketOn(event: string, handler: EventHandler): () => void {
  if (!_handlers.has(event)) _handlers.set(event, new Set());
  _handlers.get(event)!.add(handler);
  return () => _handlers.get(event)?.delete(handler);
}

function emit(event: string, data: unknown) {
  _handlers.get(event)?.forEach((fn) => fn(data));
}

function connect(token: string, url: string) {
  if (_ws && (_ws.readyState === WebSocket.OPEN || _ws.readyState === WebSocket.CONNECTING)) return;

  _token = token;
  _ws = new WebSocket(url);

  _ws.onopen = () => {
    // Server sends 'connected' prompt; auth is sent in onmessage after that
    _reconnectDelay = 2000; // reset backoff on success
  };

  _ws.onmessage = (event) => {
    let msg: { type: string; data?: unknown };
    try {
      msg = JSON.parse(event.data as string);
    } catch {
      return;
    }

    if (msg.type === 'connected') {
      // Authenticate immediately after server greeting
      _ws?.send(JSON.stringify({ type: 'auth', token: _token }));
    } else if (msg.type === 'auth_success') {
      _connected = true;
      emit('connect', null);
    } else if (msg.type === 'auth_failed') {
      _ws?.close();
    } else if (msg.type === 'ping') {
      _ws?.send(JSON.stringify({ type: 'pong' }));
    } else {
      // All other events dispatched to listeners
      emit(msg.type, msg.data ?? null);
    }
  };

  _ws.onclose = () => {
    _connected = false;
    emit('disconnect', null);

    if (_token) {
      // Exponential backoff reconnect
      _reconnectTimer = setTimeout(() => {
        if (_token) connect(_token, url);
      }, _reconnectDelay);
      _reconnectDelay = Math.min(_reconnectDelay * 2, 30_000);
    }
  };

  _ws.onerror = () => {
    _ws?.close();
  };
}

function disconnect() {
  _token = null;
  _connected = false;
  if (_reconnectTimer) {
    clearTimeout(_reconnectTimer);
    _reconnectTimer = null;
  }
  _ws?.close();
  _ws = null;
}

// ── Hook ─────────────────────────────────────────────────────────────────────

/**
 * Initialises the WebSocket connection for the authenticated user.
 * Connects on login, disconnects on logout.
 * Call once near the root of the app (AppBootstrap).
 */
export function useSocket(): { connected: boolean } {
  const token = useAuthStore((s) => s.token);
  const connectedRef = useRef(_connected);

  useEffect(() => {
    if (!token || !SOCKET_URL) return;

    connect(token, SOCKET_URL);

    const offConnect    = socketOn('connect',    () => { connectedRef.current = true; });
    const offDisconnect = socketOn('disconnect', () => { connectedRef.current = false; });

    return () => {
      offConnect();
      offDisconnect();
    };
  }, [token]);

  // Disconnect when user logs out
  useEffect(() => {
    if (!token) disconnect();
  }, [token]);

  return { connected: connectedRef.current };
}
