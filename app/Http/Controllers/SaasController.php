<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\View\View;

class SaasController extends Controller
{
    public function landing(): View
    {
        return view('saas.landing');
    }

    public function pricing(): View
    {
        $plans = [
            'starter' => config('saas.plans.starter'),
            'growth' => config('saas.plans.growth'),
            'pro' => config('saas.plans.pro'),
        ];

        return view('saas.pricing', compact('plans'));
    }

    public function terms(): View
    {
        $path = resource_path('markdown/saas-terms.md');
        $content = file_exists($path) ? Str::markdown(file_get_contents($path)) : '';

        return view('saas.content', [
            'title' => 'Terms and Conditions',
            'content' => $content,
        ]);
    }

    public function policies(): View
    {
        $path = resource_path('markdown/saas-policies.md');
        $content = file_exists($path) ? Str::markdown(file_get_contents($path)) : '';

        return view('saas.content', [
            'title' => 'Usage Policies',
            'content' => $content,
        ]);
    }
}
