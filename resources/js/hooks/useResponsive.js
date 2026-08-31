import { useEffect, useState } from 'react';

function getIsDesktop() {
    if (typeof window === 'undefined') {
        return true;
    }

    return window.matchMedia('(min-width: 1024px)').matches;
}

export default function useResponsive() {
    const [isDesktop, setIsDesktop] = useState(getIsDesktop);

    useEffect(() => {
        const media = window.matchMedia('(min-width: 1024px)');
        const onChange = () => setIsDesktop(media.matches);

        onChange();
        media.addEventListener('change', onChange);

        return () => media.removeEventListener('change', onChange);
    }, []);

    return {
        isDesktop,
        isMobile: !isDesktop,
    };
}
