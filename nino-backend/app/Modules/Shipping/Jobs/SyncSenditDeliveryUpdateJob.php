<?php

namespace App\Modules\Shipping\Jobs;

use App\Modules\Shipping\Models\Shipment;
use App\Modules\Shipping\Services\SenditService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncSenditDeliveryUpdateJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;
    public int $uniqueFor = 120;

    public function __construct(
        public readonly int $shipmentId,
    ) {
        $this->afterCommit();
        $this->onQueue('shipping');
    }

    public function uniqueId(): string
    {
        return 'sendit-delivery-update:'.$this->shipmentId;
    }

    public function handle(SenditService $senditService): void
    {
        $shipment = Shipment::query()
            ->with([
                'shippingMethod.shippingCarrier',
                'order.customer',
                'order.addresses.shippingCarrierDistrict',
                'order.lineItems',
            ])
            ->find($this->shipmentId);

        if (! $shipment || ! $shipment->usesSendit() || blank($shipment->external_reference)) {
            return;
        }

        try {
            $senditService->updateDelivery($shipment);
        } catch (Throwable $exception) {
            Log::warning('[Shipping] Queued Sendit delivery update failed', [
                'shipment_id' => $this->shipmentId,
                'external_reference' => $shipment->external_reference,
                'error' => $exception->getMessage(),
            ]);

            $freshShipment = $shipment->fresh();

            if ($freshShipment && blank($freshShipment->provider_error)) {
                $freshShipment->forceFill([
                    'provider_error' => 'Sendit delivery sync failed: '.$exception->getMessage(),
                ])->save();
            }

            throw $exception;
        }
    }
}
