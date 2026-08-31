<?php

namespace App\Services\ServiceRating;

use App\Models\ServiceRatingPolicy;
use App\Models\ServiceRatingPolicyVersion;
use Illuminate\Support\Facades\File;

class PolicyLoader
{
    /**
     * Resolve the active policy JSON for a company/project. Falls back to the
     * package working-default file when no DB version is activated yet.
     *
     * @return array{version: ServiceRatingPolicyVersion|null, policy: array<string, mixed>, version_label: string}
     */
    public function activeFor(int $companyId, int $majorProjectId): array
    {
        $policy = ServiceRatingPolicy::query()
            ->where('company_id', $companyId)
            ->where(function ($query) use ($majorProjectId) {
                $query->where('major_project_id', $majorProjectId)
                    ->orWhereNull('major_project_id');
            })
            ->orderByRaw('major_project_id is null')
            ->with('currentVersion')
            ->first();

        if ($policy?->currentVersion) {
            return [
                'version' => $policy->currentVersion,
                'policy' => $policy->currentVersion->policy_json,
                'version_label' => $policy->currentVersion->version,
            ];
        }

        return [
            'version' => null,
            'policy' => $this->defaultPolicy(),
            'version_label' => (string) data_get($this->defaultPolicy(), 'version', '1.0-working-default'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function defaultPolicy(): array
    {
        $path = config('service_rating.default_policy_path');

        if (! is_string($path) || ! File::exists($path)) {
            throw new \RuntimeException('Service rating default policy file is missing.');
        }

        /** @var array<string, mixed> $policy */
        $policy = json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);

        return $policy;
    }

    public function hash(array $policy): string
    {
        return hash('sha256', json_encode($policy, JSON_THROW_ON_ERROR));
    }
}
