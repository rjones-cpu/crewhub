<?php

namespace App\Http\Controllers;

use App\Enums\JourneyQuestionType;
use App\Http\Requests\JourneyQuestionRequest;
use App\Http\Resources\JourneyQuestionResource;
use App\Models\JourneyQuestion;
use App\Support\JourneyQuestionLibrary;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class JourneyQuestionController extends Controller
{
    /**
     * @var list<int>
     */
    private const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', JourneyQuestion::class);

        $search = trim((string) $request->query('search', ''));
        $type = $request->query('type');
        $status = $request->query('status', 'active');
        $perPage = (int) $request->query('per_page', self::PER_PAGE_OPTIONS[0]);

        if (! in_array($perPage, self::PER_PAGE_OPTIONS, true)) {
            $perPage = self::PER_PAGE_OPTIONS[0];
        }

        $query = JourneyQuestion::query()->ordered();

        if ($search !== '') {
            $query->where(function (Builder $inner) use ($search): void {
                $inner->where('question', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($type) {
            $query->where('type', $type);
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $questions = $query->paginate($perPage)->withQueryString();

        return Inertia::render('Journeys/Questions/Index', [
            'questions' => JourneyQuestionResource::collection($questions),
            'library' => $this->library(),
            'questionTypes' => array_map(
                fn (JourneyQuestionType $case) => [
                    'value' => $case->value,
                    'label' => $case->label(),
                    'has_options' => $case->hasCustomOptions(),
                    'supports_max_characters' => $case->supportsMaxCharacters(),
                ],
                JourneyQuestionType::cases(),
            ),
            'filters' => [
                'search' => $search,
                'type' => $type ?? '',
                'status' => $status,
                'per_page' => $perPage,
            ],
            'canManage' => $request->user()->can('create', JourneyQuestion::class),
        ]);
    }

    public function store(JourneyQuestionRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['company_id'] = $request->user()->company_id;
        $data['sort_order'] = (int) JourneyQuestion::query()->max('sort_order') + 1;
        $data['options'] = $this->normaliseOptions($data);
        $data['max_characters'] = $this->normaliseMaxCharacters($data);

        JourneyQuestion::query()->create($data);

        return back()->with('success', 'Question created.');
    }

    public function update(JourneyQuestionRequest $request, JourneyQuestion $question): RedirectResponse
    {
        $data = $request->validated();
        $data['options'] = $this->normaliseOptions($data);
        $data['max_characters'] = $this->normaliseMaxCharacters($data);

        $question->update($data);

        return back()->with('success', 'Question updated.');
    }

    public function destroy(Request $request, JourneyQuestion $question): RedirectResponse
    {
        $this->authorize('delete', $question);

        $question->delete();

        return back()->with('success', 'Question deleted.');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $this->authorize('create', JourneyQuestion::class);

        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => [
                'integer',
                Rule::exists('journey_questions', 'id')->where('company_id', $request->user()->company_id),
            ],
        ]);

        foreach ($validated['ids'] as $position => $id) {
            JourneyQuestion::query()->whereKey($id)->update(['sort_order' => $position + 1]);
        }

        return back()->with('success', 'Question order updated.');
    }

    /**
     * Library templates, flagged so the UI can grey out ones already adopted.
     *
     * @return list<array<string, mixed>>
     */
    private function library(): array
    {
        $existing = JourneyQuestion::query()->pluck('question')->all();

        return array_map(function (array $template) use ($existing): array {
            $type = JourneyQuestionType::from($template['type']);
            $template['type_label'] = $type->label();
            $template['already_added'] = in_array($template['question'], $existing, true);

            return $template;
        }, JourneyQuestionLibrary::templates());
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    private function normaliseOptions(array $data): array
    {
        $type = JourneyQuestionType::from($data['type']);

        return $type->hasCustomOptions()
            ? array_values(array_filter($data['options'] ?? [], fn ($option) => trim((string) $option) !== ''))
            : [];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function normaliseMaxCharacters(array $data): ?int
    {
        return JourneyQuestionType::from($data['type'])->supportsMaxCharacters()
            ? ($data['max_characters'] ?? null)
            : null;
    }
}
