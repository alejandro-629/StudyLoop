<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        return view('dashboard', [
            'cohorts' => $user->cohorts()
                ->with('course.organization')
                ->orderBy('cohorts.name')
                ->get(),
        ]);
    }
}
