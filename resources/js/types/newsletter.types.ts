// Newsletter-related shared types

export type Frequency = 'daily' | 'weekly';

export interface ExistingSubscription {
    blog_id: number;
    frequency: Frequency | string; // allow backend-provided string just in case
    send_time: string | null;
    send_time_weekend: string | null;
    send_day: number | null;
}

export interface NewsletterSubscription {
    blog_id: number;
    selected: boolean;
    frequency: Frequency;
    send_time: string | null;
    send_time_weekend: string | null;
    send_day: number | null;
}

export interface NewsletterConfig {
    daily_weekday_time: string;
    daily_weekend_time: string;
    weekly_day: number;
    weekly_time: string;
}

export interface NewsletterFormData {
    email: string;
    subscriptions: NewsletterSubscription[];
}
