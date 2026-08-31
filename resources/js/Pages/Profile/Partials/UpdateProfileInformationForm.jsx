import { Link, useForm, usePage } from '@inertiajs/react';

function splitName(name = '') {
    const trimmed = String(name).trim();
    const space = trimmed.indexOf(' ');

    if (space === -1) {
        return { first_name: trimmed, last_name: '' };
    }

    return {
        first_name: trimmed.slice(0, space),
        last_name: trimmed.slice(space + 1).trim(),
    };
}

export default function UpdateProfileInformation({
    mustVerifyEmail,
    status,
    className = '',
}) {
    const user = usePage().props.auth.user;
    const parts = splitName(user.name);

    const { data, setData, patch, errors, processing, recentlySuccessful } = useForm({
        first_name: parts.first_name,
        last_name: parts.last_name,
        email: user.email,
    });

    const submit = (e) => {
        e.preventDefault();
        patch(route('profile.update'), { preserveScroll: true });
    };

    return (
        <section className={className}>
            <header>
                <h2 className="text-sm font-semibold text-slate-900">Profile information</h2>
                <p className="mt-1 text-xs text-slate-500">
                    Update the name and email on your admin account.
                </p>
            </header>

            <form onSubmit={submit} className="mt-4 space-y-4">
                <div className="grid gap-3 sm:grid-cols-2">
                    <label className="text-xs font-medium text-slate-700">
                        First name
                        <input
                            id="first_name"
                            value={data.first_name}
                            onChange={(e) => setData('first_name', e.target.value)}
                            className="input-field mt-1 min-h-10"
                            required
                            autoComplete="given-name"
                        />
                        {errors.first_name && (
                            <span className="mt-1 block text-xs text-danger">{errors.first_name}</span>
                        )}
                    </label>
                    <label className="text-xs font-medium text-slate-700">
                        Last name
                        <input
                            id="last_name"
                            value={data.last_name}
                            onChange={(e) => setData('last_name', e.target.value)}
                            className="input-field mt-1 min-h-10"
                            required
                            autoComplete="family-name"
                        />
                        {errors.last_name && (
                            <span className="mt-1 block text-xs text-danger">{errors.last_name}</span>
                        )}
                    </label>
                </div>

                <label className="block text-xs font-medium text-slate-700">
                    Email
                    <input
                        id="email"
                        type="email"
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                        className="input-field mt-1 min-h-10"
                        required
                        autoComplete="username"
                    />
                    {errors.email && (
                        <span className="mt-1 block text-xs text-danger">{errors.email}</span>
                    )}
                </label>

                {mustVerifyEmail && user.email_verified_at === null && (
                    <p className="text-xs text-slate-600">
                        Your email address is unverified.{' '}
                        <Link
                            href={route('verification.send')}
                            method="post"
                            as="button"
                            className="font-medium text-brand underline"
                        >
                            Resend verification email
                        </Link>
                        {status === 'verification-link-sent' && (
                            <span className="mt-1 block font-medium text-emerald-600">
                                A new verification link has been sent.
                            </span>
                        )}
                    </p>
                )}

                <div className="flex items-center gap-3">
                    <button type="submit" disabled={processing} className="btn-primary min-h-9 px-3 text-xs">
                        Save profile
                    </button>
                    {recentlySuccessful && (
                        <p className="text-xs text-slate-500">Saved.</p>
                    )}
                </div>
            </form>
        </section>
    );
}
