<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tradie_services', function (Blueprint $table) {
            $table->unsignedBigInteger('tradie_profile_id');
            $table->unsignedBigInteger('service_category_id');
            $table->primary(['tradie_profile_id', 'service_category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tradie_services');
    }
};
