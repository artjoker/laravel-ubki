<?php

    use Illuminate\Support\Facades\Schema;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Database\Migrations\Migration;

    class CreateUbkiTokensTable extends Migration
    {
        /**
         * Run the migrations.
         *
         * @return void
         */
        public function up()
        {
            Schema::create('ubki_tokens', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('token')->nullable();
                $table->string('error_code')->nullable();
                $table->text('response')->nullable();
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
            Schema::dropIfExists('ubki_tokens');
        }
    }
