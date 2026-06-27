<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema): void {
        $schema->table('users', function (Blueprint $table) use ($schema): void {
            if (!$schema->hasColumn('users', 'flagrow_telegram_id')) {
                $table->string('flagrow_telegram_id', 64)->nullable()->index();
            }

            if (!$schema->hasColumn('users', 'flagrow_telegram_error')) {
                $table->string('flagrow_telegram_error', 32)->nullable();
            }
        });
    },
    'down' => function (Builder $schema): void {
        $schema->table('users', function (Blueprint $table) use ($schema): void {
            if ($schema->hasColumn('users', 'flagrow_telegram_id')) {
                $table->dropColumn('flagrow_telegram_id');
            }

            if ($schema->hasColumn('users', 'flagrow_telegram_error')) {
                $table->dropColumn('flagrow_telegram_error');
            }
        });
    },
];
