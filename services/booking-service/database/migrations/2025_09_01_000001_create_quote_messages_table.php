<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up() {
    Schema::create('quote_messages', function (Blueprint $table) {
      $table->increments('id');
      $table->unsignedInteger('quote_id');
      $table->unsignedBigInteger('sender_account_id');
      $table->text('message')->nullable();
      $table->decimal('offered_price',10,2)->nullable();
      $table->timestamps();
      $table->foreign('quote_id')->references('id')->on('quotes')->onDelete('cascade');
    });
  }
  public function down() { Schema::dropIfExists('quote_messages'); }
};