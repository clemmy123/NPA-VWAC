<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ThematicArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThematicAreaLocalizedNameTest extends TestCase
{
    use RefreshDatabase;

    public function test_localized_name_follows_the_locale_and_falls_back_to_the_english_name(): void
    {
        $area = ThematicArea::factory()->create(['name' => 'Norms and Value', 'name_sw' => 'Mila na Desturi']);

        app()->setLocale('en');
        $this->assertSame('Norms and Value', $area->localized_name);

        app()->setLocale('sw');
        $this->assertSame('Mila na Desturi', $area->localized_name);

        $area->update(['name_sw' => null]);
        $this->assertSame('Norms and Value', $area->fresh()->localized_name);
    }

    public function test_name_en_mirrors_name_unless_set_explicitly(): void
    {
        $area = ThematicArea::factory()->create(['project_id' => Project::factory()->create()->id, 'name' => 'First']);
        $this->assertSame('First', $area->name_en);

        $area->update(['name' => 'Second']);
        $this->assertSame('Second', $area->fresh()->name_en);
    }
}
