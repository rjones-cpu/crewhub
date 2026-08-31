<?php

namespace Tests\Unit\ServiceRating;

use App\Enums\ServiceRating\Grade;
use App\Services\ServiceRating\Thresholds;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ThresholdsTest extends TestCase
{
    #[DataProvider('workforceProvider')]
    public function test_workforce_variance_boundaries(float $variance, string $expected): void
    {
        $thresholds = [
            'A' => ['max_absolute_variance_percent' => 5],
            'B' => ['max_inclusive_absolute_variance_percent' => 10],
            'C' => ['max_inclusive_absolute_variance_percent' => 25],
        ];

        $this->assertSame($expected, Thresholds::workforceVarianceGrade($variance, $thresholds)->value);
    }

    public static function workforceProvider(): array
    {
        return [
            [0.0, 'A'],
            [5.0, 'A'],
            [5.0001, 'B'],
            [10.0, 'B'],
            [10.0001, 'C'],
            [25.0, 'C'],
            [25.0001, 'D'],
        ];
    }

    #[DataProvider('arrivalProvider')]
    public function test_arrival_lateness_boundaries(int $days, bool $noShow, string $expected): void
    {
        $this->assertSame($expected, Thresholds::arrivalLatenessGrade($days, $noShow)->value);
    }

    public static function arrivalProvider(): array
    {
        return [
            [0, false, 'A'],
            [1, false, 'B'],
            [2, false, 'C'],
            [3, false, 'C'],
            [4, false, 'D'],
            [0, true, 'D'],
        ];
    }

    #[DataProvider('percentProvider')]
    public function test_percent_band_boundaries(float $percent, string $expected): void
    {
        $this->assertSame($expected, Thresholds::percentBandGrade($percent, 0, 20, 40)->value);
    }

    public static function percentProvider(): array
    {
        return [
            [0.0, 'A'],
            [20.0, 'B'],
            [20.0001, 'C'],
            [40.0, 'C'],
            [40.0001, 'D'],
        ];
    }

    public function test_worst_grade_selector(): void
    {
        $this->assertSame('D', Grade::worst([Grade::A, Grade::B, Grade::D, Grade::C])->value);
        $this->assertSame('A', Grade::worst([Grade::A])->value);
        $this->assertNull(Grade::worst([]));
    }
}
