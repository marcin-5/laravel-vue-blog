export type PrivateConversationSortField = 'subject' | 'created_at' | 'updated_at';
export type PrivateConversationSortDirection = 'asc' | 'desc';
export type PrivateConversationContext = 'blog' | 'group';

export interface PrivateConversationContextOption {
    id: number;
    name: string;
}

export interface PrivateConversationSource {
    type: 'post' | 'blog' | 'group';
    label: string;
    url: string;
}

export interface PrivateConversationParticipant {
    user_id: number;
    email_notifications: boolean;
}

export interface PrivateMessageAuthor {
    id: number;
    name: string;
}

export interface PrivateMessage {
    id: number;
    private_conversation_id: number;
    user_id: number;
    content: string;
    author?: PrivateMessageAuthor;
    created_at?: string;
    updated_at?: string;
    can_edit?: boolean;
}

export interface PrivateConversation {
    id: number;
    blog_id: number | null;
    group_id: number | null;
    post_id: number | null;
    subject: string;
    source: PrivateConversationSource | null;
    initiator: PrivateMessageAuthor;
    owner: PrivateMessageAuthor;
    messages_count?: number;
    messages?: PrivateMessage[];
    email_notifications: boolean | null;
    created_at?: string;
    updated_at?: string;
}

export interface PrivateConversationFilters {
    context: PrivateConversationContext;
    context_id: number | null;
    sort_by: PrivateConversationSortField;
    sort_dir: PrivateConversationSortDirection;
    per_page: number;
}

export interface PrivateConversationPagination {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
}
