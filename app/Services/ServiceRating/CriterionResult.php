<?php

namespace App\Services\ServiceRating;

use App\Enums\ServiceRating\CriterionCode;
use App\Enums\ServiceRating\DataQualityStatus;
use App\Enums\ServiceRating\Grade;

final class CriterionResult
{
    public function __construct(
        public readonly CriterionCode $criterion,
        public readonly bool $applicable,
        public readonly ?Grade $grade,
        public readonly ?float $numerator,
        public readonly ?float $denominator,
        public readonly ?float $measuredValue,
        public readonly ?string $measuredUnit,
        public readonly string $driverSummary,
        public readonly array $trace,
        public readonly DataQualityStatus $dataQuality = DataQualityStatus::Sufficient,
        public readonly ?string $applicabilityReasonCode = null,
        public readonly int $exceptionCount = 0,
        public readonly bool $criticalOverrideApplied = false,
        public readonly ?string $thresholdCode = null,
    ) {
    }

    public static function notApplicable(CriterionCode $criterion, string $reasonCode, string $summary): self
    {
        return new self(
            criterion: $criterion,
            applicable: false,
            grade: null,
            numerator: null,
            denominator: null,
            measuredValue: null,
            measuredUnit: null,
            driverSummary: $summary,
            trace: ['applicable' => false, 'reason_code' => $reasonCode],
            applicabilityReasonCode: $reasonCode,
        );
    }

    public function isApplicable(): bool
    {
        return $this->applicable && $this->grade !== null;
    }

    public function withGrade(Grade $grade, array $extraTrace = []): self
    {
        return new self(
            criterion: $this->criterion,
            applicable: $this->applicable,
            grade: $grade,
            numerator: $this->numerator,
            denominator: $this->denominator,
            measuredValue: $this->measuredValue,
            measuredUnit: $this->measuredUnit,
            driverSummary: $this->driverSummary,
            trace: array_merge($this->trace, $extraTrace),
            dataQuality: $this->dataQuality,
            applicabilityReasonCode: $this->applicabilityReasonCode,
            exceptionCount: $this->exceptionCount,
            criticalOverrideApplied: $this->criticalOverrideApplied,
            thresholdCode: $this->thresholdCode,
        );
    }

    public function withCriticalOverride(string $ruleCode): self
    {
        return new self(
            criterion: $this->criterion,
            applicable: true,
            grade: Grade::D,
            numerator: $this->numerator,
            denominator: $this->denominator,
            measuredValue: $this->measuredValue,
            measuredUnit: $this->measuredUnit,
            driverSummary: $this->driverSummary,
            trace: array_merge($this->trace, [
                'critical_override' => true,
                'critical_rule_code' => $ruleCode,
            ]),
            dataQuality: $this->dataQuality,
            applicabilityReasonCode: $this->applicabilityReasonCode,
            exceptionCount: $this->exceptionCount,
            criticalOverrideApplied: true,
            thresholdCode: $this->thresholdCode,
        );
    }
}
