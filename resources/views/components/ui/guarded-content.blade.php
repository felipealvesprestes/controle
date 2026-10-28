@props(['permission'])

{{-- Content the user may not see is masked, not removed, so the layout stays the same (F-17). --}}
@can($permission)
    {{ $slot }}
@else
    <span class="text-muted" title="{{ __('No permission to view') }}" aria-label="{{ __('No permission to view') }}">•••</span>
@endcan
