<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Ubah product_id di transaction_items dari onDelete('cascade') ke nullOnDelete()
     * agar history transaksi tidak hilang ketika produk dihapus.
     * 
     * Sebelumnya: jika produk dihapus, transaction_items hilang → stok tidak bisa dikembalikan saat hapus transaksi
     * Sesudahnya: jika produk dihapus, product_id menjadi NULL → history transaksi tetap ada
     */
    public function up(): void
    {
        Schema::table('transaction_items', function (Blueprint $table) {
            // Drop foreign key lama
            $table->dropForeign(['product_id']);

            // Ubah kolom product_id menjadi nullable
            $table->unsignedBigInteger('product_id')->nullable()->change();

            // Tambahkan foreign key baru dengan nullOnDelete
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaction_items', function (Blueprint $table) {
            $table->dropForeign(['product_id']);

            $table->unsignedBigInteger('product_id')->nullable(false)->change();

            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');
        });
    }
};
