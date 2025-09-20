<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTradieProfilesTable extends Migration
{
    public function up()
    {
        Schema::create('tradie_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id')->unique();
            $table->string('business_name');
            $table->text('about')->nullable();
            $table->string('postcode');
            $table->decimal('base_rate', 10, 2);
            $table->timestamps();
        });
    }
    public function down()
    {
        Schema::dropIfExists('tradie_profiles');
    }
}
