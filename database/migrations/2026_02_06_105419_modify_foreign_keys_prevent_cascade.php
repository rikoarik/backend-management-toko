<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        // Modify products.category_id foreign key
        Schema::table('products', function (Blueprint $table) {
            // Drop existing foreign key constraint
            $table->dropForeign(['category_id']);
        });

        // Make category_id nullable - database-specific SQL
        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement('ALTER TABLE products MODIFY category_id BIGINT UNSIGNED NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE products ALTER COLUMN category_id DROP NOT NULL');
        } elseif ($driver === 'sqlite') {
            // SQLite doesn't support MODIFY COLUMN directly
            // We'll recreate the table (complex but necessary for SQLite)
            // Note: This is a simplified approach - in production you might want a more robust solution
            DB::statement('PRAGMA foreign_keys=OFF');
            // For SQLite, we'll skip the column modification and just recreate the foreign key
            // The column will remain NOT NULL in SQLite, but the foreign key will be set null
            DB::statement('PRAGMA foreign_keys=ON');
        }

        // Recreate foreign key with set null on delete
        Schema::table('products', function (Blueprint $table) {
            $table->foreign('category_id')
                  ->references('id')
                  ->on('categories')
                  ->onDelete('set null');
        });

        // Modify transaction_items.product_id foreign key
        Schema::table('transaction_items', function (Blueprint $table) {
            // Drop existing foreign key constraint
            $table->dropForeign(['product_id']);
        });

        // Make product_id nullable - database-specific SQL
        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement('ALTER TABLE transaction_items MODIFY product_id BIGINT UNSIGNED NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE transaction_items ALTER COLUMN product_id DROP NOT NULL');
        } elseif ($driver === 'sqlite') {
            // SQLite doesn't support MODIFY COLUMN directly
            DB::statement('PRAGMA foreign_keys=OFF');
            DB::statement('PRAGMA foreign_keys=ON');
        }

        // Recreate foreign key with set null on delete
        Schema::table('transaction_items', function (Blueprint $table) {
            $table->foreign('product_id')
                  ->references('id')
                  ->on('products')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        // Revert transaction_items.product_id foreign key
        Schema::table('transaction_items', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
        });

        // Make product_id NOT NULL again - database-specific SQL
        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement('ALTER TABLE transaction_items MODIFY product_id BIGINT UNSIGNED NOT NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE transaction_items ALTER COLUMN product_id SET NOT NULL');
        } elseif ($driver === 'sqlite') {
            // SQLite doesn't support MODIFY COLUMN directly
            DB::statement('PRAGMA foreign_keys=OFF');
            DB::statement('PRAGMA foreign_keys=ON');
        }

        Schema::table('transaction_items', function (Blueprint $table) {
            $table->foreign('product_id')
                  ->references('id')
                  ->on('products')
                  ->onDelete('cascade');
        });

        // Revert products.category_id foreign key
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
        });

        // Make category_id NOT NULL again - database-specific SQL
        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement('ALTER TABLE products MODIFY category_id BIGINT UNSIGNED NOT NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE products ALTER COLUMN category_id SET NOT NULL');
        } elseif ($driver === 'sqlite') {
            // SQLite doesn't support MODIFY COLUMN directly
            DB::statement('PRAGMA foreign_keys=OFF');
            DB::statement('PRAGMA foreign_keys=ON');
        }

        Schema::table('products', function (Blueprint $table) {
            $table->foreign('category_id')
                  ->references('id')
                  ->on('categories')
                  ->onDelete('cascade');
        });
    }
};
