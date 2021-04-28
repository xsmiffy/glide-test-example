<?php

namespace App\Http\Controllers;

use App\Models\Area;
use Illuminate\Support\Facades\DB;
use App\Models\GasCalorificValue;
use Illuminate\Http\Request;

class CalorificValueController extends Controller
{
    
    function show(Request $request)
    {
        $viewData = [];

        $gasCVQuery = DB::table('gas_calorific_values')
            ->join('areas', 'gas_calorific_values.area_id', '=', 'areas.id');

        $area = request('areas');
        $startDate = request('start-date');
        $endDate = request('end-date');

        if ($area && $area !== 'all') {
            $gasCVQuery->where('area_id', $area);
            $viewData['selectedArea'] = $area;
        }

        if ($startDate) {
            $gasCVQuery->where('applicable_for', '>=', $startDate);
            $viewData['selectedStart'] = $startDate;
        }

        if ($endDate) {
            $gasCVQuery->where('applicable_for', '<=', $endDate);
            $viewData['selectedEnd'] = $endDate;
        }

        $viewData['selectedArea'] = $viewData['selectedArea'] ?? 'all';
        $viewData['averageCV'] = floor(collect($gasCVQuery->get())->avg('value') * 10)/10;
        $viewData['calorificValues'] = $gasCVQuery->paginate(20)->appends(request()->except('page'));
        $viewData['areas'] = Area::all();

        return view('values', $viewData);
    }

}
