<?php

namespace App\Services\Training;

use App\Models\Worker;
use Illuminate\Support\Collection;

class TrainingComplianceService
{
    /**
     * Window used for the "Expiring Soon" figure on the worker Training tab.
     */
    public const EXPIRING_SOON_DAYS = 30;

    /**
     * Headline numbers for the Training Compliance Overview card.
     *
     * Compliance is measured against required training only: elective courses
     * should not drag a worker's percentage down.
     *
     * @return array<string, int|float>
     */
    public function summarize(Worker $worker): array
    {
        $records = $worker->relationLoaded('trainingRecords')
            ? $worker->trainingRecords
            : $worker->trainingRecords()->get();

        return $this->summarizeRecords($records);
    }

    /**
     * @param  Collection<int, \App\Models\TrainingRecord>  $records
     * @return array<string, int|float>
     */
    public function summarizeRecords(Collection $records): array
    {
        $required = $records->where('is_required', true);
        $requiredTotal = $required->count();
        $requiredMet = $required->filter(fn ($record) => $record->isCompliant())->count();

        return [
            'total' => $records->count(),
            'required_total' => $requiredTotal,
            'required_met' => $requiredMet,
            'compliance_percent' => $requiredTotal > 0
                ? (int) round(($requiredMet / $requiredTotal) * 100)
                : 0,
            'completed' => $records->where('status', 'completed')->count(),
            'in_progress' => $records->where('status', 'in_progress')->count(),
            'not_started' => $records->where('status', 'not_started')->count(),
            'expired' => $records->filter(fn ($record) => $record->isExpired())->count(),
            'expiring_soon' => $records
                ->filter(fn ($record) => $record->expiresWithinDays(self::EXPIRING_SOON_DAYS))
                ->count(),
            'pending' => max($requiredTotal - $requiredMet, 0),
        ];
    }
}
