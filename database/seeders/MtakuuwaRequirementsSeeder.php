<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\ThematicArea;
use Illuminate\Database\Seeder;

/**
 * Idempotent implementation of the thematic-area names approved in the
 * MABORESHO YA MFUMO WA MTAKUWWA (2026) requirements document.
 * Code matching preserves existing indicator and assignment relationships.
 */
class MtakuuwaRequirementsSeeder extends Seeder
{
    private const NAMES = [
        'T1'  => ['1', 'Household Economic Strengthening', 'Kuimarisha Uchumi wa Kaya'],
        'T2'  => ['2', 'Norms and Value', 'Mila na Desturi'],
        'T3A' => ['3A', 'Safe Environment in Public Space', 'Mazingira Salama Katika Maeneo ya Umma'],
        'T3B' => ['3B', 'Safe Environment in Media, Information and Communication Technology', 'Mazingira Salama Katika Habari, Mawasiliano na Teknolojia ya Habari'],
        'T4'  => ['4', 'Parenting, Family Support and Relationships', 'Malezi, Kuimarisha Familia na Mahusiano'],
        'T5'  => ['5', 'Implementation and Enforcement of Laws', 'Usimamizi na Utekelezaji wa Sheria'],
        'T6'  => ['6', 'Response and Supportive Services', 'Huduma za Mwitikio'],
        'T7'  => ['7', 'Safe schools and life skills', 'Mazingira Salama Shuleni na Stadi za Maisha'],
        'T8'  => ['8', 'Coordination, Monitoring and Evaluation', 'Uratibu, Ufuatiliaji na Tathmini'],
    ];

    public function run(): void
    {
        $project = Project::query()->where('code', 'NPA-VAWC')->first();
        if (! $project) return;

        foreach (self::NAMES as $prefix => [$code, $en, $sw]) {
            $area = ThematicArea::query()->where('project_id', $project->id)
                ->where('official_code', $code)->first();
            $area ??= ThematicArea::query()->where('project_id', $project->id)
                ->whereHas('indicators', fn ($query) => $query->where('code', 'like', $prefix.'-%'))->first();
            if (! $area) {
                $this->command?->warn("No existing thematic area for {$code}; it was not fabricated without the plan hierarchy.");
                continue;
            }
            $area->update(['official_code' => $code, 'name' => $en, 'name_en' => $en, 'name_sw' => $sw]);
        }

        // Project coordinators automatically also coordinate thematic area 8.
        $coordination = ThematicArea::query()->where('project_id', $project->id)->where('official_code', '8')->first();
        if (! $coordination) return;
        foreach ($project->users()->wherePivot('is_active', true)->get() as $user) {
            if ($user->hasRole('Project Manager')) {
                $coordination->users()->syncWithoutDetaching([$user->id => ['is_active' => true]]);
            }
        }
    }
}
