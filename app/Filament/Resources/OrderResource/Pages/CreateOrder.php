<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Filament\Concerns\NotifiesNearLimit;
use App\Filament\Resources\OrderResource;
use App\Models\Customer;
use App\Models\FeatureUsage;
use App\Models\Order;
use App\Models\Payment;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;

class CreateOrder extends CreateRecord
{
    use HasWizard;
    use NotifiesNearLimit;

    protected static string $resource = OrderResource::class;

    protected function getSteps(): array
    {
        return OrderResource::getWizardSteps();
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Upsert customer record
        $customer = Customer::updateOrCreate(
            ['phone' => $data['customer_phone']],
            array_filter([
                'name' => $data['customer_name'],
                'email' => $data['customer_email'] ?? null,
            ]),
        );
        $data['customer_id'] = $customer->id;

        $initialPayment = (float) ($data['initial_payment'] ?? 0);
        unset($data['initial_payment']);

        $data['total_paid'] = $initialPayment;
        $data['status'] = $initialPayment > 0 ? OrderStatus::Confirmed : OrderStatus::Pending;
        $data['confirmed_at'] = $initialPayment > 0 ? now() : null;
        $data['subtotal'] = 0;
        $data['total'] = 0;

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var Order $order */
        $order = $this->record;

        $order->recalculateTotals();

        // Create first Payment record if initial payment was provided
        if ($order->total_paid > 0) {
            Payment::create([
                'order_id' => $order->id,
                'amount' => $order->total_paid,
                'method' => PaymentMethod::Cash,
                'received_at' => now(),
                'received_by' => auth()->id(),
                'notes' => 'Initial payment',
            ]);
        }

        FeatureUsage::track('order_created');

        if ($order->total_paid > 0) {
            FeatureUsage::track('payment_recorded');
        }

        $this->checkNearLimit('orders');
    }
}
