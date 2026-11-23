<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\CohortRole;
use App\Enums\OrganizationRole;
use App\Models\Cohort;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Two synthetic organizations with one account per role, so cross-org
 * isolation can be tried by hand. Safe to re-run: everything is upserted
 * by natural key and existing passwords are left alone.
 */
final class DemoOrganizationsSeeder extends Seeder
{
    public function run(): void
    {
        $configured = config('studyloop.demo_password');
        $password = is_string($configured) && $configured !== '' ? $configured : Str::password(16, symbols: false);

        $northwind = $this->organization('northwind-college', 'Northwind Community College (demo)');
        $biology = $this->course($northwind, 'BIO101', 'Introductory Cell Biology');
        $sectionA = $this->cohort($biology, 'Spring 2027 · Section A');

        $harbor = $this->organization('harbor-tech', 'Harbor Tech Academy (demo)');
        $networks = $this->course($harbor, 'NET110', 'Networking Fundamentals');
        $evening = $this->cohort($networks, 'Spring 2027 · Evening');

        $created = [];

        foreach ([
            ['admin@studyloop.test', 'Avery Admin', $northwind, OrganizationRole::Admin, null],
            ['instructor@studyloop.test', 'Imani Instructor', $northwind, OrganizationRole::Member, [$sectionA, CohortRole::Instructor]],
            ['ta@studyloop.test', 'Tomas Assistant', $northwind, OrganizationRole::Member, [$sectionA, CohortRole::TeachingAssistant]],
            ['learner@studyloop.test', 'Lena Learner', $northwind, OrganizationRole::Member, [$sectionA, CohortRole::Learner]],
            ['other.learner@studyloop.test', 'Omar Other-Org', $harbor, OrganizationRole::Member, [$evening, CohortRole::Learner]],
        ] as [$email, $name, $organization, $orgRole, $enrollment]) {
            $user = User::query()->firstOrNew(['email' => $email]);

            if (! $user->exists) {
                $user->fill(['name' => $name, 'password' => $password]);
                $user->forceFill(['email_verified_at' => now()])->save();
                $created[] = $email;
            }

            Membership::query()->updateOrCreate(
                ['organization_id' => $organization->id, 'user_id' => $user->id],
                ['role' => $orgRole],
            );

            if ($enrollment !== null) {
                [$cohort, $cohortRole] = $enrollment;

                Enrollment::query()->updateOrCreate(
                    ['cohort_id' => $cohort->id, 'user_id' => $user->id],
                    ['role' => $cohortRole],
                );
            }
        }

        if ($created !== []) {
            $this->command->info('Demo accounts created: '.implode(', ', $created));

            if ($password !== $configured) {
                $this->command->warn("No DEMO_PASSWORD set; generated password for these accounts: {$password}");
            }
        }
    }

    private function organization(string $slug, string $name): Organization
    {
        return Organization::query()->updateOrCreate(['slug' => $slug], ['name' => $name]);
    }

    private function course(Organization $organization, string $code, string $title): Course
    {
        return Course::query()->updateOrCreate(
            ['organization_id' => $organization->id, 'code' => $code],
            ['title' => $title],
        );
    }

    private function cohort(Course $course, string $name): Cohort
    {
        return Cohort::query()->updateOrCreate(
            ['course_id' => $course->id, 'name' => $name],
            ['starts_on' => '2027-01-18', 'ends_on' => '2027-05-07'],
        );
    }
}
