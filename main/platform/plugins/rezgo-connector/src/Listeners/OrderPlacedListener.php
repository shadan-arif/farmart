<?php

namespace Botble\RezgoConnector\Listeners;

use Botble\Ecommerce\Events\OrderPlacedEvent;
use Botble\Ecommerce\Models\Order;
use Botble\RezgoConnector\Models\RezgoMeta;
use Botble\RezgoConnector\Services\RezgoApiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class OrderPlacedListener implements ShouldQueue
{
    /**
     * Queue connection/name for async processing.
     */
    public string $connection = 'sync';

    public function __construct(protected RezgoApiService $rezgoApi)
    {
    }

    /**
     * Handle the OrderPlacedEvent.
     */
    public function handle(OrderPlacedEvent $event): void
    {
        if (! $this->rezgoApi->isEnabled()) {
            return;
        }

        /** @var Order $order */
        $order = $event->order->load(['products', 'shippingAddress', 'user']);

        foreach ($order->products as $orderProduct) {
            $product = $orderProduct->product;

            if (! $product) {
                continue;
            }

            // Look up the Rezgo UID for this product in our meta table
            $rezgoUidMeta = RezgoMeta::query()
                ->where('entity_type', 'product')
                ->where('entity_id', $product->id)
                ->where('meta_key', 'rezgo_uid')
                ->first();

            if (! $rezgoUidMeta || empty($rezgoUidMeta->meta_value)) {
                // This product is not linked to Rezgo — skip
                continue;
            }

            $rezgoUid = $rezgoUidMeta->meta_value;

            // Determine booking date: use today as default if no specific date is stored
            $bookDateMeta = RezgoMeta::query()
                ->where('entity_type', 'product')
                ->where('entity_id', $product->id)
                ->where('meta_key', 'rezgo_book_date')
                ->first();

            $bookDate = $bookDateMeta?->meta_value ?? now()->format('Y-m-d');

            // Quantity from the order product represents number of adults
            $adultNum = (int) ($orderProduct->qty ?? 1);

            try {
                $result = $this->rezgoApi->commitBooking(
                    order: $order,
                    rezgoUid: $rezgoUid,
                    bookDate: $bookDate,
                    adultNum: $adultNum,
                    refId: 'farmart-order-' . $order->id . '-product-' . $product->id,
                );

                if ($result['success'] && ! empty($result['trans_num'])) {
                    // Store the Rezgo transaction number against this order
                    RezgoMeta::updateOrCreate(
                        [
                            'entity_type' => 'order',
                            'entity_id'   => $order->id,
                            'meta_key'    => 'trans_num_product_' . $product->id,
                        ],
                        ['meta_value' => $result['trans_num']]
                    );
                } else {
                    $this->logError($order->id, $product->id, $result['message']);
                }
            } catch (\Throwable $e) {
                // Never let Rezgo errors break the checkout flow
                $this->logError($order->id, $product->id, $e->getMessage());
            }
        }
    }

    /**
     * Log an error without disrupting checkout.
     */
    private function logError(int $orderId, int $productId, string $message): void
    {
        $logger = Log::build([
            'driver' => 'daily',
            'path'   => storage_path('logs/rezgo-sync.log'),
            'days'   => 14,
        ]);

        $logger->error('[REZGO] Booking commit failed', [
            'order_id'   => $orderId,
            'product_id' => $productId,
            'message'    => $message,
        ]);
    }
}
