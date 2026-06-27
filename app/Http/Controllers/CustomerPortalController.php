<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\LoyaltyReward;
use App\Models\Order;
use App\Services\LoyaltyService;
use App\Services\PdfGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerPortalController extends Controller
{
    public function __construct()
    {
        abort_unless(hasModule('customer_portal'), 404);
    }

    public function index(): RedirectResponse
    {
        return redirect()->route('customer.orders');
    }

    public function orders(Request $request): View
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        $query = $customer->orders()->with('lines.item')->latest();

        // Filter by status
        $filter = $request->get('filter', 'all');
        if ($filter === 'active') {
            $query->whereNotIn('status', [OrderStatus::Completed, OrderStatus::Cancelled]);
        } elseif ($filter === 'completed') {
            $query->where('status', OrderStatus::Completed);
        } elseif ($filter === 'cancelled') {
            $query->where('status', OrderStatus::Cancelled);
        }

        $orders = $query->paginate(10);

        return view('customer.portal', [
            'tab' => 'orders',
            'customer' => $customer,
            'orders' => $orders,
            'currentFilter' => $filter,
        ]);
    }

    public function orderDetail(Order $order): View
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        abort_unless($order->customer_id === $customer->id, 403);

        $order->load(['lines.item', 'payments', 'location']);

        return view('customer.portal', [
            'tab' => 'order-detail',
            'customer' => $customer,
            'order' => $order,
        ]);
    }

    public function orderPdf(Order $order): StreamedResponse
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        abort_unless($order->customer_id === $customer->id, 403);
        abort_if($order->status === OrderStatus::Cancelled, 404);

        $pdf = app(PdfGenerator::class)->generateOrderInline($order);

        return response()->streamDownload(
            fn () => print($pdf->output()),
            "order_{$order->id}.pdf",
            ['Content-Type' => 'application/pdf'],
        );
    }

    public function profile(): View
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        return view('customer.portal', [
            'tab' => 'profile',
            'customer' => $customer,
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();
        $customer->update($validated);

        return back()->with('success', 'Perfil actualizado correctamente.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        if (! Hash::check($request->get('current_password'), $customer->password)) {
            return back()->withErrors(['current_password' => 'La contraseña actual no es correcta.']);
        }

        $customer->update(['password' => $request->get('password')]);

        return back()->with('success', 'Contraseña actualizada correctamente.');
    }

    public function addresses(): View
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        $addresses = $customer->addresses()->orderByDesc('is_default')->latest()->get();

        return view('customer.portal', [
            'tab' => 'addresses',
            'customer' => $customer,
            'addresses' => $addresses,
        ]);
    }

    public function payments(): View
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        $orders = $customer->orders()->with('payments')->latest()->get();
        $allPayments = $customer->payments()->latest()->get();

        $totalPaid = $allPayments->sum('amount');
        $pendingBalance = $orders->sum('balance');
        $activeOrders = $orders->whereNotIn('status', [OrderStatus::Completed, OrderStatus::Cancelled])->count();
        $ordersWithBalance = $orders->where('balance', '>', 0);

        return view('customer.portal', [
            'tab' => 'payments',
            'customer' => $customer,
            'totalPaid' => $totalPaid,
            'pendingBalance' => $pendingBalance,
            'activeOrders' => $activeOrders,
            'ordersWithBalance' => $ordersWithBalance,
            'allPayments' => $allPayments,
        ]);
    }

    public function loyalty(): View
    {
        abort_unless(hasModule('loyalty'), 404);

        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        $transactions = $customer->loyaltyTransactions()->latest()->limit(20)->get();
        $coupons = $customer->loyaltyCoupons()->with('reward')->latest()->get();
        $availableRewards = LoyaltyReward::availableFor($customer)->orderBy('points_cost')->get();
        $lockedRewards = LoyaltyReward::active()
            ->where(function ($query) use ($customer) {
                $query->where('points_cost', '>', $customer->loyalty_points)
                    ->orWhere('min_tier', '>', $customer->loyalty_tier->value);
            })
            ->orderBy('points_cost')
            ->get();

        return view('customer.portal', [
            'tab' => 'loyalty',
            'customer' => $customer,
            'transactions' => $transactions,
            'coupons' => $coupons,
            'availableRewards' => $availableRewards,
            'lockedRewards' => $lockedRewards,
        ]);
    }

    public function redeemReward(LoyaltyReward $reward, LoyaltyService $loyaltyService): RedirectResponse
    {
        abort_unless(hasModule('loyalty'), 404);

        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        if ($customer->loyalty_points < $reward->points_cost) {
            return back()->withErrors(['reward' => 'Not enough points to redeem this reward.']);
        }

        if ($customer->loyalty_tier->value < $reward->min_tier) {
            return back()->withErrors(['reward' => 'Your tier is not high enough for this reward.']);
        }

        $coupon = $loyaltyService->redeemReward($customer, $reward);

        return redirect()->route('customer.loyalty')
            ->with('success', 'Reward redeemed! Your coupon code is: ' . $coupon->code);
    }

    public function settings(): View
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        return view('customer.portal', [
            'tab' => 'settings',
            'customer' => $customer,
        ]);
    }

    public function updateNotifications(Request $request): RedirectResponse
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        $customer->update([
            'notifications_payment' => $request->boolean('notifications_payment'),
            'notifications_promos' => $request->boolean('notifications_promos'),
        ]);

        return back()->with('success', 'Notificaciones actualizadas.');
    }

    public function deleteAccount(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        if (! Hash::check($request->get('password'), $customer->password)) {
            return back()->withErrors(['password' => 'La contraseña no es correcta.']);
        }

        // Anonymize instead of delete to preserve order history
        $customer->update([
            'name' => 'Cliente eliminado',
            'phone' => 'deleted-'.$customer->id,
            'email' => null,
            'password' => null,
            'notes' => null,
        ]);

        Auth::guard('customer')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'Tu cuenta ha sido eliminada.');
    }
}
