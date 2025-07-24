<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ID «админа» или твоего аккаунта
        $adminId = 1;

        DB::table('clips')
            ->whereNull('user_id')
            ->update(['user_id' => $adminId]);
    }

    public function down(): void
    {
        // при откате можно вернуть NULL (необязательно)
        $adminId = 1;

        DB::table('clips')
            ->where('user_id', $adminId)
            ->update(['user_id' => null]);
    }
};
