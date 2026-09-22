<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->esSuperadmin()) {
            return redirect()->route('superadmin.empresas.index');
        }

        return view('dashboard.index', [
            'user' => $user,
        ]);
    }
}
