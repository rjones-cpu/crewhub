import { ChevronDown } from 'lucide-react';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { createPortal } from 'react-dom';

const GAP = 4;
const MENU_MAX_HEIGHT = 240;
const MENU_MIN_HEIGHT = 120;

/**
 * Mirrors the helper in Dropdown.jsx: the shell zooms `body` on wide screens
 * (see --ui-scale in app.css), so rects read in viewport pixels have to be
 * divided back out before they are used as lengths on the portalled menu.
 */
const portalScale = () => {
    const width = document.body.offsetWidth;

    if (!width) {
        return 1;
    }

    const scale = document.body.getBoundingClientRect().width / width;

    return scale > 0 ? scale : 1;
};

const toOption = (option) => {
    if (option !== null && typeof option === 'object') {
        return { value: String(option.value ?? ''), label: String(option.label ?? option.value ?? '') };
    }

    return { value: String(option), label: String(option) };
};

/**
 * Type-to-filter replacement for a native <select> with a long option list.
 *
 * The menu is portalled to the body because callers render it inside scrolling
 * panels and cards with clipped overflow, which would cut the list off.
 */
export default function SearchableSelect({
    value = '',
    onChange,
    options = [],
    placeholder = 'Select an option',
    disabled = false,
    clearable = true,
    className = '',
    menuClassName = 'text-[10px] leading-4',
    emptyMessage = 'No matches found',
    ariaLabel,
    id,
}) {
    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState('');
    const [highlight, setHighlight] = useState(0);
    const [anchor, setAnchor] = useState(null);
    const triggerRef = useRef(null);
    const inputRef = useRef(null);
    const listRef = useRef(null);

    const items = useMemo(() => {
        const normalized = options.map(toOption);

        return clearable ? [{ value: '', label: placeholder }, ...normalized] : normalized;
    }, [options, clearable, placeholder]);

    const selectedLabel = useMemo(() => {
        const current = String(value ?? '');

        if (current === '') {
            return '';
        }

        return items.find((item) => item.value === current)?.label ?? current;
    }, [items, value]);

    const filtered = useMemo(() => {
        const needle = query.trim().toLowerCase();

        if (! needle) {
            return items;
        }

        return items.filter((item) => item.label.toLowerCase().includes(needle));
    }, [items, query]);

    const readAnchor = useCallback(() => {
        const rect = triggerRef.current?.getBoundingClientRect();

        if (rect) {
            setAnchor({ top: rect.top, bottom: rect.bottom, left: rect.left, right: rect.right });
        }
    }, []);

    const openMenu = () => {
        if (disabled || open) {
            return;
        }

        readAnchor();
        setQuery('');
        setHighlight(Math.max(0, items.findIndex((item) => item.value === String(value ?? ''))));
        setOpen(true);
    };

    const closeMenu = () => {
        setOpen(false);
        setQuery('');
    };

    const commit = (item) => {
        onChange?.(item.value);
        closeMenu();
    };

    // The menu is fixed to the viewport, so it has to follow the trigger when the
    // page or any inner scroll container moves.
    useEffect(() => {
        if (! open) {
            return undefined;
        }

        window.addEventListener('resize', readAnchor);
        window.addEventListener('scroll', readAnchor, true);

        return () => {
            window.removeEventListener('resize', readAnchor);
            window.removeEventListener('scroll', readAnchor, true);
        };
    }, [open, readAnchor]);

    useEffect(() => {
        if (open) {
            listRef.current?.children[highlight]?.scrollIntoView({ block: 'nearest' });
        }
    }, [open, highlight]);

    const onKeyDown = (event) => {
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();

            if (! open) {
                openMenu();

                return;
            }

            const step = event.key === 'ArrowDown' ? 1 : -1;

            setHighlight((current) => Math.min(Math.max(current + step, 0), Math.max(filtered.length - 1, 0)));

            return;
        }

        if (event.key === 'Enter') {
            if (! open) {
                return;
            }

            // Without this the drawer form would submit on the first Enter press.
            event.preventDefault();

            if (filtered[highlight]) {
                commit(filtered[highlight]);
            }

            return;
        }

        if (event.key === 'Escape' && open) {
            // Drawers close on a document-level Escape listener; keep this one local.
            event.stopPropagation();
            closeMenu();

            return;
        }

        if (event.key === 'Tab' && open) {
            closeMenu();
        }
    };

    const menuStyle = () => {
        const scale = portalScale();
        const spaceBelow = window.innerHeight - anchor.bottom - GAP;
        const spaceAbove = anchor.top - GAP;
        const flipUp = spaceBelow < MENU_MIN_HEIGHT && spaceAbove > spaceBelow;
        const available = flipUp ? spaceAbove : spaceBelow;

        return {
            left: anchor.left / scale,
            width: (anchor.right - anchor.left) / scale,
            maxHeight: Math.max(MENU_MIN_HEIGHT, Math.min(MENU_MAX_HEIGHT, available)) / scale,
            ...(flipUp
                ? { bottom: (window.innerHeight - anchor.top + GAP) / scale }
                : { top: (anchor.bottom + GAP) / scale }),
        };
    };

    return (
        <div ref={triggerRef} className="relative">
            <input
                ref={inputRef}
                id={id}
                type="text"
                role="combobox"
                aria-expanded={open}
                aria-autocomplete="list"
                aria-label={ariaLabel}
                autoComplete="off"
                disabled={disabled}
                value={open ? query : selectedLabel}
                placeholder={placeholder}
                onChange={(event) => {
                    setQuery(event.target.value);
                    setHighlight(0);
                }}
                onMouseDown={openMenu}
                onFocus={openMenu}
                onBlur={closeMenu}
                onKeyDown={onKeyDown}
                className={`cursor-pointer pr-7 ${className}`}
            />
            <ChevronDown className="pointer-events-none absolute right-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" />

            {open && anchor && typeof document !== 'undefined' && createPortal(
                <ul
                    ref={listRef}
                    role="listbox"
                    style={menuStyle()}
                    className={`fixed z-[60] overflow-y-auto rounded border border-slate-200 bg-white py-1 shadow-lg ${menuClassName}`}
                >
                    {filtered.length === 0 && (
                        <li className="px-2.5 py-1.5 text-slate-400">{emptyMessage}</li>
                    )}
                    {filtered.map((item, index) => {
                        const isSelected = item.value === String(value ?? '');

                        return (
                            <li
                                key={item.value || `__placeholder_${item.label}`}
                                role="option"
                                aria-selected={isSelected}
                                // mousedown instead of click: preventDefault keeps focus on the
                                // input, so the blur handler cannot close the menu first.
                                onMouseDown={(event) => {
                                    event.preventDefault();
                                    commit(item);
                                }}
                                onMouseEnter={() => setHighlight(index)}
                                className={`cursor-pointer truncate px-2.5 py-1.5 ${
                                    index === highlight ? 'bg-indigo-600 text-white' : 'text-slate-700'
                                } ${item.value === '' ? 'italic' : ''}`}
                            >
                                {item.label}
                            </li>
                        );
                    })}
                </ul>,
                document.body,
            )}
        </div>
    );
}
