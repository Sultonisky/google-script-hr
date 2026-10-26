@php
    $selectClass = $selectClass ?? 'form-select';
    $labelClass = $labelClass ?? 'form-label';
    $label = $label ?? 'Lokasi Kerja Penempatan';
    $placeholder = $placeholder ?? '-- Pilih Lokasi Kerja --';
    $locations = $locations ?? ($workLocations ?? config('hris.mpr_form_options.work_locations', []));
    $otherLabel = (string) config('hris.mpr_form_options.work_location_other', 'Lainnya');
    $selected = (string) ($selected ?? '');
    $custom = (string) ($custom ?? '');
    $isOther = strcasecmp($selected, $otherLabel) === 0
        || ($selected !== '' && !in_array($selected, $locations, true));
    if ($custom === '' && $isOther && strcasecmp($selected, $otherLabel) !== 0) {
        $custom = $selected;
    }
    $controlClass = str_contains($selectClass, 'form-select-sm') ? 'form-control form-control-sm' : 'form-control';
@endphp
<div class="col-md-6">
    <label class="{{ $labelClass }}">{{ $label }} <span class="text-danger">*</span></label>
    <select name="work_location" class="{{ $selectClass }}" data-other-value="{{ $otherLabel }}" required>
        <option value="">{{ $placeholder }}</option>
        @foreach ($locations as $location)
            <option value="{{ $location }}" @selected(!$isOther && $selected === $location)>{{ $location }}</option>
        @endforeach
        <option value="{{ $otherLabel }}" @selected($isOther)>{{ $otherLabel }}</option>
    </select>
    <div class="mt-2 {{ $isOther ? '' : 'd-none' }}" data-work-location-other>
        <label class="{{ $labelClass }}">Lokasi lainnya <span class="text-danger">*</span></label>
        <input type="text" name="work_location_custom" value="{{ $custom }}" class="{{ $controlClass }}"
            maxlength="255" placeholder="Tulis lokasi penempatan" autocomplete="off"
            @if ($isOther) required @endif>
    </div>
</div>
