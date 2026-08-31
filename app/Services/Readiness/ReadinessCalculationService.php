<?php

namespace App\Services\Readiness;

use App\Models\Worker;
use App\Models\WorkerReadiness;

class ReadinessCalculationService
{
    public function calculate(Worker $worker): WorkerReadiness
    {
        $worker->loadMissing(['medicalRecords', 'certifications', 'trainingRecords']);
        $medical = $worker->medicalRecords->contains(fn ($record) => $record->status === 'cleared' && (! $record->expires_at || $record->expires_at->isFuture())) ? 'ready' : 'not_ready';
        $certification = $worker->certifications->isNotEmpty() && $worker->certifications->every(fn ($record) => $record->status === 'valid' && (! $record->expires_at || $record->expires_at->isFuture())) ? 'ready' : 'at_risk';
        $training = $worker->trainingRecords->isNotEmpty() && $worker->trainingRecords->every(fn ($record) => $record->status === 'completed' && (! $record->expires_at || $record->expires_at->isFuture())) ? 'ready' : 'at_risk';
        $journey = $worker->journey_access ? 'ready' : 'pending';
        $accommodation = $worker->on_site ? 'ready' : 'pending';
        $siteAccess = $worker->module_access ? 'ready' : 'not_ready';
        $statuses = [$medical, $certification, $training, $journey, $accommodation, $siteAccess];
        $overall = in_array('not_ready', $statuses, true) ? 'not_ready' : (in_array('at_risk', $statuses, true) ? 'at_risk' : (in_array('pending', $statuses, true) ? 'pending_review' : 'ready'));

        return WorkerReadiness::query()->updateOrCreate(
            ['worker_id' => $worker->id],
            [
                'company_id' => $worker->company_id,
                'overall_status' => $overall,
                'medical_status' => $medical,
                'certification_status' => $certification,
                'training_status' => $training,
                'journey_status' => $journey,
                'accommodation_status' => $accommodation,
                'site_access_status' => $siteAccess,
                'last_checked_at' => now(),
            ]
        );
    }
}
