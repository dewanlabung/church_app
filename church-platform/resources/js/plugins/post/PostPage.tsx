import { useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { api } from '@/hooks/useApi';
import { useAuthStore } from '@/stores/authStore';

interface Post {
  id: number; user_id: number; name: string; avatar: string | null;
  type: string; body: string; reactions_count: number;
  comments_count: number; created_at: string; user_reaction: string | null;
}
interface Comment {
  id: number; user_id: number; name: string; avatar: string | null;
  body: string; created_at: string; replies?: Comment[];
}
const REACTIONS = [
  { key: 'like', emoji: '👍', label: 'Like' },
  { key: 'bless', emoji: '👐', label: 'Bless' },
  { key: 'amen', emoji: '🙏', label: 'Amen' },
  { key: 'pray', emoji: '💛', label: 'Pray' },
  { key: 'love', emoji: '❤️', label: 'Love' },
];

export default function PostPage() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const { isAuthenticated, user } = useAuthStore();
  const qc = useQueryClient();
  const [commentBody, setCommentBody] = useState('');

  const { data: post, isLoading } = useQuery<Post>({
    queryKey: ['post', id],
    queryFn: () => api.get(`/posts/${id}`),
    enabled: !!id,
  });
  const { data: comments = [] } = useQuery<Comment[]>({
    queryKey: ['post-comments', id],
    queryFn: () => api.get(`/posts/${id}/comments`),
    enabled: !!id,
  });
  const reactMutation = useMutation({
    mutationFn: (reaction: string) => api.post(`/posts/${id}/react`, { reaction }),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['post', id] }),
  });
  const commentMutation = useMutation({
    mutationFn: (body: string) => api.post(`/posts/${id}/comments`, { body }),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['post-comments', id] });
      qc.invalidateQueries({ queryKey: ['post', id] });
      setCommentBody('');
    },
  });
  const deleteMutation = useMutation({
    mutationFn: () => api.delete(`/posts/${id}`),
    onSuccess: () => navigate(-1),
  });

  if (isLoading) return <PostSkeleton />;
  if (!post) return <div className="p-8 text-center text-[var(--color-muted)]">Post not found.</div>;

  return (
    <div className="space-y-4 max-w-2xl">
      <button onClick={() => navigate(-1)} className="text-sm text-[var(--color-muted)] hover:text-[var(--color-primary)]">← Back</button>

      <div className="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-2xl p-4 space-y-3">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-3">
            <AvatarIcon name={post.name} avatar={post.avatar} />
            <div>
              <p className="font-semibold text-sm">{post.name}</p>
              <p className="text-xs text-[var(--color-muted)]">{new Date(post.created_at).toLocaleString()} · <span className="capitalize">{post.type.replace('_', ' ')}</span></p>
            </div>
          </div>
          {isAuthenticated && user?.id === post.user_id && (
            <button onClick={() => { if (window.confirm('Delete this post?')) deleteMutation.mutate(); }} className="text-xs text-red-400 hover:text-red-600">Delete</button>
          )}
        </div>
        {post.body && <p className="text-sm whitespace-pre-wrap leading-relaxed">{post.body}</p>}
        <div className="flex flex-wrap gap-2 pt-2 border-t border-[var(--color-border)]">
          {REACTIONS.map((r) => (
            <button key={r.key} onClick={() => isAuthenticated && reactMutation.mutate(r.key)} disabled={!isAuthenticated}
              className={`flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium transition-colors ${post.user_reaction === r.key ? 'bg-[var(--color-primary)] text-white' : 'bg-[var(--color-surface-alt)] hover:bg-[var(--color-primary)] hover:text-white'}`}>
              {r.emoji} {r.label}
            </button>
          ))}
          <span className="ml-auto text-xs text-[var(--color-muted)] self-center">{post.reactions_count} reactions</span>
        </div>
      </div>

      <div className="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-2xl p-4 space-y-4">
        <h3 className="font-semibold text-sm">{post.comments_count} Comments</h3>
        {isAuthenticated && (
          <form onSubmit={(e) => { e.preventDefault(); commentBody.trim() && commentMutation.mutate(commentBody); }} className="flex gap-2">
            <AvatarIcon name={user?.name ?? ''} avatar={user?.avatar ?? null} size="sm" />
            <input value={commentBody} onChange={(e) => setCommentBody(e.target.value)} placeholder="Write a comment…"
              className="flex-1 px-3 py-2 rounded-lg border border-[var(--color-border)] bg-[var(--color-background)] text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]" />
            <button type="submit" disabled={!commentBody.trim() || commentMutation.isPending}
              className="px-3 py-2 rounded-lg bg-[var(--color-primary)] text-white text-sm disabled:opacity-50">Post</button>
          </form>
        )}
        <div className="space-y-3">
          {(comments as Comment[]).map((c) => <CommentItem key={c.id} comment={c} postId={id!} />)}
          {(comments as Comment[]).length === 0 && <p className="text-sm text-[var(--color-muted)] text-center py-4">No comments yet. Be the first!</p>}
        </div>
      </div>
    </div>
  );
}

function CommentItem({ comment, postId }: { comment: Comment; postId: string }) {
  const [showReply, setShowReply] = useState(false);
  const [replyBody, setReplyBody] = useState('');
  const { isAuthenticated, user } = useAuthStore();
  const qc = useQueryClient();
  const replyMutation = useMutation({
    mutationFn: (body: string) => api.post(`/comments/${comment.id}/replies`, { body }),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['post-comments', postId] }); setReplyBody(''); setShowReply(false); },
  });
  return (
    <div className="space-y-2">
      <div className="flex gap-2">
        <AvatarIcon name={comment.name} avatar={comment.avatar} size="sm" />
        <div className="flex-1 bg-[var(--color-surface-alt)] rounded-xl px-3 py-2">
          <p className="text-xs font-semibold">{comment.name}</p>
          <p className="text-sm mt-0.5 whitespace-pre-wrap">{comment.body}</p>
        </div>
      </div>
      <div className="ml-9 flex items-center gap-3 text-xs text-[var(--color-muted)]">
        <span>{new Date(comment.created_at).toLocaleDateString()}</span>
        {isAuthenticated && <button onClick={() => setShowReply(!showReply)} className="hover:text-[var(--color-primary)]">Reply</button>}
      </div>
      {comment.replies?.map((r) => (
        <div key={r.id} className="ml-9 flex gap-2">
          <AvatarIcon name={r.name} avatar={r.avatar} size="sm" />
          <div className="flex-1 bg-[var(--color-surface-alt)] rounded-xl px-3 py-2">
            <p className="text-xs font-semibold">{r.name}</p>
            <p className="text-sm mt-0.5">{r.body}</p>
          </div>
        </div>
      ))}
      {showReply && (
        <div className="ml-9 flex gap-2">
          <AvatarIcon name={user?.name ?? ''} avatar={user?.avatar ?? null} size="sm" />
          <input value={replyBody} onChange={(e) => setReplyBody(e.target.value)} placeholder={`Reply to ${comment.name}…`}
            className="flex-1 px-3 py-1.5 rounded-lg border border-[var(--color-border)] bg-[var(--color-background)] text-sm focus:outline-none focus:ring-1 focus:ring-[var(--color-primary)]" />
          <button onClick={() => replyBody.trim() && replyMutation.mutate(replyBody)} disabled={!replyBody.trim() || replyMutation.isPending}
            className="px-3 py-1.5 rounded-lg bg-[var(--color-primary)] text-white text-xs disabled:opacity-50">Reply</button>
        </div>
      )}
    </div>
  );
}

function AvatarIcon({ name, avatar, size = 'md' }: { name: string; avatar: string | null; size?: 'sm' | 'md' }) {
  const cls = size === 'sm' ? 'w-7 h-7 text-xs' : 'w-10 h-10 text-sm';
  return avatar
    ? <img src={avatar} className={`${cls} rounded-full object-cover shrink-0`} alt={name} />
    : <div className={`${cls} rounded-full bg-[var(--color-primary)] flex items-center justify-center text-white font-bold shrink-0`}>{name?.[0]?.toUpperCase()}</div>;
}

function PostSkeleton() {
  return (
    <div className="bg-[var(--color-surface)] rounded-2xl p-4 space-y-3 animate-pulse">
      <div className="flex gap-3"><div className="w-10 h-10 rounded-full bg-[var(--color-surface-alt)]" /><div className="space-y-1"><div className="h-3 w-28 bg-[var(--color-surface-alt)] rounded" /><div className="h-2 w-20 bg-[var(--color-surface-alt)] rounded" /></div></div>
      <div className="h-3 w-full bg-[var(--color-surface-alt)] rounded" />
      <div className="h-3 w-3/4 bg-[var(--color-surface-alt)] rounded" />
    </div>
  );
}
