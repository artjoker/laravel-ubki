<?php

    use Illuminate\Support\Facades\Schema;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Database\Migrations\Migration;

    class AddedAccountLoginToUbkiTokensTable extends Migration
    {
        /**
         * Run the migrations.
         *
         * @return void
         */
        public function up()
        {
            Schema::table('ubki_tokens', function (Blueprint $table) {
                $table->string('account_login')->nullable()->after('token');
            });
        }

        /**
         * Reverse the migrations.
         *
         * @return void
         */
        public function down()
        {
            Schema::table('ubki_tokens', function (Blueprint $table) {
                $table->dropColumn('account_login');
            });
        }
    }
