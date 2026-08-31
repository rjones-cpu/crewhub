import { MapPin, Search } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { cn } from '@/utils/helpers';

const DEFAULT_CENTER = { lat: 53.5461, lng: -113.4938 }; // Edmonton — Alberta oil & gas hub
const NOMINATIM = 'https://nominatim.openstreetmap.org';

/**
 * Click-to-pin map for project addresses. Uses Leaflet + OpenStreetMap tiles and
 * Nominatim reverse geocoding — no API key required.
 */
export default function AddressMapPicker({
    address = '',
    latitude = null,
    longitude = null,
    onChange,
    error,
    disabled = false,
}) {
    const mapRef = useRef(null);
    const mapInstance = useRef(null);
    const markerInstance = useRef(null);
    const leafletRef = useRef(null);
    const [ready, setReady] = useState(false);
    const [search, setSearch] = useState('');
    const [searching, setSearching] = useState(false);
    const [lookupError, setLookupError] = useState(null);

    const emit = (next) => {
        onChange?.({
            address: next.address ?? '',
            latitude: next.latitude ?? null,
            longitude: next.longitude ?? null,
        });
    };

    const placeMarker = (L, map, lat, lng) => {
        if (markerInstance.current) {
            markerInstance.current.setLatLng([lat, lng]);
        } else {
            markerInstance.current = L.marker([lat, lng], { draggable: !disabled }).addTo(map);
            markerInstance.current.on('dragend', () => {
                const position = markerInstance.current.getLatLng();
                reverseGeocode(position.lat, position.lng);
            });
        }

        map.setView([lat, lng], Math.max(map.getZoom(), 13));
    };

    const reverseGeocode = async (lat, lng) => {
        setLookupError(null);

        try {
            const response = await fetch(
                `${NOMINATIM}/reverse?format=jsonv2&lat=${lat}&lon=${lng}`,
                { headers: { Accept: 'application/json' } },
            );

            if (! response.ok) {
                throw new Error('Reverse geocode failed');
            }

            const payload = await response.json();
            emit({
                address: payload.display_name || `${lat.toFixed(5)}, ${lng.toFixed(5)}`,
                latitude: Number(lat.toFixed(7)),
                longitude: Number(lng.toFixed(7)),
            });
        } catch {
            emit({
                address: `${lat.toFixed(5)}, ${lng.toFixed(5)}`,
                latitude: Number(lat.toFixed(7)),
                longitude: Number(lng.toFixed(7)),
            });
            setLookupError('Could not resolve a street address for this pin.');
        }
    };

    const searchAddress = async (event) => {
        event.preventDefault();

        if (! search.trim() || ! leafletRef.current || ! mapInstance.current) {
            return;
        }

        setSearching(true);
        setLookupError(null);

        try {
            const response = await fetch(
                `${NOMINATIM}/search?format=jsonv2&limit=1&q=${encodeURIComponent(search.trim())}`,
                { headers: { Accept: 'application/json' } },
            );

            if (! response.ok) {
                throw new Error('Search failed');
            }

            const results = await response.json();

            if (! results.length) {
                setLookupError('No matching place found. Try a fuller address.');
                return;
            }

            const hit = results[0];
            const lat = Number(hit.lat);
            const lng = Number(hit.lon);

            placeMarker(leafletRef.current, mapInstance.current, lat, lng);
            emit({
                address: hit.display_name,
                latitude: Number(lat.toFixed(7)),
                longitude: Number(lng.toFixed(7)),
            });
        } catch {
            setLookupError('Address search is unavailable right now.');
        } finally {
            setSearching(false);
        }
    };

    useEffect(() => {
        let cancelled = false;

        (async () => {
            const L = await import('leaflet');
            await import('leaflet/dist/leaflet.css');
            const iconUrl = (await import('leaflet/dist/images/marker-icon.png')).default;
            const iconRetinaUrl = (await import('leaflet/dist/images/marker-icon-2x.png')).default;
            const shadowUrl = (await import('leaflet/dist/images/marker-shadow.png')).default;

            // Vite ships leaflet images through the module graph; without this the
            // default marker icons 404 and the pin disappears.
            delete L.Icon.Default.prototype._getIconUrl;
            L.Icon.Default.mergeOptions({ iconUrl, iconRetinaUrl, shadowUrl });

            if (cancelled || ! mapRef.current || mapInstance.current) {
                return;
            }

            const startLat = latitude ?? DEFAULT_CENTER.lat;
            const startLng = longitude ?? DEFAULT_CENTER.lng;

            const map = L.map(mapRef.current, {
                center: [startLat, startLng],
                zoom: latitude != null ? 13 : 5,
                scrollWheelZoom: ! disabled,
            });

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors',
                maxZoom: 19,
            }).addTo(map);

            if (! disabled) {
                map.on('click', (event) => {
                    placeMarker(L, map, event.latlng.lat, event.latlng.lng);
                    reverseGeocode(event.latlng.lat, event.latlng.lng);
                });
            }

            leafletRef.current = L;
            mapInstance.current = map;

            if (latitude != null && longitude != null) {
                placeMarker(L, map, latitude, longitude);
            }

            // Leaflet needs a post-mount size pass when the card finishes layout.
            requestAnimationFrame(() => map.invalidateSize());
            setReady(true);
        })();

        return () => {
            cancelled = true;
            mapInstance.current?.remove();
            mapInstance.current = null;
            markerInstance.current = null;
        };
        // Mount once; later pin moves go through placeMarker / props sync below.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    useEffect(() => {
        if (! ready || ! leafletRef.current || ! mapInstance.current) {
            return;
        }

        if (latitude == null || longitude == null) {
            return;
        }

        placeMarker(leafletRef.current, mapInstance.current, latitude, longitude);
    }, [latitude, longitude, ready]);

    return (
        <div className="space-y-2">
            <div className="flex items-center justify-between gap-2">
                <label className="block text-sm font-medium text-slate-700">Address</label>
                <span className="inline-flex items-center gap-1 text-[11px] text-slate-400">
                    <MapPin className="h-3 w-3" />
                    Click the map to drop a pin
                </span>
            </div>

            <form onSubmit={searchAddress} className="flex gap-2">
                <div className="relative min-w-0 flex-1">
                    <Search className="pointer-events-none absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" />
                    <input
                        type="search"
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        disabled={disabled}
                        placeholder="Search an address, then refine the pin"
                        className="input-field pl-8"
                    />
                </div>
                <button
                    type="button"
                    disabled={disabled || searching || ! search.trim()}
                    onClick={searchAddress}
                    className="btn-secondary shrink-0 px-3 text-xs disabled:opacity-50"
                >
                    {searching ? 'Searching…' : 'Find'}
                </button>
            </form>

            <div
                ref={mapRef}
                className={cn(
                    'h-56 w-full overflow-hidden rounded-lg border border-slate-200 bg-slate-100',
                    disabled && 'pointer-events-none opacity-70',
                )}
            />

            <textarea
                className="input-field min-h-[64px] text-sm"
                value={address}
                readOnly
                placeholder="Pinned address will appear here"
            />

            {(error || lookupError) && (
                <p className="text-sm text-danger">{error || lookupError}</p>
            )}
        </div>
    );
}
