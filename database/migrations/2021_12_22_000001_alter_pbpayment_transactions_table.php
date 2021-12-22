<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Salamzadeh\PBPayment\Traits\PBPaymentDatabase;

class AlterPBPaymentTransactionsTable extends Migration
{
    use PBPaymentDatabase;

    public function up()
    {
        Schema::table($this->getTable(), function (Blueprint $table) {
            $table->string('full_name')->nullable()->after('card_number');
            $table->string('email')->nullable()->after('full_name');
            $table->json('gateway_data')->nullable()->after('extra');
        });
    }

    public function down()
    {
        Schema::table($this->getTable(), function ($table) {
            $table->dropColumn('email');
        });
        Schema::table($this->getTable(), function ($table) {
            $table->dropColumn('full_name');
        });
        Schema::table($this->getTable(), function ($table) {
            $table->dropColumn('gateway_data');
        });
    }
}
