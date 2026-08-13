import LinkExternalController from '@/actions/App/Http/Controllers/LinkExternalController';
import { clsx } from 'clsx';
import type { ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}


export const linkExternal = (link : string)=>{
    return `${LinkExternalController.link().url}?link=${link}`;
}