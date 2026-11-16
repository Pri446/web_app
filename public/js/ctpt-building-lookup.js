(function ($) {
    'use strict';

    $(function () {
        const building = $('select.bin');
        const type = $('#type');
        if (!building.length) return;

        let activeRequest = null;
        let version = 0;

        if (building.data('select2')) building.select2('destroy');
        building.prop('disabled', !type.val()).select2({
            ajax: {
                url: building.attr('data-lookup-url'),
                dataType: 'json',
                delay: 250,
                timeout: 15000,
                data: function (params) {
                    return {toilet_type: type.val(), search: params.term, page: params.page || 1};
                },
                transport: function (params, success, failure) {
                    const requestVersion = version;
                    // Read the current type even when a delayed search started before a type change.
                    params.data.toilet_type = type.val();
                    activeRequest = $.ajax(params);
                    activeRequest.done(function (data) {
                        if (requestVersion === version) success(data);
                    });
                    activeRequest.fail(function (xhr) {
                        if (requestVersion === version) failure(xhr);
                    });
                    return activeRequest;
                },
            },
            placeholder: building.attr('data-placeholder'),
            allowClear: true,
            width: '100%',
            language: {
                errorLoading: function () { return building.attr('data-load-error'); },
            },
        });

        type.on('change.ctptBuildingLookup', function () {
            version++;
            if (activeRequest) activeRequest.abort();
            building.select2('close');
            building.empty().append(new Option('', '', false, false));
            building.val(null).prop('disabled', !type.val()).trigger('change');
        });
    });
})(jQuery);
