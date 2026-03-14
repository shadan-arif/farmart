<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('rezgo_meta')) {
            return; // Table already exists — safe to skip
        }

        Schema::create('rezgo_meta', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type', 50)->comment('product or order');
            $table->unsignedBigInteger('entity_id')->comment('ID from ec_products or ec_orders');
            $table->string('meta_key', 100)->comment('e.g. rezgo_uid, trans_num, rezgo_book_date');
            $table->text('meta_value')->nullable()->comment('The meta value');
            $table->timestamps();

            $table->index(['entity_type', 'entity_id', 'meta_key'], 'rezgo_meta_lookup');
            $table->unique(['entity_type', 'entity_id', 'meta_key'], 'rezgo_meta_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rezgo_meta');
    }
};
