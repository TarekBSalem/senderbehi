<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('bounces:fetch')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('campaigns:send-scheduled')->everyMinute()->withoutOverlapping();
