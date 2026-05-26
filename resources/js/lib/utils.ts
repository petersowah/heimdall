import { clsx, type ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function timeAgo(dateStr: string): string {
    const diff = Date.now() - new Date(dateStr).getTime();
    const minutes = Math.floor(diff / 60000);
    if (minutes < 1) return 'just now';
    if (minutes < 60) return `${minutes}m ago`;
    const hours = Math.floor(minutes / 60);
    if (hours < 24) return `${hours}h ago`;
    return `${Math.floor(hours / 24)}d ago`;
}

export function getBaseUrl(): string {
    const el = document.getElementById('heimdall-app');
    return el?.dataset.baseUrl ?? '/heimdall';
}

export function apiUrl(path: string): string {
    return `${getBaseUrl()}/api${path}`;
}

export function csrf(): string {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

export async function apiFetch<T>(url: string, options?: RequestInit): Promise<T> {
    const res = await fetch(url, {
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrf(),
            ...options?.headers,
        },
        ...options,
    });

    if (!res.ok) {
        const body = await res.json().catch(() => ({}));
        throw Object.assign(new Error(body.message ?? res.statusText), { status: res.status, body });
    }

    if (res.status === 204) return null as T;
    return res.json();
}
