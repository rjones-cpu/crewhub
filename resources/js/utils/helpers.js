export function cn(...classes) {
    return classes.filter(Boolean).join(' ');
}

export function unwrapPaginated(payload) {
    if (!payload) {
        return { items: [], links: null, meta: null };
    }

    if (Array.isArray(payload)) {
        return { items: payload, links: null, meta: null };
    }

    const meta = payload.meta ?? (
        payload.current_page != null
            ? {
                current_page: payload.current_page,
                from: payload.from,
                to: payload.to,
                last_page: payload.last_page,
                per_page: payload.per_page,
                total: payload.total,
                links: Array.isArray(payload.links) ? payload.links : null,
            }
            : null
    );

    const links = Array.isArray(payload.links) ? payload.links : (meta?.links ?? null);

    return {
        items: payload.data ?? [],
        links,
        meta,
    };
}

export function initials(name = '') {
    const parts = String(name)
        .trim()
        .split(/\s+/)
        .filter(Boolean);

    if (parts.length === 0) {
        return '?';
    }

    if (parts.length === 1) {
        return parts[0].slice(0, 2).toUpperCase();
    }

    return `${parts[0][0]}${parts[parts.length - 1][0]}`.toUpperCase();
}
