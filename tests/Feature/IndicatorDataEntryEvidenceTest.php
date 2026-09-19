<?php

namespace Tests\Feature;

use App\Models\FinancialYear;
use App\Models\Indicator;
use App\Models\IndicatorDataAssignment;
use App\Models\IndicatorDataEntry;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IndicatorDataEntryEvidenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        Storage::fake('local');
    }

    /**
     * `UploadedFile::fake()->create()` fakes the reported size but leaves the temp
     * file empty, which trips Media Library's real (finfo-based) MIME sniffing on the
     * `evidence` collection's `acceptsMimeTypes` check. Write minimal real PDF bytes so
     * the file is genuinely detected as `application/pdf`.
     */
    private function fakePdf(string $name = 'proof.pdf'): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'pdf');
        file_put_contents($path, "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n1 0 obj<< /Type /Catalog >>endobj\n%%EOF");

        return new UploadedFile($path, $name, 'application/pdf', null, true);
    }

    private function assignedDataEntryUser(Indicator $indicator): User
    {
        $indicator->update(['status' => 'active']);
        $user = User::factory()->create();
        $user->assignRole('Data Entry User');
        IndicatorDataAssignment::factory()->create([
            'indicator_id' => $indicator->id,
            'user_id' => $user->id,
            'location_level' => null,
            'location_id' => null,
        ]);

        return $user;
    }

    public function test_evidence_can_be_left_empty_when_the_indicator_supports_it(): void
    {
        $indicator = Indicator::factory()->create(['requires_evidence' => true]);
        $financialYear = FinancialYear::factory()->started()->create();
        $entrant = $this->assignedDataEntryUser($indicator);

        $response = $this->actingAs($entrant)->post('/indicator-data-entries', [
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'entry_date' => now()->toDateString(),
            'actual_value' => 0,
        ]);

        $response->assertRedirect(route('indicator-data-entries.index'));
        $response->assertSessionDoesntHaveErrors();
    }

    public function test_uploading_evidence_attaches_it_to_the_entry(): void
    {
        $indicator = Indicator::factory()->create(['requires_evidence' => true]);
        $financialYear = FinancialYear::factory()->started()->create();
        $entrant = $this->assignedDataEntryUser($indicator);
        $file = $this->fakePdf();

        $response = $this->actingAs($entrant)->post('/indicator-data-entries', [
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'entry_date' => now()->toDateString(),
            'actual_value' => 0,
            'evidence' => [$file],
        ]);

        $response->assertRedirect(route('indicator-data-entries.index'));
        $entry = IndicatorDataEntry::firstOrFail();
        $this->assertCount(1, $entry->getMedia('evidence'));
        $this->assertSame('proof.pdf', $entry->getFirstMedia('evidence')->file_name);
    }

    public function test_rejects_a_disallowed_file_type(): void
    {
        $indicator = Indicator::factory()->create();
        $financialYear = FinancialYear::factory()->started()->create();
        $entrant = $this->assignedDataEntryUser($indicator);
        $file = UploadedFile::fake()->create('script.exe', 10, 'application/octet-stream');

        $response = $this->actingAs($entrant)->post('/indicator-data-entries', [
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'entry_date' => now()->toDateString(),
            'actual_value' => 0,
            'evidence' => [$file],
        ]);

        $response->assertSessionHasErrors('evidence.0');
    }

    public function test_updating_an_entry_with_existing_evidence_does_not_require_a_new_upload(): void
    {
        $indicator = Indicator::factory()->create(['requires_evidence' => true]);
        $entrant = $this->assignedDataEntryUser($indicator);
        $entry = IndicatorDataEntry::factory()->create([
            'indicator_id' => $indicator->id,
            'entered_by' => $entrant->id,
        ]);
        $entry->addMedia($this->fakePdf())->toMediaCollection('evidence');

        $response = $this->actingAs($entrant)->put("/indicator-data-entries/{$entry->id}", [
            'remarks' => 'Just updating remarks.',
        ]);

        $response->assertSessionDoesntHaveErrors();
    }

    public function test_downloading_evidence_streams_the_file(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');
        $indicator = Indicator::factory()->create();
        $entry = IndicatorDataEntry::factory()->create(['indicator_id' => $indicator->id, 'entered_by' => $admin->id]);
        $media = $entry->addMedia($this->fakePdf())->toMediaCollection('evidence');

        $response = $this->actingAs($admin)->get(route('indicator-data-entries.evidence.download', [$entry, $media]));

        $response->assertOk();
        $response->assertHeader('content-disposition');
    }

    public function test_removing_evidence_from_a_draft_entry_succeeds(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');
        $indicator = Indicator::factory()->create();
        $entry = IndicatorDataEntry::factory()->create(['indicator_id' => $indicator->id, 'entered_by' => $admin->id, 'status' => 'draft']);
        $media = $entry->addMedia($this->fakePdf())->toMediaCollection('evidence');

        $response = $this->actingAs($admin)->delete(route('indicator-data-entries.evidence.destroy', [$entry, $media]));

        $response->assertRedirect();
        $this->assertCount(0, $entry->fresh()->getMedia('evidence'));
    }

    public function test_removing_evidence_from_a_submitted_entry_is_forbidden(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');
        $indicator = Indicator::factory()->create();
        $entry = IndicatorDataEntry::factory()->submitted()->create(['indicator_id' => $indicator->id, 'entered_by' => $admin->id]);
        $media = $entry->addMedia($this->fakePdf())->toMediaCollection('evidence');

        $response = $this->actingAs($admin)->delete(route('indicator-data-entries.evidence.destroy', [$entry, $media]));

        $response->assertForbidden();
        $this->assertCount(1, $entry->fresh()->getMedia('evidence'));
    }
}
