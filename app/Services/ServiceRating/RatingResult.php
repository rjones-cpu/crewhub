<?php

namespace App\Services\ServiceRating;

use App\Enums\ServiceRating\CriterionCode;
use App\Enums\ServiceRating\DataQualityStatus;
use App\Enums\ServiceRating\Grade;

final class RatingResult
{
    /**
     * @param  list<CriterionResult>  $criteria
     * @param  list<array<string, mixed>>  $criticalOverrides
     */
    public function __construct(
        public readonly ?Grade $overallGrade,
        public readonly array $criteria,
        public readonly array $policy,
        public readonly string $policyVersion,
        public readonly string $evidenceFingerprint,
        public readonly array $trace,
        public readonly DataQualityStatus $dataQuality,
        public readonly bool $pendingData = false,
        public readonly array $criticalOverrides = [],
    ) {
    }

    /** @return list<CriterionResult> */
    public function applicableCriteria(): array
    {
        return array_values(array_filter(
            $this->criteria,
            fn (CriterionResult $result) => $result->isApplicable(),
        ));
    }

    public function criterion(CriterionCode $code): ?CriterionResult
    {
        foreach ($this->criteria as $result) {
            if ($result->criterion === $code) {
                return $result;
            }
        }

        return null;
    }
}
