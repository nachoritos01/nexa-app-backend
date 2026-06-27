<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\View\View;

class PageController extends Controller
{
    public function contact(): View
    {
        return view('pages.contact');
    }

    public function faq(): View
    {
        $path = resource_path('markdown/faq.md');
        $content = file_exists($path) ? Str::markdown(file_get_contents($path)) : '';

        return view('pages.content', [
            'title' => 'Frequently Asked Questions',
            'content' => $content,
        ]);
    }

    public function policies(): View
    {
        $path = resource_path('markdown/policies.md');
        $content = file_exists($path) ? Str::markdown(file_get_contents($path)) : '';

        return view('pages.content', [
            'title' => 'Policies',
            'content' => $content,
        ]);
    }

    public function terms(): View
    {
        $path = resource_path('markdown/terms.md');
        $content = file_exists($path) ? Str::markdown(file_get_contents($path)) : '';

        return view('pages.content', [
            'title' => 'Terms and Conditions',
            'content' => $content,
        ]);
    }

    public function usagePolicies(): View
    {
        $path = resource_path('markdown/usage-policies.md');
        $content = file_exists($path) ? Str::markdown(file_get_contents($path)) : '';

        return view('pages.content', [
            'title' => 'Usage Policies',
            'content' => $content,
        ]);
    }
}
