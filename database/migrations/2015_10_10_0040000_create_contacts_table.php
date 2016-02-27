<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateContactsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if (! Schema::hasTable('suppliers'))
		{
			Schema::create('suppliers', function(Blueprint $table)
			{
				$table->increments('id')->unsigned();
				$table->string('name')->index();
				$table->string('shortname')->index();
				$table->tinyInteger('producer')->default(0);
				$table->string('website')->index();
				$table->string('phone')->index();
				$table->integer('supplied_by')->unsigned();
				
				$table->timestamp('updated_at');
                $table->timestamp('created_at');
                $table->timestamp('deleted_at');
			});
		}	
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('suppliers');
	}

}