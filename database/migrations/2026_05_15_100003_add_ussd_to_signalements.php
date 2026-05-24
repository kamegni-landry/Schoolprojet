<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('signalements', function (Blueprint $table) {
            $table->string('ussd_session_id')->nullable()->after('id');
            $table->string('code_unique')->nullable()->unique()->after('id');
            $table->enum('origine', ['web', 'app', 'ussd'])->default('web')->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('signalements', function (Blueprint $table) {
            $table->dropColumn(['ussd_session_id', 'code_unique', 'origine']);
        });
    }
};
