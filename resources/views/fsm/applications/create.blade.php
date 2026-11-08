<!-- Last Modified Date: 18-04-2024
Developed By: Innovative Solution Pvt. Ltd. (ISPL)   -->
{{--Extend the main layout--}}
@extends('layouts.dashboard')
{{--Add sections for the main layout--}}
@section('title',  __('Add Application'))
{{--Add sections for the index layout--}}

{{--Include the layout inside the main content section--}}
@section('content')
    @include('layouts.components.error-list')
    @include('layouts.components.success-alert')
    @include('layouts.components.error-alert')
    {!! Form::open(['url' => route('application.store'), 'class' => 'form-horizontal', 'id' => 'create_application_form']) !!}
    <input
        type="hidden"
        name="action_type"
        value="{{ $action_type ?? session('action_type') ?? old('action_type') }}"
    >
    @include('layouts.partial-form', ['submitButtonText' => __('Save'), 'cardForm' => true])
    {!! Form::close() !!}
@endsection

@push('scripts')
<script>
    function toggleANF(checkbox, isUserToggle = false) {
    if (!checkbox) return;
    const textKnown = document.getElementById('text_known');
    const textUnknown = document.getElementById('text_unknown');

    localStorage.setItem("anfCheckboxState", checkbox.checked ? "true" : "false");
 
    if (checkbox.checked) {
        // Checkbox TICKED = House Number Known (ANF mode OFF)
        if (textKnown) textKnown.style.display = '';
        if (textUnknown) textUnknown.style.display = 'none';
        
        // Hide ANF fields, restore normal fields
        $('#anf-address-fields').slideUp(200);
        $('#anf-active-banner').slideUp(200);
        $('#normal-address-fields').slideDown(200);
        $('#is_anf').val('0');
 
        // Re-enable address fields
        $('#road_code, #bin, #containment_id, #ward')
            .not('[data-view-only]')
            .prop('disabled', false);

        if ($('#road_code').data('select2')) {
            $('#road_code').select2('destroy');
        }
        if ($('#bin').data('select2')) {
            $('#bin').select2('destroy');
        }

        $('#bin').select2({
            ajax: {
                url: "{{ route('building.get-house-numbers-containments') }}",
                data: function (params) {
                    return {
                        search: params.term,
                        road_code: $('#road_code').val(),
                        page: params.page || 1
                    };
                },
            },
            placeholder: '{{ __('House Number / BIN') }}',
            allowClear: true,
            closeOnSelect: true,
            width: '100%'
        });

        $('#road_code').select2({
            ajax: {
                url: "{{ route('roadlines.get-road-names') }}",
                data: function (params) {
                    return {
                        search: params.term,
                        bin: $('#bin').val(),
                        page: params.page || 1
                    };
                },
            },
            placeholder: '{{ __('Street Name / Street Code') }}',
            allowClear: true,
            closeOnSelect: true,
            width: '100%'
        });
 
        // Restore Owner Details card
        $('#owner-details-card').slideDown(200);
        $('#owner-details-card input, #owner-details-card select')
            .not('[data-view-only]')
            .prop('disabled', false);

        // Disable & unrequire ANF fields (do not clear values)
        $('#anf_ward, #anf_locality, #anf_nearest_locality').prop('disabled', true).prop('required', false);

        if (isUserToggle) {
            $('#applicant_name').val('');
            $('#applicant_gender').val('');
            $('#applicant_contact').val('');
            localStorage.removeItem("applicant_name");
            localStorage.removeItem("applicant_gender");
            localStorage.removeItem("applicant_contact");
        }

        if ($('#customer_name').val() != '' && $('#customer_gender').val() != '' ) {
            $('#autofill-wrapper').show();
        }
 
    } else {
        // Checkbox UNTICKED = House Number Unknown (ANF mode ON)
        if (textKnown) textKnown.style.display = 'none';
        if (textUnknown) textUnknown.style.display = '';
        
        // Show banner + ANF fields, hide normal fields
        $('#normal-address-fields').slideUp(200);
        $('#anf-active-banner').slideDown(200);
        $('#anf-address-fields').slideDown(200);
        $('#is_anf').val('1');
 
        // Disable normal address fields (do not clear values)
        $('#road_code, #bin, #containment_id, #ward').prop('disabled', true);
 
        // Hide Owner Details card completely and disable inputs
        $('#owner-details-card').slideUp(200);
        $('#owner-details-card input, #owner-details-card select').prop('disabled', true);

        // Enable & require ANF fields
        $('#anf_ward, #anf_locality, #anf_nearest_locality').prop('disabled', false).prop('required', true);

        // Clear Applicant Details ONLY when user explicitly toggled the checkbox
        if (isUserToggle) {
            $('#applicant_name').val('');
            $('#applicant_gender').val('');
            $('#applicant_contact').val('');
            localStorage.removeItem("applicant_name");
            localStorage.removeItem("applicant_gender");
            localStorage.removeItem("applicant_contact");
        }

        // Hide "Same as Owner" checkbox in Applicant Details
        $('#autofill-wrapper').hide();
    }

    // Scheduled application address fields are always view-only, even if the
    // House Number state is toggled.
    if (typeof lockConfirmAddressFields === 'function') {
        lockConfirmAddressFields();
    }
}

    const scheduleAccept = @json(session('schedule_accept'));
    const isConfirm = @json(
        ($action_type ?? session('action_type') ?? old('action_type')) === 'confirm'
    );
    const isScheduleConfirm = Boolean(
        isConfirm && scheduleAccept && scheduleAccept.bin
    );
    const sessionServiceProviderId = @json(
        session('service_provider_id') ?? old('service_provider_id')
    );
    const scheduleRoadText = @json($scheduleRoadText ?? null);
    const scheduleBinText = @json($scheduleBinText ?? null);

    function autoFillDetails() {
        $(document).ready(function() {
            if ($("input[name='autofill']:checked").val() === 'on') {
                $("input[name='applicant_name']").val($("input[name=customer_name]").val());
                $("#applicant_gender").val($("#customer_gender").val());
                $("input[name='applicant_contact']").val($("input[name=customer_contact]").val());
            }
        });
    }

    function setOwnerFieldFromLookup(selector, hiddenName, value) {
        const fieldValue = value === null ||
            value === undefined ||
            value === 'null' ||
            value === 'undefined'
            ? ''
            : value;
        const hasValue = $.trim(String(fieldValue)) !== '';

        $(`input[type="hidden"][name="${hiddenName}"]`).remove();
        const $field = $(selector);
        $field.val(fieldValue).prop('disabled', hasValue);

        if (hasValue) {
            $field.attr('data-view-only', 'lookup');
        } else {
            $field.removeAttr('data-view-only');
        }

        if (hasValue) {
            $('<input>', {
                type: 'hidden',
                name: hiddenName,
                value: fieldValue
            }).insertAfter(selector);
        }
    }

    function lockConfirmAddressFields() {
        if (!isScheduleConfirm) {
            return;
        }

        ['road_code', 'bin', 'containment_id', 'ward'].forEach(
            function (fieldName) {
                const field = document.getElementById(fieldName);

                if (!field) {
                    return;
                }

                const $field = $(field);
                const fallbackValue = scheduleAccept[fieldName] ?? '';
                let fieldValue = $field.val();

                if (
                    (fieldValue === null || fieldValue === '') &&
                    fallbackValue !== null &&
                    fallbackValue !== ''
                ) {
                    fieldValue = String(fallbackValue);

                    if (
                        field.tagName === 'SELECT' &&
                        !$field.find(`option[value="${fieldValue}"]`).length
                    ) {
                        let optionText = fieldValue;
                        if (fieldName === 'road_code' && scheduleRoadText) {
                            optionText = scheduleRoadText;
                        } else if (fieldName === 'bin' && scheduleBinText) {
                            optionText = scheduleBinText;
                        }

                        $field.append(
                            new Option(optionText, fieldValue, true, true)
                        );
                    }

                    $field.val(fieldValue);
                }

                $(`input[type="hidden"]` +
                    `[data-confirm-address="${fieldName}"]`).remove();

                $field.prop('disabled', true);
                $field.attr('data-view-only', 'confirm');

                $('<input>', {
                    type: 'hidden',
                    name: fieldName,
                    value: fieldValue ?? '',
                    'data-confirm-address': fieldName
                }).insertAfter($field);

                if ($field.hasClass('select2-hidden-accessible')) {
                    $field.trigger('change.select2');
                }
            }
        );
    }

    function emptyAutoFields() {
        $('input[type="hidden"][name="customer_name"], ' +
            'input[type="hidden"][name="customer_gender"], ' +
            'input[type="hidden"][name="customer_contact"]').remove();
        $('#containment_id').val('');
        $('#ward').val('');
        $('#customer_name').val('');
        $('#customer_gender').val('');
        $('#customer_contact').val('');
        $('[data-view-only="lookup"]')
            .removeAttr('data-view-only')
            .prop('disabled', false);
        $("input[name='autofill']").prop('checked', false);
        $('#autofill-wrapper').hide();
        $('#containment_info, #accessibility_info').remove();
    }

    function onAddressChange() {
        localStorage.setItem("applicant_name", $('#applicant_name').val());
        localStorage.setItem("applicant_gender", $('#applicant_gender').val());
        localStorage.setItem("applicant_contact", $('#applicant_contact').val());   

        emptyAutoFields();

        $('#applicant_name').val(localStorage.getItem("applicant_name") || '');
        $('#applicant_gender').val(localStorage.getItem("applicant_gender") || '');
        $('#applicant_contact').val(localStorage.getItem("applicant_contact") || '');
        $('#desludging_vehicle_size').val('');
        $('#service_provider_name').val('');
        
        if ($('#bin').find(":selected").text() === 'House Number Known') {
            $('#building-if-address').hide();
            $("#building-if-address :input").each(function () {
                $(this).attr("disabled", true);
            });
            $('#building-if-not-address').show();
            $("#building-if-not-address :input").each(function () {
                $(this).attr("disabled", false);
            });
            $("input[type='submit']").removeAttr('disabled');
        } else {
            $('#building-if-not-address').hide();
            $("#building-if-not-address :input").each(function () {
                $(this).attr("disabled", true);
            });
            $('#building-if-address').show();
            $("#building-if-address :input").each(function () {
                $(this).attr("disabled", false);
            });

            if ($('#bin').val() != '') {
                displayAjaxLoader();
                $.ajax({
                    url: "{{ route('application.get-building-details') }}",
                    data: {
                        "bin": $('#bin').val()
                    },
                    success: function (res) {
                        if (res.status === true) {
                            let containmentOptions = '';
                            res.containments.forEach(function (containment) {
                                containmentOptions += `<option value="${containment}">${containment}</option>`;
                            });

                            setOwnerFieldFromLookup(
                                '#customer_name',
                                'customer_name',
                                res.customer_name
                            );
                            setOwnerFieldFromLookup(
                                '#customer_gender',
                                'customer_gender',
                                res.customer_gender
                            );
                            setOwnerFieldFromLookup(
                                '#customer_contact',
                                'customer_contact',
                                res.customer_contact
                            );
                            $('#household_served').val(res.household_served)
                                .prop('disabled', true).attr('data-view-only', 'lookup');
                            $('#population_served').val(res.population_served)
                                .prop('disabled', true).attr('data-view-only', 'lookup');
                            $('#toilet_count').val(res.toilet_count)
                                .prop('disabled', true).attr('data-view-only', 'lookup');
                           
                            if ($('#customer_name').val() != '' && $('#customer_gender').val() != '' ) {
                                $('#autofill-wrapper').show();
                            } else {
                                $('#autofill-wrapper').hide();
                            }

                            localStorage.setItem("selectedOwnerName", res.customer_name);
                            localStorage.setItem("selectedOwnerGender", res.customer_gender);
                            localStorage.setItem("selectedOwnerContact", res.customer_contact);
                            localStorage.setItem("selectedHouseholdServed", res.household_served);
                            localStorage.setItem("selectedPopulationServed", res.population_served);
                            localStorage.setItem("selectedToiletCount", res.toilet_count);
                            localStorage.setItem("selectedWard", res.ward);
                            $('#ward').val(res.ward).prop('disabled', true)
                                .attr('data-view-only', 'lookup');
                            $('<input>').attr({
                                type: 'hidden',
                                name: 'ward',
                                value: res.ward
                            }).insertAfter('#ward');

                            if (res.containments.length === 1) {
                                $('#containment_id').replaceWith(`
                                    <input id="containment_id" name="containment_id" class="form-control" value="${res.containments[0]}" readonly>
                                `);
                                localStorage.setItem("containment_id", res.containments[0]);

                                $('#containment_info, #accessibility_info').remove();
                            $(`
                                <div id="containment_info" style="font-size: 12px; color: #6c757d; margin-top: 5px;">
                                    <strong>Containment Volume (m³):</strong> ${res.containment_size || 'N/A'}
                                </div>
                                <div id="accessibility_info" style="font-size: 12px; color: #6c757d; margin-top: 5px;">
                                    <strong>Building Road Accessibility (m):</strong> ${res.building_accessible !== null ? (res.building_accessible ? "Yes" : "No") : 'N/A'}
                                </div>
                            `).insertAfter('#containment_id');
                            } else {
                                $('#containment_id').replaceWith(`
                                    <select id="containment_id" name="containment_id" class="form-control">
                                        ${containmentOptions}
                                    </select>
                                `);

                                if (
                                    scheduleAccept &&
                                    scheduleAccept.containment_id
                                ) {
                                    $('#containment_id')
                                        .val(String(scheduleAccept.containment_id))
                                        .trigger('change');
                                }

                                let selectedContainment = localStorage.getItem("containment_id");
                                $('#containment_id').on('change', function() {
                                    let selectedId = $(this).val();
                                    $('#containment_info, #accessibility_info').remove();
                                    if (selectedId) {
                                        $(`
                                            <div id="containment_info" style="font-size: 12px; color: #6c757d; margin-top: 5px;">
                                                <strong>Containment Volume (m³):</strong> ${res.containment_size || 'N/A'}
                                            </div>
                                            <div id="accessibility_info" style="font-size: 12px; color: #6c757d; margin-top: 5px;">
                                                <strong>Building Road Accessibility (m):</strong> ${res.building_accessible !== null ? (res.building_accessible ? "Yes" : "No") : 'N/A'}
                                            </div>
                                        `).insertAfter('#containment_id');
                                    }
                                });
                            }

                            lockConfirmAddressFields();

                            $("input[type='submit']").removeAttr('disabled');
                        } else if (res.status === false) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Oops...',
                                text: "There is an ongoing application for this address!",
                            });
                            emptyAutoFields();
                            $("input[type='submit']").attr('disabled', 'disabled');
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Oops...',
                                text: "Error!",
                            });
                            emptyAutoFields();
                        }
                        removeAjaxLoader();
                    },
                    error: function (err) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: err.responseJSON.error,
                        });
                        emptyAutoFields();
                        $("input[type='submit']").attr('disabled', 'disabled');
                    }
                });
            }
        }
    }

    $(document).ready(function() {
        $('#applicant_name, #applicant_gender, #applicant_contact').on('input change', function() {
            localStorage.setItem("applicant_name", $('#applicant_name').val());
            localStorage.setItem("applicant_gender", $('#applicant_gender').val());
            localStorage.setItem("applicant_contact", $('#applicant_contact').val());
        });

        $('#customer_name, #customer_gender, #customer_contact').on('input change', function() {
            const fieldName = $(this).attr('name');
            const fieldValue = $(this).val();

            if (fieldValue && fieldValue.trim() !== '') {
                $(`input[name="${fieldName}"][type="hidden"]`).remove();
            
                $('<input>').attr({
                    type: 'hidden',
                    name: fieldName,
                    value: fieldValue
                }).insertAfter(`#${$(this).attr('id')}`);
            }
            const ownerName = $('#customer_name').val();
            const ownerGender = $('#customer_gender').val();
            const ownerContact = $('#customer_contact').val();

            if (ownerName) localStorage.setItem("selectedOwnerName", ownerName);
            else localStorage.removeItem("selectedOwnerName");

            if (ownerGender) localStorage.setItem("selectedOwnerGender", ownerGender);
            else localStorage.removeItem("selectedOwnerGender");

            if (ownerContact) localStorage.setItem("selectedOwnerContact", ownerContact);
            else localStorage.removeItem("selectedOwnerContact");
        });
        const formSubmitted = localStorage.getItem("formSubmitted");

        if (formSubmitted === "true" && $('.alert.alert-danger.alert-dismissible').length === 0) {
            localStorage.removeItem("anfCheckboxState");
            localStorage.removeItem("formSubmitted");
        }

        // Default state: House Number Known (checked = true)
        const anfCheckboxEl = document.getElementById('anf_checkbox');
        if (anfCheckboxEl) {
            if ('{{ old('is_anf') }}' === '1') {
                $('#anf_checkbox').prop('checked', false);
                toggleANF(anfCheckboxEl);
            } else {
                $('#anf_checkbox').prop('checked', true);
                toggleANF(anfCheckboxEl);
            }
        }

        $('#bin').prepend('<option selected=""></option>').select2({
            ajax: {
                url: "{{ route('building.get-house-numbers-containments') }}",
                data: function (params) {
                    return {
                        search: params.term,
                        road_code: $('#road_code').val(),
                        page: params.page || 1
                    };
                },
            },
            placeholder: '{{ __('House Number / BIN') }}',
            allowClear: true,
            closeOnSelect: true,
            width: '100%'
        });

        $('#road_code').prepend('<option selected=""></option>').select2({
            ajax: {
                url: "{{ route('roadlines.get-road-names') }}",
                data: function (params) {
                    return {
                        search: params.term,
                        bin: $('#bin').val(),
                        page: params.page || 1
                    };
                },
            },
            placeholder: '{{ __('Street Name / Street Code') }}',
            allowClear: true,
            closeOnSelect: true,
            width: '100%'
        });

        if ('{{ old('address') }}' !== '') {
            $('#address').select2().val('{{ old('address') }}').trigger('change');
            onAddressChange();
        }

        if (isScheduleConfirm && scheduleAccept) {
            if (scheduleAccept.road_code) {
                const roadText = scheduleRoadText || scheduleAccept.road_code;
                if (!$('#road_code option[value="' + scheduleAccept.road_code + '"]').length) {
                    $('#road_code').append(new Option(roadText, scheduleAccept.road_code, true, true));
                }
                $('#road_code').val(scheduleAccept.road_code).trigger('change.select2');
            }
            if (scheduleAccept.bin) {
                const binText = scheduleBinText || scheduleAccept.bin;
                if (!$('#bin option[value="' + scheduleAccept.bin + '"]').length) {
                    $('#bin').append(new Option(binText, scheduleAccept.bin, true, true));
                }
                $('#bin').val(scheduleAccept.bin).trigger('change.select2');
            }
            onAddressChange();
        }

        

        $('#bin').on('change', onAddressChange);
        $('#customer_name, #customer_gender, #customer_contact').on('change keyup input', function() {
            if ($('#customer_name').val() != '' && $('#customer_gender').val() != '' ) {
                if ($('#is_anf').val() !== '1') {
                    $('#autofill-wrapper').show();
                }
            } else {
                $('#autofill-wrapper').hide();
                if ($('#autofill').is(':checked')) {
                    $('#autofill').prop('checked', false);
                }
            }
        });

       $('#create_application_form').on('submit', function (e) {
        localStorage.setItem("formSubmitted", "true");

        if (isScheduleConfirm) {
            lockConfirmAddressFields();
        } else if ($('#is_anf').val() === '1') {
            // ANF (House Number Unknown) mode:
            // Disable normal address fields & owner details inputs so they are NOT sent in POST payload
            $('#road_code, #bin, #containment_id, #ward').prop('disabled', true);
            $('#owner-details-card input, #owner-details-card select').prop('disabled', true);

            // Enable ANF fields so they ARE sent
            $('#anf_ward, #anf_locality, #anf_nearest_locality').prop('disabled', false);
        } else {
            // Normal (House Number Known) mode:
            // Disable ANF fields so they are NOT sent in POST payload
            $('#anf_ward, #anf_locality, #anf_nearest_locality').prop('disabled', true);

            // Enable normal address fields & owner details inputs so intended fields are sent
            $('#road_code, #bin, #ward, #containment_id').prop('disabled', false);
            $('#owner-details-card input, #owner-details-card select').prop('disabled', false);
        }
    });

           if ($('.alert.alert-danger.alert-dismissible').length == 0) {
            localStorage.removeItem("selectedRoadCode");
            localStorage.removeItem("selectedRoadValue");
            localStorage.removeItem("selectedOwnerName");
            localStorage.removeItem("selectedOwnerGender");
            localStorage.removeItem("selectedOwnerContact");
            localStorage.removeItem("selectedBINValue");
            localStorage.removeItem("selectedBINText");
            localStorage.removeItem("containment_id");
            localStorage.removeItem("selectedHouseholdServed");
            localStorage.removeItem("selectedPopulationServed");
            localStorage.removeItem("selectedServiceProviderText");
            localStorage.removeItem("selectedServiceProviderValue");
            localStorage.removeItem("selectedToiletCount");
            localStorage.removeItem("selectedWard");
            localStorage.removeItem("service_provider_name");
            localStorage.removeItem("service_provider_id");
            localStorage.removeItem("applicant_name");
            localStorage.removeItem("applicant_gender");
            localStorage.removeItem("applicant_contact");
            localStorage.removeItem("anfCheckboxState");

        } else {
            // Retrieve values from localStorage and populate the form
            const selectedRoadCode = localStorage.getItem("selectedRoadCode");
            const selectedRoadValue = localStorage.getItem("selectedRoadValue");
            const selectedWard = localStorage.getItem("selectedWard");
            const selectedBINValue = localStorage.getItem("selectedBINValue");
            const selectedBINText = localStorage.getItem("selectedBINText");
            const selectedOwnerName = localStorage.getItem("selectedOwnerName");
            const selectedOwnerGender = localStorage.getItem("selectedOwnerGender");
            const selectedOwnerContact = localStorage.getItem("selectedOwnerContact");
            const selectedHouseholdServed = localStorage.getItem("selectedHouseholdServed");
            const selectedPopulationServed = localStorage.getItem("selectedPopulationServed");
            const selectedToiletCount = localStorage.getItem("selectedToiletCount");
            const service_provider_id = localStorage.getItem("service_provider_id");
            const service_provider_name = localStorage.getItem("service_provider_name");
            const applicantName = localStorage.getItem("applicant_name");
            const applicantGender = localStorage.getItem("applicant_gender");
            const applicantContact = localStorage.getItem("applicant_contact");

            if (selectedRoadCode) {
                var roadCode = selectedRoadValue;
                $('#road_code').val(selectedRoadCode); // Set road code from localStorage
            }
            if (selectedWard) {
                $('#ward').val(selectedWard).prop('disabled', true)
                    .attr('data-view-only', 'lookup');
            }
        

            $('#containment_id').prop('disabled', true);

            // Populate form fields with localStorage data
            if (selectedRoadValue) $('#road_code').val(selectedRoadValue);
            if (selectedBINValue) $('#bin').val(selectedBINValue);
            if (selectedOwnerName && selectedOwnerName !== 'null')
                $('#customer_name').val(selectedOwnerName).prop('disabled', true)
                    .attr('data-view-only', 'lookup');
            if (selectedOwnerGender && selectedOwnerGender !== 'null')
                $('#customer_gender').val(selectedOwnerGender).prop('disabled', true)
                    .attr('data-view-only', 'lookup');
            if (selectedOwnerContact && selectedOwnerContact !== 'null')
                $('#customer_contact').val(selectedOwnerContact).prop('disabled', true)
                    .attr('data-view-only', 'lookup');
            if (selectedHouseholdServed) {
                $('#household_served').val(selectedHouseholdServed).prop('disabled', true)
                    .attr('data-view-only', 'lookup');
            }
            if (selectedPopulationServed) {
                $('#population_served').val(selectedPopulationServed).prop('disabled', true)
                    .attr('data-view-only', 'lookup');
            }
            if (selectedToiletCount) {
                $('#toilet_count').val(selectedToiletCount).prop('disabled', true)
                    .attr('data-view-only', 'lookup');
            }
            if (applicantName && applicantName !== 'null' && $('#applicant_name').val() == '') $('#applicant_name').val(applicantName);
            if (applicantGender && applicantGender !== 'null' && $('#applicant_gender').val() == '') $('#applicant_gender').val(applicantGender);
            if (applicantContact && applicantContact !== 'null' && $('#applicant_contact').val() == '') $('#applicant_contact').val(applicantContact);
            if (selectedOwnerName && selectedOwnerName !== 'null' &&
                selectedOwnerGender && selectedOwnerGender !== 'null' &&
                selectedOwnerContact && selectedOwnerContact !== 'null') {
                $('#autofill-wrapper').show();
            } else {
                $('#autofill-wrapper').hide();
            }

            // Update road code select2 with stored value
            optionHtmlRoadCode = selectedRoadCode
                ? `<option value="${roadCode}" selected="selected">${selectedRoadCode}</option>`
                : `<option selected=""></option>`;
            $('#road_code').prepend(optionHtmlRoadCode).select2({
                ajax: {
                    url: "{{ route('roadlines.get-road-names') }}",
                    data: function (params) {
                        return {
                            search: params.term,
                        bin: $('#bin').val(),
                        page: params.page || 1
                        };
                    },
                },
                placeholder: 'Street Name / Street Code',
                allowClear: true,
                closeOnSelect: true,
                width: '100%',
            });

            // Update bin select2 with stored value
            optionHtmlBIN = selectedBINValue
                ? `<option value="${selectedBINValue}" selected="selected">${selectedBINText}</option>`
                : `<option selected=""></option>`;

            $('#bin').prepend(optionHtmlBIN).select2({
                ajax: {
                    url: "{{ route('building.get-house-numbers-containments') }}",
                    data: function (params) {
                        return {
                            search: params.term,
                            road_code: $('#road_code').val(),
                            page: params.page || 1
                        };
                    },
                },
                placeholder: 'House Number / BIN',
                allowClear: true,
                closeOnSelect: true,
                width: '100%',
            });
        }
        const savedApplicantName = localStorage.getItem("applicant_name");
        const savedApplicantGender = localStorage.getItem("applicant_gender");
        const savedApplicantContact = localStorage.getItem("applicant_contact");

        if (savedApplicantName && savedApplicantName !== 'null' && !$('#applicant_name').val()) $('#applicant_name').val(savedApplicantName);
        if (savedApplicantGender && savedApplicantGender !== 'null' && !$('#applicant_gender').val()) $('#applicant_gender').val(savedApplicantGender);
        if (savedApplicantContact && savedApplicantContact !== 'null' && !$('#applicant_contact').val()) $('#applicant_contact').val(savedApplicantContact);
        // Store selected values in localStorage
        $('#road_code').on('change', function() {
            var selectedRoadCode = $(this).find('option:selected').text();
            localStorage.setItem("selectedRoadCode", selectedRoadCode);
            var selectedRoadValue = $(this).find('option:selected').attr('value');
            localStorage.setItem("selectedRoadValue", selectedRoadValue);
        });

        $('#service_provider_name_display').on('input', function () {
            localStorage.setItem(
                'service_provider_name',
                $(this).val()
            );
        });

        // Auto-select road_code when a BIN is chosen
    $('#bin').on('change', function() {
        var selectedBINValue = $(this).find('option:selected').attr('value');
        var selectedBINText = $(this).find('option:selected').text();
        localStorage.setItem("selectedBINValue", selectedBINValue);
        localStorage.setItem("selectedBINText", selectedBINText);

        if (!$(this).val()) return;

        // Fetch road code associated with the selected BIN
        if (selectedBINValue) {
            $.ajax({
                url: "{{ route('roadlines.get-road-names') }}",
                data: { bin: selectedBINValue, search: '' },
                success: function(data) {
                    var items = data.results || (Array.isArray(data) ? data : []);
                    if (items.length === 1) {
                        var road = items[0];
                        // Set the road_code select2 value
                        var roadOption = new Option(road.text, road.id, true, true);
                        $('#road_code').empty().append(roadOption).trigger('change');
                        localStorage.setItem("selectedRoadCode", road.text);
                        localStorage.setItem("selectedRoadValue", road.id);
                    } else if (items.length > 1) {
                        // If multiple roads, optionally open the dropdown for manual selection
                        $('#road_code').val(null).trigger('change');
                    }
                },
                error: function() {
                    console.error("Failed to fetch road code for BIN");
                }
            });
        }

        $('#bin').select2('open');
        });

        $('#customer_name, #customer_gender, #customer_contact')
            .on('input change', function () {
                if ($('#autofill').is(':checked')) {
                    autoFillDetails();
                }
        });

        checkDetailsAndUpdateCheckbox();
        // Function to check if the Owner and Applicant details are the same
    function checkDetailsAndUpdateCheckbox() {
        // Get the values of the Owner and Applicant Details
        const ownerDetails = {
            name: document.getElementById('customer_name').value,
            gender: document.getElementById('customer_gender').value,
            contact: document.getElementById('customer_contact').value
        };

        const applicantDetails = {
            name: document.getElementById('applicant_name').value,
            gender: document.getElementById('applicant_gender').value,
            contact: document.getElementById('applicant_contact').value
        };

        // Get the checkbox element
        const sameAsOwnerCheckbox = document.getElementById('autofill');
        const areOwnerDetailsValid = ownerDetails.name !== "" && ownerDetails.gender !== "" && ownerDetails.contact !== "";
        const areApplicantDetailsValid = applicantDetails.name !== "" && applicantDetails.gender !== "" && applicantDetails.contact !== "";


        // Compare Owner and Applicant details
        const isSame = areOwnerDetailsValid && areApplicantDetailsValid &&
                   ownerDetails.name === applicantDetails.name &&
                   ownerDetails.gender === applicantDetails.gender &&
                   ownerDetails.contact === applicantDetails.contact;

        // Update checkbox state based on comparison
        sameAsOwnerCheckbox.checked = isSame;
    }
        // Attach event listeners to Applicant fields to check when any field changes
        document.getElementById('applicant_name').addEventListener('input', checkDetailsAndUpdateCheckbox);
        document.getElementById('applicant_gender').addEventListener('input', checkDetailsAndUpdateCheckbox);
        document.getElementById('applicant_contact').addEventListener('input', checkDetailsAndUpdateCheckbox);
   

    });

    document.addEventListener('DOMContentLoaded', () => {
  // 1) Grab your select by its id
  const sel = document.getElementById('desludging_vehicle_size');

  // 2) Listen for changes
  sel.addEventListener('change', async () => {
    const selectValue = sel.value;
    if (!selectValue) return;

    try {
      // 3) Fire a POST request with the capacity
      const res = await fetch("{{ route('sequence.byCapacity') }}", {
        method: 'POST',   // must be POST to send a JSON body
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document
            .querySelector('meta[name="csrf-token"]')
            .getAttribute('content'),
        },
        body: JSON.stringify({ capacity: selectValue })
      });

      const data = await res.json();

    } catch (err) {
      console.error('Request failed:', err);
    }
  });
});

document.addEventListener('DOMContentLoaded', () => {
    const sizeSel = document.getElementById('desludging_vehicle_size');
    const nameInput = document.getElementById('service_provider_name');
    const idInput = document.getElementById('service_provider_id');

    sizeSel.addEventListener('change', async () => {
        const capacity = sizeSel.value;
        if (!capacity) return;

        try {
            const res = await fetch("{{ route('sequence.byCapacity') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ capacity: Number(capacity) })
            });

            const data = await res.json();
            const spName = data.next_name ?? '';
            const spId   = data.next_id ?? '';

            // set form values
            nameInput.value = spName;
            idInput.value   = spId;

    
            localStorage.setItem('service_provider_name', spName);
            localStorage.setItem('service_provider_id', spId);

        } catch (e) {
            nameInput.value = '';
            idInput.value = '';
            localStorage.removeItem('service_provider_name');
            localStorage.removeItem('service_provider_id');
        }
    });
        const storedName = localStorage.getItem('service_provider_name');
        const storedId   = localStorage.getItem('service_provider_id');

        if (storedName) nameInput.value = storedName;
        if (storedId)   idInput.value   = storedId;
});


</script>

@endpush
