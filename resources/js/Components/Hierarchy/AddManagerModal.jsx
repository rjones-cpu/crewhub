import { useForm } from '@inertiajs/react';
import { useEffect } from 'react';
import Button from '@/Components/Shared/Button';
import Input from '@/Components/Shared/Input';
import Modal from '@/Components/Shared/Modal';
import Select from '@/Components/Shared/Select';
import { statusLabel } from '@/utils/formatters';

export default function AddManagerModal({ show, onClose, projectId, candidates = [] }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        major_project_id: projectId || '',
        user_id: '',
        title: '',
        relationship: 'connected',
    });

    useEffect(() => {
        setData('major_project_id', projectId || '');
        // setData is provided by Inertia and does not need to retrigger this sync.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [projectId]);

    const submit = (event) => {
        event.preventDefault();

        post(route('hierarchy.managers.store'), {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                onClose();
            },
        });
    };

    return (
        <Modal show={show} onClose={onClose} title="Connect a manager">
            <form onSubmit={submit} className="space-y-4">
                <Select
                    name="user_id"
                    label="Manager"
                    placeholder="Select a team member"
                    value={data.user_id}
                    error={errors.user_id}
                    onChange={(event) => setData('user_id', event.target.value)}
                    options={candidates.map((candidate) => ({
                        value: candidate.id,
                        label: `${candidate.name} — ${statusLabel(candidate.role)}`,
                    }))}
                />

                <Input
                    name="title"
                    label="Title"
                    placeholder="Major Project Manager"
                    value={data.title}
                    error={errors.title}
                    onChange={(event) => setData('title', event.target.value)}
                />

                <Select
                    name="relationship"
                    label="Relationship"
                    value={data.relationship}
                    error={errors.relationship}
                    onChange={(event) => setData('relationship', event.target.value)}
                    options={[
                        { value: 'connected', label: 'Connected' },
                        { value: 'primary', label: 'Primary' },
                    ]}
                />

                <p className="text-xs text-slate-500">
                    Setting a manager as Primary moves the current primary to Connected.
                </p>

                <div className="flex justify-end gap-2 pt-1">
                    <Button variant="secondary" onClick={onClose}>
                        Cancel
                    </Button>
                    <Button type="submit" disabled={processing || !data.user_id}>
                        Connect manager
                    </Button>
                </div>
            </form>
        </Modal>
    );
}
