<?php

namespace App\Http\Controllers;

use App\Models\FinancialYear;
use App\Models\IndicatorPlan;
use App\Models\Organization;
use App\Models\ThematicArea;
use App\Services\IndicatorPlanService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

/** Annual planning worksheet and its authorization-aware review actions. */
class IndicatorPlanController extends Controller
{
    public function __construct(private readonly IndicatorPlanService $plans) {}

    public function index(Request $request, ThematicArea $thematicArea): View
    {
        $user = $request->user();
        abort_unless($user->hasRole('Super Admin') || in_array($thematicArea->id, $user->visibleThematicAreaIds(), true), 403);
        $financialYearId = $request->integer('financial_year_id') ?: FinancialYear::query()->where('is_current', true)->value('id');
        $indicators = $thematicArea->indicators()
            ->with([
                'plans' => fn ($query) => $query->where('financial_year_id', $financialYearId),
                'plans.leadOrganization', 'plans.media', 'unitOfMeasure', 'interventions',
            ])
            ->orderBy('name')->get();

        return view('indicator-plans.index', [
            'thematicArea' => $thematicArea,
            'indicators' => $indicators,
            'financialYears' => FinancialYear::query()->orderByDesc('start_date')->get(),
            'financialYearId' => $financialYearId,
            'organizations' => Organization::query()->where('is_active', true)->orderBy('name')->get(),
            'canManage' => $user->can('indicator.set-target') || $user->hasRole('Project Manager') || $user->hasRole('Super Admin'),
        ]);
    }

    public function store(Request $request, ThematicArea $thematicArea): RedirectResponse
    {
        $data = $request->validate([
            'indicator_id' => ['required', 'integer', Rule::exists('indicators', 'id')->where('thematic_area_id', $thematicArea->id)],
            'financial_year_id' => ['required', 'integer', 'exists:financial_years,id'],
            'planned_activity' => ['nullable', 'string', 'max:5000'],
            'baseline_year' => ['nullable', 'integer', 'between:2000,2100'],
            'baseline_value' => ['nullable', 'numeric', 'min:0'],
            'annual_target' => ['required', 'numeric', 'min:0'],
            'domestic_budget' => ['required', 'numeric', 'min:0'],
            'external_budget' => ['required', 'numeric', 'min:0'],
            'lead_organization_id' => ['required', 'integer', 'exists:organizations,id'],
            'participating_organization_ids' => ['nullable', 'array'],
            'participating_organization_ids.*' => ['integer', 'distinct', 'exists:organizations,id'],
        ]);
        $this->plans->save($data, $request->user());

        return redirect()->route('indicator-plans.index', [$thematicArea, 'financial_year_id' => $data['financial_year_id']])
            ->with('success', __('Annual indicator plan saved. Submit for institutional confirmation when ready.'));
    }

    public function action(Request $request, IndicatorPlan $indicatorPlan): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['submit', 'confirm', 'approve', 'return'])],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);
        $this->plans->transition($indicatorPlan, $request->user(), $data['action'], $data['comment'] ?? null);

        return redirect()->route('indicator-plans.index', [$indicatorPlan->indicator->thematicArea, 'financial_year_id' => $indicatorPlan->financial_year_id])
            ->with('success', __('Plan workflow updated.'));
    }

    /** Apply one workflow action to every plan in the area/year it is valid for; skips the ones the user may not act on. */
    public function bulkAction(Request $request, ThematicArea $thematicArea): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['confirm', 'approve', 'return'])],
            'financial_year_id' => ['required', 'integer', 'exists:financial_years,id'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);
        $from = ['confirm' => ['pending_institution'], 'approve' => ['pending_thematic'], 'return' => ['pending_institution', 'pending_thematic']][$data['action']];

        $plans = IndicatorPlan::query()
            ->whereHas('indicator', fn ($query) => $query->where('thematic_area_id', $thematicArea->id))
            ->where('financial_year_id', $data['financial_year_id'])
            ->whereIn('status', $from)
            ->get();

        $done = 0;
        foreach ($plans as $plan) {
            try {
                $this->plans->transition($plan, $request->user(), $data['action'], $data['comment'] ?? null);
                $done++;
            } catch (AuthorizationException|HttpException) {
                continue;
            }
        }

        return redirect()->route('indicator-plans.index', [$thematicArea, 'financial_year_id' => $data['financial_year_id']])
            ->with($done > 0 ? 'success' : 'error', $done > 0
                ? trans_choice(':count plan updated.|:count plans updated.', $done, ['count' => $done])
                : __('None of the plans can be actioned by you right now.'));
    }

    public function uploadDocument(Request $request, IndicatorPlan $indicatorPlan): RedirectResponse
    {
        $this->plans->authorizeThematic($request->user(), (int) $indicatorPlan->indicator->thematic_area_id);
        abort_unless(in_array($indicatorPlan->status, ['draft', 'returned'], true), 403);
        $data = $request->validate(['document' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx']]);
        $indicatorPlan->addMediaFromRequest('document')->toMediaCollection('supporting-documents');

        return back()->with('success', __('Supporting document attached.'));
    }

    public function downloadDocument(Request $request, IndicatorPlan $indicatorPlan, Media $media): BinaryFileResponse
    {
        $user = $request->user();
        $mayView = $user->hasRole('Super Admin')
            || in_array((int) $indicatorPlan->indicator->thematic_area_id, $user->visibleThematicAreaIds(), true)
            || ($user->can('institution.confirm') && (int) $user->organization_id === (int) $indicatorPlan->lead_organization_id);
        abort_unless($mayView, 403);
        abort_unless($media->model_type === IndicatorPlan::class && (int) $media->model_id === (int) $indicatorPlan->id
            && $media->collection_name === 'supporting-documents', 404);

        return response()->download($media->getPath(), $media->file_name);
    }

    public function deleteDocument(Request $request, IndicatorPlan $indicatorPlan, Media $media): RedirectResponse
    {
        $this->plans->authorizeThematic($request->user(), (int) $indicatorPlan->indicator->thematic_area_id);
        abort_unless(in_array($indicatorPlan->status, ['draft', 'returned'], true), 403);
        abort_unless($media->model_type === IndicatorPlan::class && (int) $media->model_id === (int) $indicatorPlan->id
            && $media->collection_name === 'supporting-documents', 404);
        $media->delete();

        return back()->with('success', __('Supporting document removed.'));
    }
}
