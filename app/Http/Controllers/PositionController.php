<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportPositionsRequest;
use App\Http\Requests\StorePositionRequest;
use App\Models\Position;
use App\Models\Worker;
use App\Services\Positions\PositionCsvImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PositionController extends Controller
{
    /**
     * @var list<int>
     */
    private const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Position::class);

        $search = trim((string) $request->query('search', ''));
        $status = $request->query('status', 'all');
        $perPage = (int) $request->query('per_page', self::PER_PAGE_OPTIONS[0]);

        if (! in_array($perPage, self::PER_PAGE_OPTIONS, true)) {
            $perPage = self::PER_PAGE_OPTIONS[0];
        }

        $positions = Position::query()
            ->ordered()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Position $position) => [
                'id' => $position->id,
                'name' => $position->name,
                'code' => $position->code,
                'description' => $position->description,
                'is_active' => $position->is_active,
            ]);

        return Inertia::render('Settings/Positions', [
            'positions' => $positions,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function store(StorePositionRequest $request): RedirectResponse
    {
        Position::query()->create([
            ...$request->validated(),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Position added.');
    }

    public function update(StorePositionRequest $request, Position $position): RedirectResponse
    {
        $previousName = $position->name;
        $position->update([
            ...$request->validated(),
            'is_active' => $request->boolean('is_active', $position->is_active),
        ]);

        if ($previousName !== $position->name) {
            Worker::query()
                ->where('position', $previousName)
                ->update(['position' => $position->name]);
        }

        return back()->with('success', 'Position updated.');
    }

    public function destroy(Position $position): RedirectResponse
    {
        $this->authorize('delete', $position);

        $position->delete();

        return back()->with('success', 'Position removed.');
    }

    public function import(ImportPositionsRequest $request, PositionCsvImporter $importer): RedirectResponse
    {
        $summary = $importer->import($request->file('file'));

        return back()->with(
            'success',
            "CSV import finished: {$summary['created']} added, {$summary['updated']} updated, {$summary['skipped']} skipped.",
        );
    }

    public function template(): StreamedResponse
    {
        $this->authorize('viewAny', Position::class);

        return response()->streamDownload(function (): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['name', 'code', 'description']);
            fputcsv($handle, ['Site Supervisor', 'SUP', 'Leads the crew on site']);
            fputcsv($handle, ['Field Engineer', 'FE', '']);
            fclose($handle);
        }, 'positions-template.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
