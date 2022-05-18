<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('journal', function (Blueprint $table) {
            $table->id();
            $table->integer('salary');
            $table->unsignedTinyInteger('daysCount');
            $table->unsignedTinyInteger('workDays');
            $table->year('calendarYear');
            $table->unsignedTinyInteger('calendarMonth');
            $table->boolean('hasTax');
            $table->boolean('isPensioner');
            $table->boolean('isInvalid');
            $table->unsignedTinyInteger('invalidDegree')->nullable();
            $table->decimal('ipn', $precision = 20, $scale = 2);
            $table->decimal('opv', $precision = 20, $scale = 2);
            $table->decimal('osms', $precision = 20, $scale = 2);
            $table->decimal('vosms', $precision = 20, $scale = 2);
            $table->decimal('so', $precision = 20, $scale = 2);
            $table->decimal('resultSalary', $precision = 20, $scale = 2);
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
        Schema::dropIfExists('journal');
    }
};
