<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerAddressController extends Controller
{
    public function __construct()
    {
        abort_unless(hasModule('customer_portal'), 404);
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        if ($customer->addresses()->count() >= 5) {
            return back()->withErrors(['limit' => 'Maximum 5 addresses allowed.']);
        }

        $validated = $request->validate([
            'label' => ['required', 'string', 'max:20'],
            'street' => ['required', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:5'],
            'zip' => ['nullable', 'string', 'max:10'],
            'references' => ['nullable', 'string', 'max:500'],
        ]);

        $isFirst = $customer->addresses()->doesntExist();
        $customer->addresses()->create(array_merge($validated, ['is_default' => $isFirst]));

        return back()->with('success', 'Address added.');
    }

    public function update(Request $request, CustomerAddress $address): RedirectResponse
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();
        abort_unless($address->customer_id === $customer->id, 403);

        $validated = $request->validate([
            'label' => ['required', 'string', 'max:20'],
            'street' => ['required', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:5'],
            'zip' => ['nullable', 'string', 'max:10'],
            'references' => ['nullable', 'string', 'max:500'],
        ]);

        $address->update($validated);

        return back()->with('success', 'Address updated.');
    }

    public function destroy(CustomerAddress $address): RedirectResponse
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();
        abort_unless($address->customer_id === $customer->id, 403);

        $address->delete();

        return back()->with('success', 'Address deleted.');
    }

    public function setDefault(CustomerAddress $address): RedirectResponse
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();
        abort_unless($address->customer_id === $customer->id, 403);

        // Clear all defaults
        $customer->addresses()->update(['is_default' => false]);

        // Set new default
        $address->update(['is_default' => true]);

        return back()->with('success', 'Default address updated.');
    }
}
