<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Activate every holiday easter egg (testing)
    |--------------------------------------------------------------------------
    |
    | When true, every page listens for every holiday's secret word, regardless
    | of today's date. Works in any environment, including production.
    |
    */

    'all_eggs' => (bool) env('HOLIDAY_EGGS_ALL', false),

];
