<?php

namespace App\Http\Controllers;

use App\Models\IndicatorDataEntry;
use App\Models\IndicatorPlan;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Institution-level ownership queue; never lists another institution's data. */
class InstitutionInboxController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->can('institution.confirm'), 403);
        $organizationId = $user->organization_id;
        $isAdmin = $user->hasRole('Super Admin');
        if (! $isAdmin) abort_unless($organizationId !== null, 403);

        $plans = IndicatorPlan::query()
            ->with(['indicator.thematicArea', 'financialYear', 'creator'])
            ->where('status', 'pending_institution')
            ->when(! $isAdmin, fn ($query) => $query->where('lead_organization_id', $organizationId)
                ->where('submitted_by', '!=', $user->id))
            ->orderBy('submitted_at')->get();

        $entries = IndicatorDataEntry::query()
            ->with(['indicator.thematicArea', 'plan', 'enteredBy'])
            ->whereIn('status', ['submitted', 'pending_approval'])
            ->whereNull('institution_confirmed_at')
            ->when(! $isAdmin, fn ($query) => $query->where('organization_id', $organizationId)
                ->where('entered_by', '!=', $user->id))
            ->orderBy('submitted_at')->get();

        return view('institution-inbox.index', compact('plans','entries'));
    }
}
