import { Link } from '@inertiajs/react';
import { createContext, useCallback, useContext, useEffect, useLayoutEffect, useRef, useState } from 'react';
import { createPortal } from 'react-dom';

const DropDownContext = createContext();

const GAP = 8;

/**
 * The shell zooms `body` on wide screens (see --ui-scale in app.css). Rects are
 * reported in real viewport pixels but lengths set on a zoomed element are
 * multiplied by that zoom, so offsets have to be divided back out.
 */
const portalScale = () => {
    const width = document.body.offsetWidth;

    if (!width) {
        return 1;
    }

    const scale = document.body.getBoundingClientRect().width / width;

    return scale > 0 ? scale : 1;
};

const Dropdown = ({ children }) => {
    const [open, setOpen] = useState(false);
    const [anchor, setAnchor] = useState(null);
    const triggerRef = useRef(null);

    const readAnchor = useCallback(() => {
        const rect = triggerRef.current?.getBoundingClientRect();

        if (rect) {
            setAnchor({ top: rect.top, bottom: rect.bottom, left: rect.left, right: rect.right });
        }
    }, []);

    const toggleOpen = () => {
        if (open) {
            setOpen(false);

            return;
        }

        readAnchor();
        setOpen(true);
    };

    // The menu is fixed to the viewport, so it has to follow the trigger when the
    // page or any inner scroll container moves.
    useEffect(() => {
        if (!open) {
            return undefined;
        }

        window.addEventListener('resize', readAnchor);
        window.addEventListener('scroll', readAnchor, true);

        return () => {
            window.removeEventListener('resize', readAnchor);
            window.removeEventListener('scroll', readAnchor, true);
        };
    }, [open, readAnchor]);

    return (
        <DropDownContext.Provider value={{ open, setOpen, toggleOpen, triggerRef, anchor }}>
            <div className="relative">{children}</div>
        </DropDownContext.Provider>
    );
};

const Trigger = ({ children }) => {
    const { toggleOpen, triggerRef } = useContext(DropDownContext);

    return (
        <div ref={triggerRef} onClick={toggleOpen}>
            {children}
        </div>
    );
};

const Content = ({
    align = 'right',
    width = '48',
    contentClasses = 'py-1 bg-white',
    children,
}) => {
    const { open, setOpen, anchor } = useContext(DropDownContext);
    const [menu, setMenu] = useState(null);
    const [flipUp, setFlipUp] = useState(false);

    // Only the flip decision needs the menu's size; the menu is already correctly
    // placed on first paint because it is anchored by edge, not by measured box.
    useLayoutEffect(() => {
        if (!menu || !anchor) {
            return;
        }

        // offsetHeight is in the zoomed local space; compare in viewport pixels.
        const height = menu.offsetHeight * portalScale();

        setFlipUp(window.innerHeight - anchor.bottom < height + GAP && anchor.top > height + GAP);
    }, [menu, anchor]);

    useEffect(() => {
        if (!open) {
            return undefined;
        }

        const closeOnEscape = (event) => {
            if (event.key === 'Escape') {
                setOpen(false);
            }
        };

        document.addEventListener('keydown', closeOnEscape);

        return () => document.removeEventListener('keydown', closeOnEscape);
    }, [open, setOpen]);

    if (!open || !anchor || typeof document === 'undefined') {
        return null;
    }

    // Anchoring the menu's own right/bottom edge to the trigger avoids measuring
    // its width or height, so there is never an unpositioned first frame.
    const scale = portalScale();

    const style = flipUp
        ? { bottom: Math.max(GAP, window.innerHeight - anchor.top + GAP) / scale }
        : { top: (anchor.bottom + GAP) / scale };

    if (align === 'right') {
        style.right = Math.max(GAP, window.innerWidth - anchor.right) / scale;
    } else {
        style.left = Math.max(GAP, anchor.left) / scale;
    }

    const widthClasses = width === '48' ? 'w-48' : '';

    // Portalled to the body so cards and tables with clipped overflow cannot cut
    // the menu off, which is what hid it on the last row of a table.
    return createPortal(
        <>
            <div className="fixed inset-0 z-40" onClick={() => setOpen(false)}></div>
            <div
                ref={setMenu}
                style={style}
                className={`fixed z-50 rounded-md shadow-lg ${widthClasses}`}
                onClick={() => setOpen(false)}
            >
                <div
                    className={
                        `rounded-md ring-1 ring-black ring-opacity-5 ` +
                        contentClasses
                    }
                >
                    {children}
                </div>
            </div>
        </>,
        document.body,
    );
};

const DropdownLink = ({ className = '', children, ...props }) => {
    return (
        <Link
            {...props}
            className={
                'block w-full px-4 py-2 text-start text-sm leading-5 text-gray-700 transition duration-150 ease-in-out hover:bg-gray-100 focus:bg-gray-100 focus:outline-none ' +
                className
            }
        >
            {children}
        </Link>
    );
};

Dropdown.Trigger = Trigger;
Dropdown.Content = Content;
Dropdown.Link = DropdownLink;

export default Dropdown;
