@php
    $savedAnnualRows = $treatmentPlant ? $treatmentPlant->annualSustainability->map(function ($row) { return $row->toArray(); })->all() : [];
    $annualRows = old('cwis_annual', $savedAnnualRows ?: [['data_year' => now()->year - 1]]);
    if (!is_array($annualRows) || !$annualRows) $annualRows = [['data_year' => now()->year - 1]];
    $removedAnnual = old('cwis_removed_annual', []);
    $removedAnnual = is_array($removedAnnual) ? array_filter($removedAnnual, 'is_scalar') : [];
@endphp
<div id="cwis-annual-rows">
    @foreach($annualRows as $index => $annual)
        @include('fsm.treatment-plants.cwis-annual-row', ['index' => $index, 'annual' => is_array($annual) ? $annual : [], 'addYearControl' => $loop->first, 'removable' => !$loop->first])
    @endforeach
</div>
<div id="cwis-removed-annual">
    @foreach($removedAnnual as $removedId)
        <input type="hidden" name="cwis_removed_annual[]" value="{{ $removedId }}">
    @endforeach
</div>
<template id="cwis-annual-template">
    @include('fsm.treatment-plants.cwis-annual-row', ['index' => '__INDEX__', 'annual' => [], 'removable' => true, 'addYearControl' => false])
</template>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var rows = document.getElementById('cwis-annual-rows');
    var nextIndex = @json(count($annualRows) ? max(array_map('intval', array_keys($annualRows))) + 1 : 0);
    document.getElementById('cwis-add-year').addEventListener('click', function () {
        var usedYears = Array.from(rows.querySelectorAll('input[name$="[data_year]"]')).map(function (input) { return Number(input.value); });
        var year = @json(now()->year - 1);
        while (usedYears.includes(year) && year >= 1900) year--;
        if (year < 1900) return;
        var markup = document.getElementById('cwis-annual-template').innerHTML.replace(/__INDEX__/g, nextIndex++);
        rows.insertAdjacentHTML('beforeend', markup);
        var yearInput = rows.lastElementChild.querySelector('input[name$="[data_year]"]');
        yearInput.value = year;
        yearInput.focus();
    });
    rows.addEventListener('click', function (event) {
        var button = event.target.closest('.cwis-remove-year');
        if (!button) return;
        var row = button.closest('.cwis-annual-row');
        var recordId = row.querySelector('input[name$="[id]"]');
        if (recordId && recordId.value) {
            var removed = document.createElement('input');
            removed.type = 'hidden';
            removed.name = 'cwis_removed_annual[]';
            removed.value = recordId.value;
            document.getElementById('cwis-removed-annual').appendChild(removed);
        }
        row.remove();
    });
});
</script>
@endpush
