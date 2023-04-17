<?php

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

/**
 * Migration for {{ name }}
 */
return new class extends Migration
{
    public function up()
    {
        Schema::create('{{ tableName }}', function(Blueprint $table) {
            $table->increments('id');
            $table->integer('site_id')->nullable()->index();
            $table->integer('site_root_id')->nullable()->index();
            $table->string('title')->nullable();
            $table->string('slug')->nullable()->index();
{{ migrationCode|raw }}
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('{{ tableName }}');
    }
};
