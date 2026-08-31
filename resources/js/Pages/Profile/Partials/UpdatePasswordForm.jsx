import { useForm } from '@inertiajs/react';
import { useRef } from 'react';

export default function UpdatePasswordForm({ className = '' }) {
    const passwordInput = useRef();
    const currentPasswordInput = useRef();

    const {
        data,
        setData,
        errors,
        put,
        reset,
        processing,
        recentlySuccessful,
    } = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const updatePassword = (e) => {
        e.preventDefault();

        put(route('password.update'), {
            preserveScroll: true,
            onSuccess: () => reset(),
            onError: (formErrors) => {
                if (formErrors.password) {
                    reset('password', 'password_confirmation');
                    passwordInput.current?.focus();
                }

                if (formErrors.current_password) {
                    reset('current_password');
                    currentPasswordInput.current?.focus();
                }
            },
        });
    };

    return (
        <section className={className}>
            <header>
                <h2 className="text-sm font-semibold text-slate-900">Update password</h2>
                <p className="mt-1 text-xs text-slate-500">
                    Choose a strong password that you do not use elsewhere.
                </p>
            </header>

            <form onSubmit={updatePassword} className="mt-4 space-y-4">
                <label className="block text-xs font-medium text-slate-700">
                    Current password
                    <input
                        id="current_password"
                        ref={currentPasswordInput}
                        type="password"
                        value={data.current_password}
                        onChange={(e) => setData('current_password', e.target.value)}
                        className="input-field mt-1 min-h-10"
                        autoComplete="current-password"
                    />
                    {errors.current_password && (
                        <span className="mt-1 block text-xs text-danger">{errors.current_password}</span>
                    )}
                </label>
                <label className="block text-xs font-medium text-slate-700">
                    New password
                    <input
                        id="password"
                        ref={passwordInput}
                        type="password"
                        value={data.password}
                        onChange={(e) => setData('password', e.target.value)}
                        className="input-field mt-1 min-h-10"
                        autoComplete="new-password"
                    />
                    {errors.password && (
                        <span className="mt-1 block text-xs text-danger">{errors.password}</span>
                    )}
                </label>
                <label className="block text-xs font-medium text-slate-700">
                    Confirm new password
                    <input
                        id="password_confirmation"
                        type="password"
                        value={data.password_confirmation}
                        onChange={(e) => setData('password_confirmation', e.target.value)}
                        className="input-field mt-1 min-h-10"
                        autoComplete="new-password"
                    />
                    {errors.password_confirmation && (
                        <span className="mt-1 block text-xs text-danger">{errors.password_confirmation}</span>
                    )}
                </label>

                <div className="flex items-center gap-3">
                    <button type="submit" disabled={processing} className="btn-primary min-h-9 px-3 text-xs">
                        Update password
                    </button>
                    {recentlySuccessful && (
                        <p className="text-xs text-slate-500">Saved.</p>
                    )}
                </div>
            </form>
        </section>
    );
}
