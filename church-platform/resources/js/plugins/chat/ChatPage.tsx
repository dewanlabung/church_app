import { useState, useEffect, useRef } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useAuthStore } from '@/stores/authStore';
import { api } from '@/hooks/useApi';

interface Conversation {
  peer_id: number;
  peer_name: string;
  peer_avatar: string | null;
  last_message: string;
  last_at: string;
  unread_count: number;
}

interface Message {
  id: number;
  sender_id: number;
  receiver_id: number;
  body: string;
  media: string | null;
  is_read: boolean;
  created_at: string;
}

export default function ChatPage() {
  const { user } = useAuthStore();
  const qc = useQueryClient();
  const [activePeer, setActivePeer] = useState<Conversation | null>(null);
  const [text, setText] = useState('');
  const messagesEndRef = useRef<HTMLDivElement>(null);

  const { data: convos = [] } = useQuery<Conversation[]>({
    queryKey: ['chat-conversations'],
    queryFn: () => api.get('/chat/conversations').then((r: any) => r.data ?? []),
    refetchInterval: 15_000,
  });

  const { data: messages = [], isLoading: msgsLoading } = useQuery<Message[]>({
    queryKey: ['chat-messages', activePeer?.peer_id],
    queryFn: () => api.get(`/chat/messages/${activePeer!.peer_id}`).then((r: any) => r.data ?? []),
    enabled: !!activePeer,
    refetchInterval: 5_000,
  });

  const sendMutation = useMutation({
    mutationFn: (body: string) => api.post(`/chat/messages/${activePeer!.peer_id}`, { body }),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['chat-messages', activePeer?.peer_id] });
      qc.invalidateQueries({ queryKey: ['chat-conversations'] });
      setText('');
    },
  });

  useEffect(() => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  }, [messages]);

  const handleSend = () => {
    const body = text.trim();
    if (!body || sendMutation.isPending) return;
    sendMutation.mutate(body);
  };

  const sortedMessages = [...messages].reverse();

  return (
    <div className="flex h-[calc(100vh-3.5rem)] bg-[var(--color-background)]">
      {/* Conversation list */}
      <div className={`w-full md:w-80 flex-shrink-0 border-r border-[var(--color-border)] bg-[var(--color-surface)] flex flex-col ${activePeer ? 'hidden md:flex' : 'flex'}`}>
        <div className="px-4 py-3 border-b border-[var(--color-border)]">
          <h2 className="font-semibold">Messages</h2>
        </div>
        <div className="overflow-y-auto flex-1">
          {(convos as Conversation[]).length === 0 ? (
            <div className="text-center py-16 text-[var(--color-muted)] text-sm">
              <p className="text-3xl mb-2">💬</p>
              <p>No conversations yet.</p>
            </div>
          ) : (
            (convos as Conversation[]).map((c) => (
              <button
                key={c.peer_id}
                onClick={() => setActivePeer(c)}
                className={`w-full flex items-center gap-3 px-4 py-3 hover:bg-[var(--color-surface-alt)] transition-colors text-left ${activePeer?.peer_id === c.peer_id ? 'bg-[var(--color-primary)]/10' : ''}`}
              >
                {c.peer_avatar
                  ? <img src={c.peer_avatar} className="w-10 h-10 rounded-full shrink-0 object-cover" alt={c.peer_name} />
                  : <div className="w-10 h-10 rounded-full bg-[var(--color-primary)] flex items-center justify-center text-white font-bold shrink-0">{c.peer_name?.[0]?.toUpperCase()}</div>
                }
                <div className="min-w-0 flex-1">
                  <div className="flex items-center justify-between">
                    <p className="font-medium text-sm truncate">{c.peer_name}</p>
                    {c.unread_count > 0 && (
                      <span className="text-[10px] bg-[var(--color-primary)] text-white rounded-full px-1.5 py-0.5 font-bold ml-1 shrink-0">{c.unread_count}</span>
                    )}
                  </div>
                  <p className="text-xs text-[var(--color-muted)] truncate">{c.last_message}</p>
                </div>
              </button>
            ))
          )}
        </div>
      </div>

      {/* Message thread */}
      <div className={`flex-1 flex flex-col ${activePeer ? 'flex' : 'hidden md:flex'}`}>
        {!activePeer ? (
          <div className="flex-1 flex items-center justify-center text-[var(--color-muted)]">
            <div className="text-center">
              <p className="text-4xl mb-3">💬</p>
              <p className="font-medium">Select a conversation</p>
            </div>
          </div>
        ) : (
          <>
            {/* Thread header */}
            <div className="flex items-center gap-3 px-4 py-3 border-b border-[var(--color-border)] bg-[var(--color-surface)]">
              <button onClick={() => setActivePeer(null)} className="md:hidden text-[var(--color-muted)] mr-1">←</button>
              {activePeer.peer_avatar
                ? <img src={activePeer.peer_avatar} className="w-8 h-8 rounded-full object-cover" alt={activePeer.peer_name} />
                : <div className="w-8 h-8 rounded-full bg-[var(--color-primary)] flex items-center justify-center text-white text-sm font-bold">{activePeer.peer_name?.[0]?.toUpperCase()}</div>
              }
              <p className="font-semibold text-sm">{activePeer.peer_name}</p>
            </div>

            {/* Messages */}
            <div className="flex-1 overflow-y-auto px-4 py-4 space-y-2">
              {msgsLoading ? (
                <div className="text-center text-[var(--color-muted)] text-sm py-8">Loading…</div>
              ) : (
                sortedMessages.map((msg) => {
                  const isMine = msg.sender_id === user?.id;
                  return (
                    <div key={msg.id} className={`flex ${isMine ? 'justify-end' : 'justify-start'}`}>
                      <div className={`max-w-xs lg:max-w-md px-3 py-2 rounded-2xl text-sm ${isMine ? 'bg-[var(--color-primary)] text-white' : 'bg-[var(--color-surface)] border border-[var(--color-border)]'}`}>
                        <p>{msg.body}</p>
                        <p className={`text-[10px] mt-0.5 ${isMine ? 'text-white/70' : 'text-[var(--color-muted)]'}`}>
                          {new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                        </p>
                      </div>
                    </div>
                  );
                })
              )}
              <div ref={messagesEndRef} />
            </div>

            {/* Input */}
            <div className="px-4 py-3 border-t border-[var(--color-border)] bg-[var(--color-surface)] flex gap-2">
              <input
                value={text}
                onChange={(e) => setText(e.target.value)}
                onKeyDown={(e) => e.key === 'Enter' && !e.shiftKey && (e.preventDefault(), handleSend())}
                placeholder="Type a message…"
                className="flex-1 px-3 py-2 rounded-xl border border-[var(--color-border)] bg-[var(--color-background)] text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]"
              />
              <button
                onClick={handleSend}
                disabled={!text.trim() || sendMutation.isPending}
                className="px-4 py-2 rounded-xl bg-[var(--color-primary)] text-white text-sm font-medium disabled:opacity-50 hover:opacity-90"
              >
                Send
              </button>
            </div>
          </>
        )}
      </div>
    </div>
  );
}
