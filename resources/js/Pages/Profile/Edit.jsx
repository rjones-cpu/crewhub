import UpdatePasswordForm from './Partials/UpdatePasswordForm';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm';
import SettingsLayout from '@/Layouts/SettingsLayout';

export default function Edit({ mustVerifyEmail, status }) {
    return (
        <SettingsLayout
            title="Settings"
            pageTitle="Profile"
            subtitle="Company and account configuration"
        >
            <div className="space-y-4">
                <div>
                    <h1 className="text-lg font-semibold text-slate-900">Profile</h1>
                    <p className="mt-0.5 text-xs text-slate-500">
                        Manage your admin name, email, and password.
                    </p>
                </div>

                <div className="card p-4">
                    <UpdateProfileInformationForm
                        mustVerifyEmail={mustVerifyEmail}
                        status={status}
                    />
                </div>

                <div className="card p-4">
                    <UpdatePasswordForm />
                </div>
            </div>
        </SettingsLayout>
    );
}
