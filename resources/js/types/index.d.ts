import { InertiaLinkProps } from '@inertiajs/vue3';
import type { LucideIcon } from 'lucide-vue-next';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

// export interface NavItem {
//     title: string;
//     href: NonNullable<InertiaLinkProps['href']>;
//     icon?: LucideIcon;
//     isActive?: boolean;
// }

export interface NavItem {
  title: string;
  href: any; // Wayfinder route object or string
  icon?: any;
  children?: NavItem[];
}

export type AppPageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    sidebarOpen: boolean;
};

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
}

export type BreadcrumbItemType = BreadcrumbItem;

// //this is for QR Scanner
// export interface ScannedStudent {
//     id: string;
//     name: string;
//     year: string;
//     course: string;
//     section: string;
//     timestamp?: string;
//     status?: 'success' | 'error' | 'duplicate';
// }