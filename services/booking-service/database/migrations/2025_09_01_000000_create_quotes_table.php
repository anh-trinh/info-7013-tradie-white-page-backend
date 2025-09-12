<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up() {
    Schema::create('quotes', function (Blueprint $table) {
      $table->increments('id');
      $table->unsignedBigInteger('resident_account_id');
      $table->unsignedBigInteger('tradie_account_id');
      $table->string('service_address');
      $table->string('service_postcode')->nullable();
      $table->text('job_description');
      $table->enum('status', ['pending','responded','accepted','rejected','counter-offered'])->default('pending');
      $table->timestamps();
    });
  }
  public function down() { Schema::dropIfExists('quotes'); }
};