import { router } from '@inertiajs/react';

export default function usePagination(links = []) {
    const goTo = (url) => {
        if (!url) {
            return;
        }

        router.get(url, {}, { preserveState: true, preserveScroll: true });
    };

    return {
        links: Array.isArray(links) ? links : [],
        goTo,
    };
}
