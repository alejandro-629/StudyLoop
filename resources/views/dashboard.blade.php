<x-layouts.app :title="__('My cohorts')">
    <section aria-labelledby="cohorts-heading" class="space-y-6">
        <h1 id="cohorts-heading" class="text-2xl font-semibold tracking-tight">{{ __('My cohorts') }}</h1>

        @if ($cohorts->isEmpty())
            <p class="text-stone-600">{{ __('You are not in any cohort yet. Open the invitation link from your instructor to join one.') }}</p>
        @else
            <ul class="space-y-3">
                @foreach ($cohorts as $cohort)
                    <li class="flex items-center justify-between rounded-lg border border-stone-200 bg-white p-4">
                        <div>
                            <p class="font-medium">{{ $cohort->course->code }} · {{ $cohort->course->title }}</p>
                            <p class="text-sm text-stone-600">
                                {{ $cohort->name }} · {{ $cohort->course->organization->name }} · {{ $cohort->enrollment->role->label() }}
                            </p>
                        </div>
                        @can('manageInvitations', $cohort)
                            <a href="{{ route('cohorts.invitations.index', $cohort) }}" class="text-sm font-medium hover:underline">{{ __('Invitations') }}</a>
                        @endcan
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</x-layouts.app>
