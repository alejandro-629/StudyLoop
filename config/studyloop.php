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

    /*
    | Course material uploads. Files are stored content-addressed on the
    | given disk; parsing runs on the queue. Limits apply before anything is
    | stored (size, extension and detected MIME type) and again after
    | extraction (normalized text length, PDF page count). Changing
    | max_section_characters only affects versions parsed afterwards.
    */
    'materials' => [
        'disk' => env('STUDYLOOP_MATERIALS_DISK', 'local'),
        'max_upload_kb' => (int) env('STUDYLOOP_MATERIALS_MAX_UPLOAD_KB', 10240),
        'max_text_characters' => (int) env('STUDYLOOP_MATERIALS_MAX_TEXT_CHARACTERS', 500000),
        'max_pdf_pages' => (int) env('STUDYLOOP_MATERIALS_MAX_PDF_PAGES', 300),
        // Longer sections are split at paragraph breaks so each one stays a
        // reasonable unit for item writing and AI drafting.
        'max_section_characters' => (int) env('STUDYLOOP_MATERIALS_MAX_SECTION_CHARACTERS', 6000),
    ],

];
