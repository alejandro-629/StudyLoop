<?php

declare(strict_types=1);

return [

    /*
    | How long an emailed cohort invitation link stays valid.
    */
    'invitation_ttl_days' => (int) env('STUDYLOOP_INVITATION_TTL_DAYS', 7),

    /*
    | Password for the synthetic demo accounts created by the seeder. When it
    | is empty the seeder generates a random one and prints it once.
    */
    'demo_password' => env('DEMO_PASSWORD'),

];
