<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tradie_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('tradie_profiles', 'email')) {
                $table->string('email')->nullable()->after('base_rate');
            }
            if (!Schema::hasColumn('tradie_profiles', 'phone_number')) {
                $table->string('phone_number')->nullable()->after('email');
            }
            if (!Schema::hasColumn('tradie_profiles', 'contact_person')) {
                $table->string('contact_person')->nullable()->after('phone_number');
            }
            if (!Schema::hasColumn('tradie_profiles', 'average_rating')) {
                $table->decimal('average_rating', 3, 1)->default(0)->after('contact_person');
            }
            if (!Schema::hasColumn('tradie_profiles', 'reviews_count')) {
                $table->unsignedInteger('reviews_count')->default(0)->after('average_rating');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tradie_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('tradie_profiles', 'reviews_count')) {
                $table->dropColumn('reviews_count');
            }
            if (Schema::hasColumn('tradie_profiles', 'average_rating')) {
                $table->dropColumn('average_rating');
            }
            if (Schema::hasColumn('tradie_profiles', 'contact_person')) {
                $table->dropColumn('contact_person');
            }
            if (Schema::hasColumn('tradie_profiles', 'phone_number')) {
                $table->dropColumn('phone_number');
            }
            if (Schema::hasColumn('tradie_profiles', 'email')) {
                $table->dropColumn('email');
            }
        });
    }
};
