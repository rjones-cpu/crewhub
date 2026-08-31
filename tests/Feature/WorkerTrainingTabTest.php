<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Certification;
use App\Models\Company;
use App\Models\TrainingRecord;
use App\Models\User;
use App\Models\Worker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WorkerTrainingTabTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected User $admin;

    protected Worker $worker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::query()->create([
            'name' => 'Baker Hughes',
            'code' => 'BKRH',
            'status' => 'active',
        ]);

        $this->admin = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => Role::CompanyAdmin,
        ]);

        $this->worker = Worker::query()->create([
            'company_id' => $this->company->id,
            'employee_id' => 'BK-100245',
            'first_name' => 'James',
            'last_name' => 'Anderson',
            'status' => 'active',
        ]);
    }

    protected function training(array $attributes = []): TrainingRecord
    {
        return TrainingRecord::query()->create([
            'company_id' => $this->company->id,
            'worker_id' => $this->worker->id,
            'course_name' => 'WHMIS 2015',
            'category' => 'Safety',
            'is_required' => true,
            'status' => 'completed',
            'completed_at' => now()->subYear(),
            'expires_at' => now()->addYears(2),
            ...$attributes,
        ]);
    }

    public function test_it_shows_the_training_tab_by_default(): void
    {
        $this->training();

        $this->actingAs($this->admin)
            ->get(route('workers.show', $this->worker))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Workers/Show')
                ->where('tab', 'training')
                ->where('worker.full_name', 'James Anderson')
                ->has('training.records', 1));
    }

    public function test_compliance_percentage_only_counts_required_training(): void
    {
        $this->training(['course_name' => 'WHMIS 2015']);
        $this->training(['course_name' => 'Fall Protection', 'status' => 'not_started', 'completed_at' => null]);
        // Elective work must not drag the percentage down.
        $this->training(['course_name' => 'Leadership', 'is_required' => false, 'status' => 'not_started']);

        $this->actingAs($this->admin)
            ->get(route('workers.show', $this->worker))
            ->assertInertia(fn ($page) => $page
                ->where('training.summary.required_total', 2)
                ->where('training.summary.required_met', 1)
                ->where('training.summary.compliance_percent', 50)
                ->where('training.counts.elective', 1));
    }

    public function test_expired_training_is_not_compliant_and_is_flagged(): void
    {
        $this->training(['expires_at' => now()->subDay()]);

        $this->actingAs($this->admin)
            ->get(route('workers.show', $this->worker))
            ->assertInertia(fn ($page) => $page
                ->where('training.summary.expired', 1)
                ->where('training.summary.compliance_percent', 0)
                ->where('training.records.0.is_expired', true));
    }

    public function test_records_can_be_filtered_by_scope_and_search(): void
    {
        $this->training(['course_name' => 'WHMIS 2015']);
        $this->training(['course_name' => 'Leadership Basics', 'is_required' => false]);

        $this->actingAs($this->admin)
            ->get(route('workers.show', ['worker' => $this->worker, 'scope' => 'elective']))
            ->assertInertia(fn ($page) => $page
                ->has('training.records', 1)
                ->where('training.records.0.course_name', 'Leadership Basics'));

        $this->actingAs($this->admin)
            ->get(route('workers.show', ['worker' => $this->worker, 'training_search' => 'whmis']))
            ->assertInertia(fn ($page) => $page
                ->has('training.records', 1)
                ->where('training.records.0.course_name', 'WHMIS 2015'));
    }

    public function test_a_certificate_can_be_uploaded_against_a_training_record(): void
    {
        Storage::fake('public');
        $record = $this->training();

        $this->actingAs($this->admin)
            ->post(route('workers.certificates.store', $this->worker), [
                'file' => UploadedFile::fake()->create('first-aid.pdf', 200, 'application/pdf'),
                'training_record_id' => $record->id,
            ])
            ->assertRedirect();

        $certification = Certification::query()->firstOrFail();

        $this->assertSame('first-aid.pdf', $certification->file_name);
        $this->assertSame($this->admin->id, $certification->uploaded_by);
        $this->assertSame($certification->id, $record->fresh()->certification_id);
        Storage::disk('public')->assertExists($certification->file_path);
    }

    public function test_re_uploading_replaces_the_previous_file(): void
    {
        Storage::fake('public');
        $record = $this->training();

        $this->actingAs($this->admin)->post(route('workers.certificates.store', $this->worker), [
            'file' => UploadedFile::fake()->create('old.pdf', 100, 'application/pdf'),
            'training_record_id' => $record->id,
        ]);

        $original = Certification::query()->firstOrFail()->file_path;

        $this->actingAs($this->admin)->post(route('workers.certificates.store', $this->worker), [
            'file' => UploadedFile::fake()->create('new.pdf', 100, 'application/pdf'),
            'training_record_id' => $record->id,
        ]);

        $this->assertSame(1, Certification::query()->count());
        $this->assertSame('new.pdf', Certification::query()->firstOrFail()->file_name);
        Storage::disk('public')->assertMissing($original);
    }

    public function test_it_rejects_unsupported_certificate_files(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin)
            ->post(route('workers.certificates.store', $this->worker), [
                'file' => UploadedFile::fake()->create('notes.txt', 10, 'text/plain'),
            ])
            ->assertSessionHasErrors('file');

        $this->assertSame(0, Certification::query()->count());
    }

    public function test_another_company_cannot_view_the_worker(): void
    {
        $other = Company::query()->create([
            'name' => 'LodgeX',
            'code' => 'LOGX',
            'status' => 'active',
        ]);

        $intruder = User::factory()->create([
            'company_id' => $other->id,
            'role' => Role::CompanyAdmin,
        ]);

        $this->actingAs($intruder)
            ->get(route('workers.show', $this->worker))
            ->assertNotFound();
    }
}
