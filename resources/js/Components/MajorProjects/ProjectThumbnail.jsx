import { Building2 } from 'lucide-react';
import { useState } from 'react';
import { cn } from '@/utils/helpers';

// `icon` doubles as an image source, but Camp sync also writes plain icon names
// into it, so only URLs and file paths are treated as pictures.
const IMAGE_SOURCE = /^(https?:\/\/|\/)|\.(png|jpe?g|webp|gif|svg)$/i;

export default function ProjectThumbnail({ project, className = 'h-9 w-11' }) {
    const [failed, setFailed] = useState(false);
    const source = project?.image_url || project?.icon;
    const isImage = typeof source === 'string' && IMAGE_SOURCE.test(source);

    if (isImage && !failed) {
        return (
            <img
                src={source}
                alt=""
                onError={() => setFailed(true)}
                className={cn('shrink-0 rounded-md object-cover', className)}
            />
        );
    }

    return (
        <span
            aria-hidden="true"
            className={cn(
                'grid shrink-0 place-items-center rounded-md bg-gradient-to-br from-slate-300 to-slate-400 text-white',
                className,
            )}
        >
            <Building2 className="h-4 w-4" strokeWidth={1.8} />
        </span>
    );
}
