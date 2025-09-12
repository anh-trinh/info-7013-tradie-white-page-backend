<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up() {
    Schema::create('bookings', function (Blueprint $table) {
      $table->increments('id');
      $table->unsignedInteger('quote_id')->unique();
      $table->decimal('final_price',10,2)->nullable();
      $table->dateTime('scheduled_at')->nullable();
      $table->enum('status', ['scheduled','in_progress','completed','cancelled'])->default('scheduled');
      $table->timestamps();
      $table->foreign('quote_id')->references('id')->on('quotes')->onDelete('cascade');
    });
  }
  public function down() { Schema::dropIfExists('bookings'); }
};