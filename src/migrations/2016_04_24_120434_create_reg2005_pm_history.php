<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateReg2005PmHistory extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reg2005_pm_history', function(Blueprint $table)
        {
            $table->increments('id');

            $table->integer('batch');

            $table->timestamp('date');

            $table->integer('accountId');

            $table->integer('time');

            $table->text('type');

            $table->boolean('income');

            $table->text('currency');

            $table->decimal('amount')->default(0);

            $table->decimal('fee')->default(0);

            $table->text('to')->nullable();

            $table->text('from')->nullable();

            $table->text('memo')->nullable();

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
        Schema::drop('reg2005_pm_history');
    }

}
