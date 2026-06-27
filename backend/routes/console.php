<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('backup:scheduled --type=database')->dailyAt('02:00');
Schedule::command('backup:scheduled --type=full --password=' . (config('app.backup_password', '')))->weekly();
