<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;

Artisan::command('admin:grant {email}', function (string $email) {
    $user = User::query()->where('email', $email)->first();

    if (! $user) {
        $this->error('Nu există utilizator cu emailul '.$email.'.');

        return 1;
    }

    $user->forceFill(['is_admin' => true])->save();

    $this->info($email.' are acum acces de administrator.');

    return 0;
})->purpose('Acordă acces la zona de administrare e-test.ro');
