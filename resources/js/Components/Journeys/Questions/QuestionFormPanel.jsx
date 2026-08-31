import { useForm } from '@inertiajs/react';
import { Info, Plus, Trash2 } from 'lucide-react';
import { useEffect } from 'react';
import { Field, SelectInput, TextArea, TextInput } from '@/Components/Journeys/Vehicles/FormPrimitives';
import ToggleSwitch from '@/Components/Shared/ToggleSwitch';

const EMPTY = {
    type: '',
    question: '',
    description: '',
    help_text: '',
    options: ['', '', ''],
    max_characters: '',
    risk_key: '',
    risk_weight: 0,
    is_required: true,
    is_active: true,
};

function toFormState(question) {
    if (!question) {
        return EMPTY;
    }

    return {
        type: question.type || '',
        question: question.question || '',
        description: question.description || '',
        help_text: question.help_text || '',
        options: question.options?.length ? question.options : ['', '', ''],
        max_characters: question.max_characters ?? '',
        risk_key: question.risk_key || '',
        risk_weight: question.risk_weight ?? 0,
        is_required: Boolean(question.is_required),
        is_active: Boolean(question.is_active),
    };
}

export default function QuestionFormPanel({ question, questionTypes = [], onCancel, onSaved }) {
    const editing = Boolean(question);
    const { data, setData, post, put, processing, errors, reset, clearErrors } = useForm(
        toFormState(question),
    );

    useEffect(() => {
        clearErrors();
        setData(toFormState(question));
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [question?.id]);

    const selectedType = questionTypes.find((type) => type.value === data.type);
    const showOptions = Boolean(selectedType?.has_options);
    const showMaxCharacters = Boolean(selectedType?.supports_max_characters);

    const setOption = (index, value) => {
        setData('options', data.options.map((option, i) => (i === index ? value : option)));
    };

    const removeOption = (index) => {
        setData('options', data.options.filter((_, i) => i !== index));
    };

    const submit = (event) => {
        event.preventDefault();
        const options = {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                onSaved?.();
            },
        };

        if (editing) {
            put(route('journeys.questions.update', question.id), options);
        } else {
            post(route('journeys.questions.store'), options);
        }
    };

    return (
        <aside className="card w-full shrink-0 p-4 lg:sticky lg:top-24 lg:w-[280px]">
            <h2 className="text-sm font-semibold text-slate-900">
                {editing ? 'Edit Question' : 'Add Question'}
            </h2>
            <p className="mt-0.5 text-[11px] text-slate-500">
                {editing
                    ? 'Update this question for the journey assessment.'
                    : 'Create a new question for the journey assessment.'}
            </p>

            <form onSubmit={submit} className="mt-4 space-y-3">
                <Field label="Select Question Type" required error={errors.type} htmlFor="type">
                    <SelectInput
                        id="type"
                        value={data.type}
                        onChange={(e) => setData('type', e.target.value)}
                        error={errors.type}
                        placeholder="Select type..."
                        options={questionTypes}
                    />
                </Field>

                <Field
                    label="Enter Question"
                    required
                    error={errors.question}
                    hint="Be clear and concise"
                    htmlFor="question"
                >
                    <TextArea
                        id="question"
                        rows={3}
                        value={data.question}
                        onChange={(e) => setData('question', e.target.value)}
                        error={errors.question}
                        placeholder="Enter your question here..."
                    />
                </Field>

                {showOptions && (
                    <div>
                        <p className="text-[11px] font-medium text-slate-700">Answers</p>
                        <p className="mt-0.5 text-[11px] text-slate-400">
                            Add the possible answers for this question.
                        </p>

                        <div className="mt-2 space-y-2">
                            {data.options.map((option, index) => (
                                <div key={index} className="flex items-center gap-2">
                                    <span className="w-12 shrink-0 text-[11px] text-slate-500">
                                        Option {index + 1}
                                    </span>
                                    <TextInput
                                        value={option}
                                        onChange={(e) => setOption(index, e.target.value)}
                                        error={errors[`options.${index}`]}
                                        placeholder={`Answer ${index + 1}`}
                                        className="flex-1"
                                    />
                                    <button
                                        type="button"
                                        onClick={() => removeOption(index)}
                                        aria-label={`Remove option ${index + 1}`}
                                        className="shrink-0 rounded p-1 text-slate-400 transition hover:bg-danger-soft hover:text-danger"
                                    >
                                        <Trash2 className="h-3.5 w-3.5" />
                                    </button>
                                </div>
                            ))}
                        </div>

                        {errors.options && (
                            <p className="mt-1 text-[11px] text-danger">{errors.options}</p>
                        )}

                        <button
                            type="button"
                            onClick={() => setData('options', [...data.options, ''])}
                            className="mt-2 inline-flex items-center gap-1 text-[11px] font-medium text-brand hover:underline"
                        >
                            <Plus className="h-3 w-3" />
                            Add Answer
                        </button>
                    </div>
                )}

                {showMaxCharacters && (
                    <Field
                        label="Max Characters (Optional)"
                        error={errors.max_characters}
                        htmlFor="max_characters"
                    >
                        <TextInput
                            id="max_characters"
                            type="number"
                            min="1"
                            max="2000"
                            value={data.max_characters}
                            onChange={(e) => setData('max_characters', e.target.value)}
                            error={errors.max_characters}
                            placeholder="e.g. 150"
                        />
                    </Field>
                )}

                <div className="flex items-center justify-between gap-2">
                    <span className="inline-flex items-center gap-1 text-[11px] font-medium text-slate-700">
                        Required Question
                        <Info className="h-3 w-3 text-slate-400" />
                    </span>
                    <ToggleSwitch
                        size="sm"
                        checked={data.is_required}
                        onChange={(value) => setData('is_required', value)}
                        label="Required question"
                    />
                </div>

                <Field
                    label="Help Text (Optional)"
                    error={errors.help_text}
                    htmlFor="help_text"
                >
                    <TextArea
                        id="help_text"
                        rows={2}
                        value={data.help_text}
                        onChange={(e) => setData('help_text', e.target.value)}
                        error={errors.help_text}
                        placeholder="Add help text to guide the user..."
                    />
                </Field>

                <div className="space-y-2 pt-1">
                    <button
                        type="submit"
                        disabled={processing}
                        className="btn-primary w-full justify-center py-2 text-xs"
                    >
                        {processing
                            ? 'Saving...'
                            : editing
                                ? 'Save Changes'
                                : 'Create Question'}
                    </button>
                    <button
                        type="button"
                        onClick={() => {
                            reset();
                            onCancel?.();
                        }}
                        className="w-full rounded-lg px-3 py-2 text-center text-xs font-medium text-slate-500 transition hover:bg-slate-50"
                    >
                        Cancel
                    </button>
                </div>
            </form>
        </aside>
    );
}
