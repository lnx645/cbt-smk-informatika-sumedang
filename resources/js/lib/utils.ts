import LinkExternalController from '@/actions/App/Http/Controllers/LinkExternalController';
import { clsx } from 'clsx';
import type { ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';
import type { User } from '@/types/auth';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

/**
 * Determine whether the given user has the provided role.
 */
export function isRole(user: Pick<User, 'role'> | null | undefined, role: User['role']): boolean {
    return user?.role === role;
}


export const linkExternal = (link : string)=>{
    return `${LinkExternalController.link().url}?link=${link}`;
}