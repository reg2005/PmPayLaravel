<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateReg2005PmWallets extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reg2005_pm_wallets', function(Blueprint $table)
        {

            $table->increments('id');

            $table->string('name')->unique();
            $table->text('currency')->nullable();
            $table->decimal('amount', 10)->nullable()->default(0);
            $table->integer('account');

            $table->timestamp('last_use')->nullable();


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('reg2005_pm_wallets');
    }

}
