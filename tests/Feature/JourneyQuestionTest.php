<?php

namespace Tests\Feature;

use App\Enums\JourneyQuestionType;
use App\Enums\Role;
use App\Models\Company;
use App\Models\JourneyQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JourneyQuestionTest extends TestCase
{
    use RefreshDatabase;

    private function companyAdmin(?Company $company = null): User
    {
        $company ??= Company::factory()->create();

        return User::factory()->create([
            'company_id' => $company->id,
            'role' => Role::CompanyAdmin,
        ]);
    }

    public function test_question_list_renders_with_the_library(): void
    {
        $user = $this->companyAdmin();
        JourneyQuestion::factory()->count(3)->create(['company_id' => $user->company_id]);

        $this->actingAs($user)
            ->get(route('journeys.questions'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Journeys/Questions/Index')
                ->has('questions.data', 3)
                ->has('library')
                ->has('questionTypes', count(JourneyQuestionType::cases()))
                ->where('canManage', true));
    }

    public function test_company_admin_can_create_a_dropdown_question(): void
    {
        $user = $this->companyAdmin();

        $this->actingAs($user)
            ->post(route('journeys.questions.store'), [
                'type' => JourneyQuestionType::Dropdown->value,
                'question' => 'What type of road conditions will you encounter?',
                'options' => ['Good', 'Gravel', 'Poor'],
                'is_required' => true,
                'is_active' => true,
            ])
            ->assertRedirect();

        $question = JourneyQuestion::query()->firstOrFail();

        $this->assertSame(['Good', 'Gravel', 'Poor'], $question->options);
        $this->assertSame(1, $question->sort_order);
    }

    public function test_choice_questions_require_options(): void
    {
        $user = $this->companyAdmin();

        $this->actingAs($user)
            ->post(route('journeys.questions.store'), [
                'type' => JourneyQuestionType::MultipleChoice->value,
                'question' => 'Pick one',
            ])
            ->assertSessionHasErrors('options');
    }

    public function test_fixed_choice_questions_expose_implicit_answers(): void
    {
        $user = $this->companyAdmin();
        $question = JourneyQuestion::factory()->create([
            'company_id' => $user->company_id,
            'type' => JourneyQuestionType::YesNo,
        ]);

        $this->assertSame(['Yes', 'No'], $question->answerOptions());
    }

    public function test_max_characters_is_dropped_for_types_that_cannot_use_it(): void
    {
        $user = $this->companyAdmin();

        $this->actingAs($user)
            ->post(route('journeys.questions.store'), [
                'type' => JourneyQuestionType::YesNo->value,
                'question' => 'Do you have satellite communication?',
                'max_characters' => 150,
            ])
            ->assertRedirect();

        $this->assertNull(JourneyQuestion::query()->firstOrFail()->max_characters);
    }

    public function test_company_admin_can_reorder_questions(): void
    {
        $user = $this->companyAdmin();
        $first = JourneyQuestion::factory()->create([
            'company_id' => $user->company_id,
            'sort_order' => 1,
        ]);
        $second = JourneyQuestion::factory()->create([
            'company_id' => $user->company_id,
            'sort_order' => 2,
        ]);

        $this->actingAs($user)
            ->post(route('journeys.questions.reorder'), ['ids' => [$second->id, $first->id]])
            ->assertRedirect();

        $this->assertSame(1, $second->fresh()->sort_order);
        $this->assertSame(2, $first->fresh()->sort_order);
    }

    public function test_company_admin_can_delete_a_question(): void
    {
        $user = $this->companyAdmin();
        $question = JourneyQuestion::factory()->create(['company_id' => $user->company_id]);

        $this->actingAs($user)
            ->delete(route('journeys.questions.destroy', $question))
            ->assertRedirect();

        $this->assertSoftDeleted($question);
    }

    public function test_questions_are_scoped_to_the_owning_company(): void
    {
        $owner = $this->companyAdmin();
        $outsider = $this->companyAdmin(Company::factory()->create(['name' => 'Other Co']));
        JourneyQuestion::factory()->create(['company_id' => $owner->company_id]);

        $this->actingAs($outsider)
            ->get(route('journeys.questions'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('questions.data', 0));
    }
}
