<div>
    <div class="card mb-3">
        <div class="card-header">{{ __('Recurrence') }}</div>
        <div class="card-body">
            <div class="row g-2 align-items-center">
                <div class="col-auto">
                    <label class="form-label mb-0" for="frequency">{{ __('Every') }}</label>
                </div>
                <div class="col-auto">
                    <input type="number" min="1" class="form-control form-control-sm" style="width: 5rem"
                        wire:model.live="interval" id="interval">
                </div>
                <div class="col-auto">
                    <select class="form-select form-select-sm" wire:model.live="frequency" id="frequency">
                        <option value="weekly">{{ __('week(s)') }}</option>
                        <option value="monthly">{{ __('month(s)') }}</option>
                    </select>
                </div>
            </div>

            @if ($frequency === 'weekly')
                <div class="col-12 btn-group btn-group-sm mt-2" role="group" aria-label="{{ __('Recurrence') }}">
                    @foreach (['MO' => __('Monday'), 'TU' => __('Tuesday'), 'WE' => __('Wednesday'), 'TH' => __('Thursday'), 'FR' => __('Friday'), 'SA' => __('Saturday'), 'SU' => __('Sunday')] as $code => $label)
                        <input wire:model.live="byDay" type="checkbox" class="btn-check" value="{{ $code }}" id="byDay-{{ $code }}" autocomplete="off">
                        <label class="btn btn-outline-secondary" for="byDay-{{ $code }}">{{ substr($label, 0, 3) }}<span class="d-none d-sm-inline">{{ substr($label, 3) }}</span></label>
                    @endforeach
                </div>
            @endif

            <div class="mt-2">
                <label class="form-label" for="untilDate">{{ __('Ends') }}</label>
                <input type="date" class="form-control form-control-sm" style="width: 12rem" wire:model.live="untilDate" id="untilDate">
                <x-forms.helpers.help text="{{ __('Leave empty to repeat indefinitely.') }}" />
            </div>

            <hr>

            <div class="mt-3">
                <label class="form-label">{{ __('Exception dates') }}</label>
                <x-forms.helpers.help text="{{ __('Individual occurrences to skip.') }}" />
                <ul class="list-group list-group-flush mb-2" style="max-width: 20rem">
                    @foreach ($exceptionDates as $date)
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-1">
                            {{ $date }}
                            <button type="button" class="btn btn-sm btn-outline-danger" wire:click="removeExceptionDate('{{ $date }}')">
                                <i class="fas fa-fw fa-trash"></i>
                            </button>
                        </li>
                    @endforeach
                </ul>
                <div class="input-group input-group-sm" style="max-width: 16rem">
                    <input type="date" class="form-control" wire:model="newExceptionDate">
                    <button type="button" class="btn btn-outline-secondary" wire:click="addExceptionDate">{{ __('Add') }}</button>
                </div>
            </div>

            @error('rrule')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>
