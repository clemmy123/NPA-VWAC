<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\FinancialYear;
use App\Models\Indicator;
use App\Models\IndicatorDataEntry;
use App\Models\IndicatorTarget;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Region;
use App\Models\ReportingPeriod;
use App\Models\ThematicArea;
use App\Models\User;
use App\Services\ReportVisualizationService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeneralReportPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    public function test_unauthorized_user_cannot_open_the_general_report(): void
    {
        $user = $this->userWithRole('Data Entry User');

        $this->actingAs($user)->get(route('reports.general'))->assertForbidden();
    }

    public function test_authorized_user_sees_the_filter_bar_and_frequency_pills(): void
    {
        $user = $this->userWithRole('Super Admin');

        $response = $this->actingAs($user)->get(route('reports.general'));

        $response->assertOk();
        $response->assertSee('Monthly Reports');
        $response->assertSee('Plan');
        $response->assertSee('Thematic Area');
        $response->assertSee('Indicator');
        $response->assertSee('id="project_id"', false);
        $response->assertSee('id="thematic_area_id"', false);
        $response->assertSee('id="indicator_id"', false);
        $response->assertSee('All Indicators');
        $response->assertSee('minimumResultsForSearch', false);
        $response->assertSee('Filter');
        $response->assertSee('Reset');
        $response->assertSee('>Monthly</a>', false);
        $response->assertSee('>Quarterly</a>', false);
        $response->assertSee('>Yearly</a>', false);
        $response->assertSee('Apply filters to see analysis.');
        $response->assertDontSee('Monitoring & Evaluation');
    }

    public function test_monthly_report_shows_approved_actuals_for_the_selected_month_only(): void
    {
        $user = $this->userWithRole('Super Admin');
        $project = Project::factory()->create(['name' => 'NPA-VAWC']);
        $thematicArea = ThematicArea::factory()->create([
            'project_id' => $project->id,
            'name' => 'Household Economic Strengthening',
        ]);
        $indicator = Indicator::factory()->create([
            'thematic_area_id' => $thematicArea->id,
            'name' => 'Women reached with savings groups',
            'code' => 'HES-01',
            'aggregation_method' => 'sum',
        ]);
        $financialYear = FinancialYear::factory()->create([
            'name' => '2025/26',
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
            'is_current' => true,
        ]);
        IndicatorTarget::factory()->create([
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'target_value' => 1000,
        ]);
        IndicatorDataEntry::factory()->approved()->create([
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'entry_date' => '2025-12-10',
            'actual_value' => 400,
        ]);
        IndicatorDataEntry::factory()->approved()->create([
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'entry_date' => '2026-01-10',
            'actual_value' => 200,
        ]);
        IndicatorDataEntry::factory()->create([
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'entry_date' => '2025-12-15',
            'actual_value' => 999,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($user)->get(route('reports.general', [
            'frequency' => 'monthly',
            'month' => '2025-12-01',
            'project_id' => $project->id,
            'thematic_area_id' => $thematicArea->id,
            'visualization' => 'bar',
            'apply' => 1,
        ]));

        $response->assertOk();
        $response->assertSee('Women reached with savings groups');
        $response->assertSee('400</td>', false);
        $response->assertDontSee('200</td>', false);
        $response->assertDontSee('999</td>', false);
        $response->assertSee('40%');
        $response->assertSee('Monitoring & Evaluation');
        $response->assertSee('Off track');
        $response->assertSee('id="visualization"', false);
        $response->assertSee('id="report-visual-chart"', false);
        $response->assertSee("type: 'horizontalBar'", false);
        $response->assertSee("hoverBackgroundColor: '#2563eb'", false);
        $response->assertSee('#188ae2', false);
        $response->assertSee('#dc2626', false);
        $response->assertSee('#22c55e', false);
        $response->assertSee('gridLines: { display: false }', false);
        $response->assertDontSee('rgba(59, 130, 246, 0.35)', false);
        $response->assertDontSee('id="report-achievement-chart"', false);
        $this->assertSame('bar', $response->viewData('visualization')['type']);
    }

    public function test_visualization_filter_offers_every_visual_type(): void
    {
        $response = $this->actingAs($this->userWithRole('Super Admin'))->get(route('reports.general'));

        $response->assertOk();
        $response->assertSee('id="visualization"', false);

        foreach (['Map', 'Bar chart', 'List', 'Histogram', 'Pie chart', 'Doughnut chart', 'Line chart', 'Area chart', 'Radar chart'] as $label) {
            $response->assertSee('>'.$label.'</option>', false);
        }
    }

    public function test_each_visualization_type_summarizes_the_report(): void
    {
        $this->travelTo('2026-01-20');
        $fixture = $this->visualizationFixture();

        $visual = fn (string $type): array => $this->actingAs($fixture['user'])
            ->get(route('reports.general', $fixture['query'] + ['visualization' => $type]))
            ->assertOk()
            ->viewData('visualization');

        $list = $visual('list');
        $this->assertSame(['off-track', 'at-risk', 'on-track', 'no-data'], array_column($list['groups'], 'key'));
        $this->assertSame([1, 1, 1, 1], array_map(fn (array $group): int => count($group['items']), $list['groups']));

        $histogram = $visual('histogram');
        $this->assertSame([1, 0, 1, 0, 1], $histogram['chart']['values']);
        $this->assertStringContainsString('1 have no score yet', $histogram['caption']);

        $this->assertSame([1, 1, 1, 1], $visual('pie')['chart']['values']);
        $this->assertSame('doughnut', $visual('doughnut')['type']);

        $line = $visual('line');
        $this->assertSame(['Jul 2025', 'Aug 2025', 'Sep 2025', 'Oct 2025', 'Nov 2025', 'Dec 2025', 'Jan 2026'], $line['chart']['labels']);
        $this->assertNull($line['chart']['values'][0]);
        $this->assertEqualsWithDelta(23.3, $line['chart']['values'][5], 0.1);
        $this->assertEqualsWithDelta(63.3, $line['chart']['values'][6], 0.1);

        $area = $visual('area');
        $this->assertSame([0, 0, 0, 0, 0, 3, 1], $area['chart']['values']);

        $radar = $visual('radar');
        $this->assertNull($radar['empty']);
        $this->assertCount(3, $radar['chart']['labels']);

        $this->actingAs($fixture['user'])
            ->get(route('reports.general', $fixture['query'] + ['visualization' => 'list']))
            ->assertSee('Indicator summary')
            ->assertDontSee('id="report-visual-chart"', false);

        $this->actingAs($fixture['user'])
            ->get(route('reports.general', $fixture['query'] + ['visualization' => 'area']))
            ->assertSee('id="report-visual-chart"', false)
            ->assertSee('fill: true', false);
    }

    public function test_visualization_card_offers_a_pdf_export_carrying_the_filters(): void
    {
        $this->travelTo('2026-01-20');
        $fixture = $this->visualizationFixture();

        $this->actingAs($fixture['user'])
            ->get(route('reports.general', $fixture['query'] + ['visualization' => 'pie']))
            ->assertOk()
            ->assertSee('action="'.route('reports.general.pdf').'"', false)
            ->assertSee('name="visualization" value="pie"', false)
            ->assertSee('name="thematic_area_id" value="'.$fixture['query']['thematic_area_id'].'"', false)
            ->assertSee('Export PDF');
    }

    public function test_pdf_export_downloads_a_report_for_each_visualization(): void
    {
        $this->travelTo('2026-01-20');
        $fixture = $this->visualizationFixture();
        $pixel = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        foreach (array_keys(ReportVisualizationService::TYPES) as $type) {
            $response = $this->actingAs($fixture['user'])
                ->post(route('reports.general.pdf'), $fixture['query'] + ['visualization' => $type, 'chart_image' => $pixel]);

            $response->assertOk()->assertHeader('content-type', 'application/pdf');
            $this->assertStringStartsWith('%PDF', $response->getContent(), "{$type} export is not a PDF");
            $this->assertStringContainsString("-{$type}.pdf", $response->headers->get('content-disposition'));
        }
    }

    public function test_pdf_export_rejects_a_chart_image_that_is_not_a_png_data_uri(): void
    {
        $this->travelTo('2026-01-20');
        $fixture = $this->visualizationFixture();

        $this->actingAs($fixture['user'])
            ->post(route('reports.general.pdf'), $fixture['query'] + ['chart_image' => 'https://example.com/chart.png'])
            ->assertSessionHasErrors('chart_image');
    }

    public function test_list_visualization_collapses_long_groups_behind_show_more(): void
    {
        $this->travelTo('2026-01-20');
        $fixture = $this->visualizationFixture();
        $thematicAreaId = $fixture['query']['thematic_area_id'];

        foreach (range(1, 6) as $number) {
            Indicator::factory()->create([
                'thematic_area_id' => $thematicAreaId,
                'code' => "EMPTY{$number}",
                'aggregation_method' => 'sum',
            ]);
        }

        $this->actingAs($fixture['user'])
            ->get(route('reports.general', $fixture['query'] + ['visualization' => 'list']))
            ->assertOk()
            ->assertSee('class="me-visual-more"', false)
            ->assertSee('Show 2 more')
            ->assertSee('Show less');
    }

    public function test_default_visualization_is_a_tanzania_map_of_collections_by_region(): void
    {
        $this->travelTo('2026-01-20');
        $fixture = $this->visualizationFixture();
        $dar = Region::factory()->create(['name' => 'Dar es Salaam']);
        $mwanza = Region::factory()->create(['name' => 'Mwanza']);
        $district = District::factory()->create(['region_id' => $dar->region_id]);
        $mwanzaDesk = Organization::factory()->create(['location_level' => 'region', 'location_id' => $mwanza->region_id]);
        $indicatorId = Indicator::where('code', 'RISK')->value('id');
        $financialYearId = $fixture['query']['financial_year_id'];

        IndicatorDataEntry::factory()->approved()->count(2)->create([
            'indicator_id' => $indicatorId,
            'financial_year_id' => $financialYearId,
            'entry_date' => '2025-11-10',
            'location_level' => 'district',
            'location_id' => $district->district_id,
            'actual_value' => 0,
        ]);
        IndicatorDataEntry::factory()->approved()->create([
            'indicator_id' => $indicatorId,
            'financial_year_id' => $financialYearId,
            'entry_date' => '2025-11-10',
            'organization_id' => $mwanzaDesk->id,
            'actual_value' => 0,
        ]);
        IndicatorDataEntry::factory()->create([
            'indicator_id' => $indicatorId,
            'financial_year_id' => $financialYearId,
            'entry_date' => '2025-11-10',
            'location_level' => 'region',
            'location_id' => $mwanza->region_id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($fixture['user'])->get(route('reports.general', $fixture['query']));

        $response->assertOk();
        $response->assertSee('Collections by region');
        $response->assertSee('class="me-map-svg"', false);
        $response->assertSee('data-region="Songwe"', false);
        $response->assertSee('data-region="Dar es Salaam"', false);
        $response->assertSee('data-share="66.7"', false);
        $response->assertSee('window.ReportTip', false);
        $response->assertSee('ReportTip.show', false);
        $response->assertSee('<g class="me-map-score has-score"', false);
        $response->assertSee('class="me-map-lake"', false);
        $response->assertSee('Lake Victoria');
        $response->assertSee('Lake Tanganyika');
        $response->assertSee('Lake Nyasa');
        $response->assertSee('<text y="13.5" class="me-map-badge-value">2</text>', false);
        $response->assertSee('Not tracked in this system');
        $response->assertSee('Share of collections');
        $response->assertSee('4 approved collections have no location set.');
        $response->assertDontSee('id="report-visual-chart"', false);

        $map = $response->viewData('visualization')['map'];
        $this->assertSame('map', $response->viewData('visualization')['type']);
        $this->assertCount(31, $map['regions']);
        $this->assertSame([
            ['name' => 'Dar es Salaam', 'collections' => 2, 'indicators' => 1],
            ['name' => 'Mwanza', 'collections' => 1, 'indicators' => 1],
        ], $map['ranking']);
        $regions = collect($map['regions'])->keyBy('name');
        $this->assertSame(5, $regions['Dar es Salaam']['shade']);
        $this->assertSame(1, $regions['Dar es Salaam']['rank']);
        $this->assertSame(66.7, $regions['Dar es Salaam']['share']);
        $this->assertSame(2, $regions['Mwanza']['rank']);
        $this->assertNull($regions['Arusha']['rank']);
        $this->assertFalse($regions['Kusini Pemba']['tracked']);
        $this->assertSame(3, $regions['Mwanza']['shade']);
        $this->assertFalse($regions['Arusha']['tracked']);
    }

    public function test_unknown_visualization_type_is_rejected(): void
    {
        $this->actingAs($this->userWithRole('Super Admin'))
            ->get(route('reports.general', ['visualization' => 'sparkles', 'apply' => 1]))
            ->assertSessionHasErrors('visualization');
    }

    /**
     * Four indicators: on track (120%), at risk (60%), off track (10%) and one with no data.
     *
     * @return array{user: User, query: array<string, mixed>}
     */
    private function visualizationFixture(): array
    {
        $user = $this->userWithRole('Super Admin');
        $project = Project::factory()->create();
        $thematicArea = ThematicArea::factory()->create(['project_id' => $project->id]);
        $financialYear = FinancialYear::factory()->create([
            'name' => '2025/26',
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
            'is_current' => true,
        ]);

        foreach ([['ON', 120], ['RISK', 60], ['OFF', 10], ['NONE', null]] as [$code, $actual]) {
            $indicator = Indicator::factory()->create([
                'thematic_area_id' => $thematicArea->id,
                'code' => $code,
                'aggregation_method' => 'sum',
            ]);
            IndicatorTarget::factory()->create([
                'indicator_id' => $indicator->id,
                'financial_year_id' => $financialYear->id,
                'target_value' => 100,
            ]);

            if ($actual !== null) {
                IndicatorDataEntry::factory()->approved()->create([
                    'indicator_id' => $indicator->id,
                    'financial_year_id' => $financialYear->id,
                    'entry_date' => $code === 'ON' ? '2026-01-05' : '2025-12-05',
                    'actual_value' => $actual,
                ]);
            }
        }

        IndicatorDataEntry::factory()->approved()->create([
            'indicator_id' => Indicator::where('code', 'ON')->value('id'),
            'financial_year_id' => $financialYear->id,
            'entry_date' => '2025-12-01',
            'actual_value' => 0,
        ]);

        return [
            'user' => $user,
            'query' => [
                'frequency' => 'yearly',
                'financial_year_id' => $financialYear->id,
                'project_id' => $project->id,
                'thematic_area_id' => $thematicArea->id,
                'apply' => 1,
            ],
        ];
    }

    public function test_quarterly_and_yearly_tabs_swap_the_period_filter(): void
    {
        $user = $this->userWithRole('Super Admin');

        $this->actingAs($user)
            ->get(route('reports.general', ['frequency' => 'quarterly']))
            ->assertOk()
            ->assertSee('Quarterly Reports')
            ->assertSee('>Quarter</label>', false);

        $this->actingAs($user)
            ->get(route('reports.general', ['frequency' => 'yearly']))
            ->assertOk()
            ->assertSee('Yearly Reports')
            ->assertSee('>Year</label>', false);
    }

    public function test_project_manager_can_open_the_general_report(): void
    {
        $user = $this->userWithRole('Project Manager');

        $this->actingAs($user)->get(route('reports.general'))->assertOk();
    }

    public function test_yearly_report_sums_approved_actuals_across_the_financial_year(): void
    {
        $user = $this->userWithRole('Super Admin');
        $project = Project::factory()->create();
        $thematicArea = ThematicArea::factory()->create(['project_id' => $project->id]);
        $indicator = Indicator::factory()->create([
            'thematic_area_id' => $thematicArea->id,
            'name' => 'Women reached with savings groups',
            'aggregation_method' => 'sum',
        ]);
        $financialYear = FinancialYear::factory()->create([
            'name' => '2025/26',
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
            'is_current' => true,
        ]);
        IndicatorTarget::factory()->create([
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'target_value' => 1000,
        ]);
        IndicatorDataEntry::factory()->approved()->create([
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'entry_date' => '2025-12-10',
            'actual_value' => 400,
        ]);
        IndicatorDataEntry::factory()->approved()->create([
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'entry_date' => '2026-01-10',
            'actual_value' => 200,
        ]);

        $response = $this->actingAs($user)->get(route('reports.general', [
            'frequency' => 'yearly',
            'financial_year_id' => $financialYear->id,
            'project_id' => $project->id,
            'thematic_area_id' => $thematicArea->id,
            'apply' => 1,
        ]));

        $response->assertOk();
        $response->assertSee('600</td>', false);
        $response->assertSee('60%');
        $response->assertDontSee('Previous Result');
    }

    public function test_quarterly_report_uses_the_period_target_and_period_actuals(): void
    {
        $user = $this->userWithRole('Super Admin');
        $project = Project::factory()->create();
        $thematicArea = ThematicArea::factory()->create(['project_id' => $project->id]);
        $indicator = Indicator::factory()->create([
            'thematic_area_id' => $thematicArea->id,
            'name' => 'Women reached with savings groups',
            'aggregation_method' => 'sum',
        ]);
        $financialYear = FinancialYear::factory()->create([
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
            'is_current' => true,
        ]);
        $period = ReportingPeriod::factory()->create([
            'financial_year_id' => $financialYear->id,
            'name' => 'Q2',
            'start_date' => '2025-10-01',
            'end_date' => '2025-12-31',
        ]);
        IndicatorTarget::factory()->create([
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'reporting_period_id' => $period->id,
            'target_value' => 500,
        ]);
        IndicatorDataEntry::factory()->approved()->create([
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'reporting_period_id' => $period->id,
            'entry_date' => '2025-11-10',
            'actual_value' => 250,
        ]);
        IndicatorDataEntry::factory()->approved()->create([
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'entry_date' => '2026-01-10',
            'actual_value' => 800,
        ]);

        $response = $this->actingAs($user)->get(route('reports.general', [
            'frequency' => 'quarterly',
            'reporting_period_id' => $period->id,
            'project_id' => $project->id,
            'thematic_area_id' => $thematicArea->id,
            'apply' => 1,
        ]));

        $response->assertOk();
        $response->assertSee('250</td>', false);
        $response->assertDontSee('800</td>', false);
        $response->assertSee('50%');
    }

    public function test_all_thematic_areas_summarizes_the_chart_and_compacts_alerts(): void
    {
        $user = $this->userWithRole('Super Admin');
        $project = Project::factory()->create(['name' => 'NPA-VAWC']);
        $household = ThematicArea::factory()->create([
            'project_id' => $project->id,
            'name' => 'Household Economic Strengthening',
        ]);
        $protection = ThematicArea::factory()->create([
            'project_id' => $project->id,
            'name' => 'Child Protection',
        ]);
        $financialYear = FinancialYear::factory()->create([
            'name' => '2025/26',
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
            'is_current' => true,
        ]);

        foreach (range(1, 5) as $index) {
            $indicator = Indicator::factory()->create([
                'thematic_area_id' => $household->id,
                'name' => 'Household indicator '.$index,
                'code' => sprintf('HES-%02d', $index),
                'aggregation_method' => 'sum',
            ]);
            IndicatorTarget::factory()->create([
                'indicator_id' => $indicator->id,
                'financial_year_id' => $financialYear->id,
                'target_value' => 100,
            ]);
            IndicatorDataEntry::factory()->approved()->create([
                'indicator_id' => $indicator->id,
                'financial_year_id' => $financialYear->id,
                'entry_date' => '2025-12-10',
                'actual_value' => 20,
            ]);
        }

        $protectionIndicator = Indicator::factory()->create([
            'thematic_area_id' => $protection->id,
            'name' => 'Cases managed',
            'code' => 'CP-01',
            'aggregation_method' => 'sum',
        ]);
        IndicatorTarget::factory()->create([
            'indicator_id' => $protectionIndicator->id,
            'financial_year_id' => $financialYear->id,
            'target_value' => 100,
        ]);
        IndicatorDataEntry::factory()->approved()->create([
            'indicator_id' => $protectionIndicator->id,
            'financial_year_id' => $financialYear->id,
            'entry_date' => '2025-12-10',
            'actual_value' => 80,
        ]);

        $response = $this->actingAs($user)->get(route('reports.general', [
            'frequency' => 'monthly',
            'month' => '2025-12-01',
            'project_id' => $project->id,
            'visualization' => 'bar',
            'apply' => 1,
        ]));

        $response->assertOk();
        $response->assertSee('Average achievement by thematic area');
        $response->assertSee('across all thematic areas');
        $response->assertSee('Household Economic Strengthening');
        $response->assertSee('Child Protection');
        $response->assertDontSee('Achievement by indicator');
        $response->assertSee('Open one area for indicator bars.');
    }

    public function test_indicator_results_table_is_paginated(): void
    {
        $user = $this->userWithRole('Super Admin');
        $project = Project::factory()->create();
        $thematicArea = ThematicArea::factory()->create(['project_id' => $project->id]);
        $financialYear = FinancialYear::factory()->create([
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
            'is_current' => true,
        ]);

        foreach (range(1, 16) as $index) {
            $indicator = Indicator::factory()->create([
                'thematic_area_id' => $thematicArea->id,
                'name' => 'Paginated indicator '.$index,
                'code' => sprintf('ALT-%02d', $index),
                'aggregation_method' => 'sum',
            ]);
            IndicatorTarget::factory()->create([
                'indicator_id' => $indicator->id,
                'financial_year_id' => $financialYear->id,
                'target_value' => 100,
            ]);
            IndicatorDataEntry::factory()->approved()->create([
                'indicator_id' => $indicator->id,
                'financial_year_id' => $financialYear->id,
                'entry_date' => '2025-12-10',
                'actual_value' => 20,
            ]);
        }

        $pageOne = $this->actingAs($user)->get(route('reports.general', [
            'frequency' => 'monthly',
            'month' => '2025-12-01',
            'project_id' => $project->id,
            'thematic_area_id' => $thematicArea->id,
            'visualization' => 'bar',
            'apply' => 1,
        ]));

        $pageOne->assertOk();
        $pageOne->assertSee('id="results"', false);
        $pageOne->assertSee('Achievement by indicator');
        $pageOne->assertSee('Off track');
        $pageOne->assertDontSee('Lowest achievement');
        $pageOne->assertDontSee('Achievement by status');
        $pageOne->assertSee('>Paginated indicator 1</div>', false);
        $pageOne->assertDontSee('>Paginated indicator 11</div>', false);
        $pageOne->assertSee('page=2', false);
        $pageOne->assertSee('#results', false);
        $pageOne->assertSee('Showing');
        $pageOne->assertSee('>10</span>', false);
        $pageOne->assertSee('>16</span>', false);
        $pageOne->assertDontSee('>Issue</th>', false);
        $pageOne->assertDontSee('id="alerts"', false);

        $pageTwo = $this->actingAs($user)->get(route('reports.general', [
            'frequency' => 'monthly',
            'month' => '2025-12-01',
            'project_id' => $project->id,
            'thematic_area_id' => $thematicArea->id,
            'apply' => 1,
            'page' => 2,
        ]));

        $pageTwo->assertOk();
        $pageTwo->assertSee('>Paginated indicator 16</div>', false);
        $pageTwo->assertDontSee('>Paginated indicator 1</div>', false);
    }

    public function test_indicator_results_table_lists_off_track_rows_first(): void
    {
        $user = $this->userWithRole('Super Admin');
        $project = Project::factory()->create();
        $thematicArea = ThematicArea::factory()->create(['project_id' => $project->id]);
        $financialYear = FinancialYear::factory()->create([
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
            'is_current' => true,
        ]);

        $onTrack = Indicator::factory()->create([
            'thematic_area_id' => $thematicArea->id,
            'name' => 'AAA on track indicator',
            'code' => 'AAA-01',
            'aggregation_method' => 'sum',
        ]);
        $atRisk = Indicator::factory()->create([
            'thematic_area_id' => $thematicArea->id,
            'name' => 'MMM at risk indicator',
            'code' => 'MMM-01',
            'aggregation_method' => 'sum',
        ]);
        $offTrack = Indicator::factory()->create([
            'thematic_area_id' => $thematicArea->id,
            'name' => 'ZZZ off track indicator',
            'code' => 'ZZZ-01',
            'aggregation_method' => 'sum',
        ]);

        foreach ([
            [$onTrack, 120],
            [$atRisk, 60],
            [$offTrack, 20],
        ] as [$indicator, $actual]) {
            IndicatorTarget::factory()->create([
                'indicator_id' => $indicator->id,
                'financial_year_id' => $financialYear->id,
                'target_value' => 100,
            ]);
            IndicatorDataEntry::factory()->approved()->create([
                'indicator_id' => $indicator->id,
                'financial_year_id' => $financialYear->id,
                'entry_date' => '2025-12-10',
                'actual_value' => $actual,
            ]);
        }

        $response = $this->actingAs($user)->get(route('reports.general', [
            'frequency' => 'monthly',
            'month' => '2025-12-01',
            'project_id' => $project->id,
            'thematic_area_id' => $thematicArea->id,
            'apply' => 1,
        ]));

        $response->assertOk();
        $response->assertSeeInOrder([
            '>ZZZ off track indicator</div>',
            '>MMM at risk indicator</div>',
            '>AAA on track indicator</div>',
        ], false);
        $response->assertSee('s-achievement', false);
    }

    public function test_selected_indicator_scopes_the_general_report(): void
    {
        $user = $this->userWithRole('Super Admin');
        $project = Project::factory()->create();
        $thematicArea = ThematicArea::factory()->create(['project_id' => $project->id]);
        $shown = Indicator::factory()->create([
            'thematic_area_id' => $thematicArea->id,
            'name' => 'Shown savings groups',
            'code' => 'HES-01',
            'aggregation_method' => 'sum',
        ]);
        Indicator::factory()->create([
            'thematic_area_id' => $thematicArea->id,
            'name' => 'Hidden grants issued',
            'code' => 'HES-02',
            'aggregation_method' => 'sum',
        ]);
        $financialYear = FinancialYear::factory()->create([
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
            'is_current' => true,
        ]);
        IndicatorTarget::factory()->create([
            'indicator_id' => $shown->id,
            'financial_year_id' => $financialYear->id,
            'target_value' => 100,
        ]);
        IndicatorDataEntry::factory()->approved()->create([
            'indicator_id' => $shown->id,
            'financial_year_id' => $financialYear->id,
            'entry_date' => '2025-12-10',
            'actual_value' => 40,
        ]);

        $response = $this->actingAs($user)->get(route('reports.general', [
            'frequency' => 'monthly',
            'month' => '2025-12-01',
            'project_id' => $project->id,
            'thematic_area_id' => $thematicArea->id,
            'indicator_id' => $shown->id,
            'apply' => 1,
        ]));

        $response->assertOk();
        $response->assertSee('this indicator');
        $response->assertSee('>Shown savings groups</div>', false);
        $response->assertDontSee('>Hidden grants issued</div>', false);
        $response->assertSee('HES-01 · Shown savings groups');
    }
}
