<?php

namespace Tests\Unit\Schedule;

use App\Services\Schedule\ScheduleDragRules;
use Tests\TestCase;

class ScheduleDragRulesTest extends TestCase
{
    private ScheduleDragRules $rules;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rules = new ScheduleDragRules;
    }

    public function test_same_shift_collapse_wipes_a_blue_middle_to_off(): void
    {
        $types = [
            '2026-08-10' => 'travel',
            '2026-08-11' => 'work',
            '2026-08-12' => 'work',
            '2026-08-13' => 'travel',
        ];

        $outcome = $this->rules->resolve('2026-08-10', '2026-08-13', $types);

        $this->assertSame('off', $outcome['cells']['2026-08-10']);
        $this->assertSame('off', $outcome['cells']['2026-08-11']);
        $this->assertSame('off', $outcome['cells']['2026-08-13']);
    }

    public function test_yellow_attach_to_blue_end_moves_travel_and_fills_work(): void
    {
        $types = [
            '2026-08-10' => 'work',
            '2026-08-11' => 'travel',
            '2026-08-12' => 'off',
            '2026-08-13' => 'off',
        ];

        $outcome = $this->rules->resolve('2026-08-11', '2026-08-13', $types);

        $this->assertSame('work', $outcome['cells']['2026-08-11']);
        $this->assertSame('work', $outcome['cells']['2026-08-12']);
        $this->assertSame('travel', $outcome['cells']['2026-08-13']);
    }

    public function test_arrival_yellow_dragged_back_extends_the_rotation_with_work(): void
    {
        $types = [
            '2026-08-10' => 'off',
            '2026-08-11' => 'off',
            '2026-08-12' => 'travel',
            '2026-08-13' => 'work',
        ];

        $outcome = $this->rules->resolve('2026-08-12', '2026-08-10', $types);

        $this->assertSame('travel', $outcome['cells']['2026-08-10']);
        $this->assertSame('work', $outcome['cells']['2026-08-11']);
        $this->assertSame('work', $outcome['cells']['2026-08-12']);
    }

    public function test_arrival_yellow_dragged_forward_shortens_the_rotation(): void
    {
        $types = [
            '2026-08-10' => 'travel',
            '2026-08-11' => 'work',
            '2026-08-12' => 'work',
            '2026-08-13' => 'travel',
        ];

        $outcome = $this->rules->resolve('2026-08-10', '2026-08-12', $types);

        $this->assertSame('off', $outcome['cells']['2026-08-10']);
        $this->assertSame('off', $outcome['cells']['2026-08-11']);
        $this->assertSame('travel', $outcome['cells']['2026-08-12']);
    }

    public function test_yellow_dropped_on_work_turns_the_rest_off(): void
    {
        $types = [
            '2026-08-10' => 'off',
            '2026-08-11' => 'travel',
            '2026-08-12' => 'work',
            '2026-08-13' => 'work',
        ];

        $outcome = $this->rules->resolve('2026-08-11', '2026-08-13', $types);

        $this->assertSame('off', $outcome['cells']['2026-08-11']);
        $this->assertSame('off', $outcome['cells']['2026-08-12']);
        $this->assertSame('travel', $outcome['cells']['2026-08-13']);
    }

    public function test_lone_yellows_over_white_bookend_a_shift(): void
    {
        $types = [
            '2026-08-10' => 'travel',
            '2026-08-11' => 'off',
            '2026-08-12' => 'off',
            '2026-08-13' => 'travel',
        ];

        $outcome = $this->rules->resolve('2026-08-10', '2026-08-13', $types);

        $this->assertSame('travel', $outcome['cells']['2026-08-10']);
        $this->assertSame('work', $outcome['cells']['2026-08-11']);
        $this->assertSame('work', $outcome['cells']['2026-08-12']);
        $this->assertSame('travel', $outcome['cells']['2026-08-13']);
    }

    public function test_forward_combine_across_off_keeps_the_drop_travel(): void
    {
        $types = [
            '2026-08-10' => 'work',
            '2026-08-11' => 'travel',
            '2026-08-12' => 'off',
            '2026-08-13' => 'work',
            '2026-08-14' => 'travel',
        ];

        $outcome = $this->rules->resolve('2026-08-11', '2026-08-14', $types);

        $this->assertSame('work', $outcome['cells']['2026-08-11']);
        $this->assertSame('work', $outcome['cells']['2026-08-12']);
        $this->assertSame('travel', $outcome['cells']['2026-08-14']);
    }

    public function test_yellow_on_yellow_with_blue_gap_collapses_to_work_when_not_a_full_shift_wipe(): void
    {
        $types = [
            '2026-08-10' => 'off',
            '2026-08-11' => 'travel',
            '2026-08-12' => 'work',
            '2026-08-13' => 'travel',
            '2026-08-14' => 'off',
        ];

        // Between is all work → same-shift collapse (rule 0) wins over 1.48.
        $outcome = $this->rules->resolve('2026-08-11', '2026-08-13', $types);

        $this->assertSame('off', $outcome['cells']['2026-08-11']);
        $this->assertSame('off', $outcome['cells']['2026-08-13']);
    }

    public function test_yellow_drag_over_white_bookends(): void
    {
        $types = [
            '2026-08-10' => 'off',
            '2026-08-11' => 'travel',
            '2026-08-12' => 'off',
            '2026-08-13' => 'off',
        ];

        $outcome = $this->rules->resolve('2026-08-11', '2026-08-13', $types);

        $this->assertSame('travel', $outcome['cells']['2026-08-11']);
        $this->assertSame('work', $outcome['cells']['2026-08-12']);
        $this->assertSame('travel', $outcome['cells']['2026-08-13']);
    }

    public function test_off_over_off_is_selection_only(): void
    {
        $types = [
            '2026-08-11' => 'off',
            '2026-08-12' => 'off',
            '2026-08-13' => 'off',
        ];

        $outcome = $this->rules->resolve('2026-08-11', '2026-08-13', $types);

        $this->assertSame(ScheduleDragRules::MODE_SELECTION, $outcome['mode']);
        $this->assertSame([], $outcome['cells']);
    }

    public function test_work_over_white_paints_work(): void
    {
        $types = [
            '2026-08-11' => 'work',
            '2026-08-12' => 'off',
            '2026-08-13' => 'off',
        ];

        $outcome = $this->rules->resolve('2026-08-11', '2026-08-13', $types);

        $this->assertSame('work', $outcome['cells']['2026-08-11']);
        $this->assertSame('work', $outcome['cells']['2026-08-13']);
    }

    public function test_back_drag_restores_the_pre_drag_types(): void
    {
        $types = [
            '2026-08-11' => 'travel',
            '2026-08-12' => 'off',
            '2026-08-13' => 'off',
        ];

        $outcome = $this->rules->resolve('2026-08-13', '2026-08-11', $types, backDragRevert: true);

        $this->assertSame(ScheduleDragRules::MODE_REVERT, $outcome['mode']);
        $this->assertSame('travel', $outcome['cells']['2026-08-11']);
        $this->assertSame('off', $outcome['cells']['2026-08-12']);
        $this->assertSame('off', $outcome['cells']['2026-08-13']);
    }
}
