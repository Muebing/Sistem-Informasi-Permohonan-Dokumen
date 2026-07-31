<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('about', function (): void {
    $this->components->info(config('app.name').' console');
});
