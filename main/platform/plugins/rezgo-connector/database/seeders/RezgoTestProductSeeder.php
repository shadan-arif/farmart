<?php

namespace Botble\RezgoConnector\Database\Seeders;

use Botble\Ecommerce\Models\Product;
use Botble\RezgoConnector\Models\RezgoMeta;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RezgoTestProductSeeder extends Seeder
{
    public function run(): void
    {
        // ---------------------------------------------------------------
        // 1. Create (or find) a test product in ec_products
        // ---------------------------------------------------------------
        $sku  = 'REZGO-TEST-001';
        $slug = 'rezgo-test-tour-' . Str::random(6);

        /** @var Product $product */
        $product = Product::query()->firstOrCreate(
            ['sku' => $sku],
            [
                'name'              => '[TEST] Rezgo Test Tour',
                'description'       => 'This is a test product for validating the Rezgo Connector integration. Do not use in production.',
                'content'           => '<p>Test tour linked to Rezgo via the Rezgo Connector plugin.</p>',
                'status'            => 'published',
                'price'             => 99.00,
                'sale_price'        => null,
                'sku'               => $sku,
                'quantity'          => 100,
                'allow_checkout_when_out_of_stock' => true,
                'is_featured'       => false,
                'sale_type'         => 0,
                'stock_status'      => 'in_stock',
                'with_storehouse_management' => true,
                'generate_license_code'       => false,
                'minimum_order_quantity'      => 1,
                'maximum_order_quantity'      => 10,
                'images'            => '[]',
            ]
        );

        $this->command->info("Test product created/found with ID: {$product->id}");

        // ---------------------------------------------------------------
        // 2. Link the product to a Rezgo UID in rezgo_meta
        //    Replace '72547' with your real Rezgo service/item UID.
        // ---------------------------------------------------------------
        RezgoMeta::updateOrCreate(
            [
                'entity_type' => 'product',
                'entity_id'   => $product->id,
                'meta_key'    => 'rezgo_uid',
            ],
            ['meta_value' => '72547'] // <-- Replace with your Rezgo tour UID
        );

        // 3. Set a default booking date (e.g., 30 days from now)
        RezgoMeta::updateOrCreate(
            [
                'entity_type' => 'product',
                'entity_id'   => $product->id,
                'meta_key'    => 'rezgo_book_date',
            ],
            ['meta_value' => now()->addDays(30)->format('Y-m-d')]
        );

        $this->command->info("Rezgo UID (72547) and book date linked to product ID: {$product->id}");
        $this->command->info("Update the rezgo_uid in rezgo_meta to match your actual Rezgo service UID before testing.");
    }
}
