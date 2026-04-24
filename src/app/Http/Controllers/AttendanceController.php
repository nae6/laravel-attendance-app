<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class AttendanceController extends Controller
{
    public function index() {
        $now_date = Carbon::now()->isoFormat('YYYY年MM月DD日(ddd)');
        $now_time = Carbon::now()->format('H:i');

        return view('user.index', compact('now_date', 'now_time'));
    }
}
