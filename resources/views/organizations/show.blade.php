<x-layouts.app :title="$organization->name">
    <section aria-labelledby="organization-heading" class="space-y-6">
        <div>
            <h1 id="organization-heading" class="text-2xl font-semibold tracking-tight">{{ $organization->name }}</h1>
            <p class="text-stone-600">{{ __('Courses and cohorts in this organization.') }}</p>
        </div>

        @forelse ($courses as $course)
            <div class="space-y-3 rounded-lg border border-stone-200 bg-white p-4">
                <div class="flex items-center justify-between">
                    <h2 class="font-medium">{{ $course->code }} · {{ $course->title }}</h2>
                    <a href="{{ route('materials.index', $course) }}" class="text-sm font-medium hover:underline">{{ __('Materials') }}</a>
                </div>

                @if ($course->cohorts->isEmpty())
                    <p class="text-sm text-stone-600">{{ __('No cohorts yet.') }}</p>
                @else
                    <ul class="divide-y divide-stone-100 text-sm">
                        @foreach ($course->cohorts as $cohort)
                            <li class="flex items-center justify-between py-2">
                                <span>{{ $cohort->name }} · {{ trans_choice(':count member|:count members', $cohort->members_count) }}</span>
                                <a href="{{ route('cohorts.invitations.index', $cohort) }}" class="font-medium hover:underline">{{ __('Invitations') }}</a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @empty
            <p class="text-stone-600">{{ __('No courses yet.') }}</p>
        @endforelse
    </section>
</x-layouts.app>
