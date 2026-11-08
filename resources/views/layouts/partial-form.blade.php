<!-- Last Modified Date: 18-04-2024
Developed By: Innovative Solution Pvt. Ltd. (ISPL)   -->
{{--
A dynamic form layout
--}}

@if(!empty($cardForm))
    <div class="col-sm-12 col-md-8 col-lg-8">
    @foreach($formFields as $group)
            <div class="card" @if(!empty($group['id'])) id="{{ $group['id'] }}" @endif @if(!empty($group['hidden'])) @if($group['hidden'] === true) style="display: none" @endif @endif>
                <div class="card-header">
                    {{ $group['title'] }}
                    @if(!empty($group["copyDetails"]))
                        @if($group["copyDetails"])
                            <div class="clearfix float-right" id="autofill-wrapper" style="display: none;">
                                <div class="icheck-primary d-inline">
                                    <input type="checkbox" name="autofill" id="autofill" onclick="autoFillDetails()">
                                    <label for="autofill">
                                    {{ __('Same as Owner') }}
                                    </label>
                                </div>
                            </div>
                        @endif
                    @endif

                   {{-- ✅ ANF toggle — only renders on Address card --}}
                    @if(!empty($group["anfCheckbox"]) && $group["anfCheckbox"])
                        <div class="clearfix float-right">
                            <!-- Added iCheck wrapper classes below -->
                            <div class="icheck-primary d-inline">
                                <input type="checkbox" name="anf_checkbox" id="anf_checkbox" onclick="toggleANF(this)" checked>
                                <label for="anf_checkbox">
                                    <span class="text-known" id="text_known">{{ __('House Number Known') }}</span>
                                    <span class="text-unknown" id="text_unknown" style="display: none;">{{ __('House Number Known') }}</span>
                                </label>
                            </div>
                        </div>
                    @endif

                </div>
                <div class="card-body">

                    @if(!empty($group["anfCheckbox"]) && $group["anfCheckbox"])

                        {{-- Warning banner, hidden by default --}}
                        <div id="anf-active-banner" style="display:none; margin-bottom:14px;">
                            <div style="background:#fff8e1; border:1.5px solid #f4a261; border-radius:6px; padding:10px 14px; color:#7a4a00; font-size:0.85rem;">
                                <strong>⚠ {{__('House Number Unknown')}}</strong> — {{__('Fill in the manual fields below. Street')}} &amp; {{__('BIN are disabled')}}.
                            </div>
                        </div>

                        {{-- Normal address fields — hidden when ANF active --}}
                        <div id="normal-address-fields">
                            @foreach($group['fields'] as $field)
                            <div class="form-group row @if($field->required) required @endif" @if(!empty($field->hidden)) @if($field->hidden === true) style="display: none" @endif @endif>
                                {!! Form::label($field->labelFor,$field->label,['class' => $field->labelClass]) !!}
                                <div class="col-sm-4">
                                    @if($field->inputType === 'text')
                                        {!! Form::text($field->inputId,$field->inputValue,['class' => $field->inputClass, 'placeholder' => $field->placeholder,'disabled' => $field->disabled,'autocomplete'=>$field->autoComplete, 'oninput'=>$field->oninput]) !!}
                                        @if($field->disabled)
                                        {!! Form::hidden($field->inputId,$field->inputValue) !!}
                                        @endif
                                    @endif
                                    @if($field->inputType === 'number')
                                        {!! Form::number($field->inputId,$field->inputValue,['class' => $field->inputClass, 'placeholder' => $field->placeholder,'disabled' => $field->disabled, 'oninput'=>$field->oninput]) !!}
                                    @endif
                                    @if($field->inputType === 'select')
                                        {!! Form::select($field->inputId,$field->selectValues,$field->selectedValue,['class' => $field->inputClass, 'placeholder' => $field->placeholder,'disabled' => $field->disabled]) !!}
                                        @if($field->disabled)
                                        {!! Form::hidden($field->inputId,$field->selectedValue) !!}
                                        @endif
                                    @endif
                                    @if($field->inputType === 'label')
                                        {!! Form::label($field->inputId,$field->labelValue, ['class' => $field->inputClass, 'placeholder' => $field->placeholder, 'disabled' => $field->disabled]) !!}
                                    @endif
                                    @if($field->inputType === 'radio')
                                        {!! Form::radio($field->inputId,$field->labelValue,['class' => $field->inputClass,'disabled' => $field->disabled]) !!}
                                    @endif
                                    @if($field->inputType === 'multiple-select')
                                        {!! Form::select($field->inputId,$field->selectValues,$field->selectedValue,['class' => $field->inputClass,'disabled' => $field->disabled]) !!}
                                    @endif
                                    @if($field->inputType === 'date')
                                        {!! Form::date($field->inputId, $field->inputValue, ['onclick' => 'this.showPicker()', 'class' => $field->inputClass, 'disabled' => $field->disabled, 'autocomplete' => 'off']) !!}
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        </div>

                        {{-- ✅ ANF manual fields — same label/input classes as the rest of the form --}}
                        <div id="anf-address-fields" style="display:none;">
                            <input type="hidden" name="is_anf" id="is_anf" value="0">

                            <div class="form-group row required">
                                {!! Form::label('anf_ward', __('Ward Number'), ['class' => 'col-sm-4 col-form-label']) !!}
                                <div class="col-sm-4">
                                    {!! Form::select('anf_ward', $anfWardOptions ?? [], null, ['class' => 'form-control', 'placeholder' => __('Ward Number'), 'id' => 'anf_ward']) !!}
                                </div>
                            </div>

                            <div class="form-group row required">
                                {!! Form::label('anf_locality', __('Locality Name'), ['class' => 'col-sm-4 col-form-label']) !!}
                                <div class="col-sm-4">
                                    {!! Form::text('anf_locality', null, ['class' => 'form-control', 'placeholder' => __('Locality Name'), 'id' => 'anf_locality']) !!}
                                </div>
                            </div>

                            <div class="form-group row required">
                                {!! Form::label('anf_nearest_locality', __('Nearest Landmark'), ['class' => 'col-sm-4 col-form-label']) !!}
                                <div class="col-sm-4">
                                    {!! Form::text('anf_nearest_locality', null, ['class' => 'form-control', 'placeholder' => __('Nearest Landmark'), 'id' => 'anf_nearest_locality']) !!}
                                </div>
                            </div>
                        </div>

                    @else
                        {{-- ALL OTHER CARDS: 100% original, not touched --}}
                        @foreach($group['fields'] as $field)
                        <div class="form-group row @if($field->required) required @endif" @if(!empty($field->hidden)) @if($field->hidden === true) style="display: none" @endif @endif>
                            {!! Form::label($field->labelFor,$field->label,['class' => $field->labelClass]) !!}
                            <div class="col-sm-4">
                                @if($field->inputType === 'text')
                                    {!! Form::text($field->inputId,$field->inputValue,['class' => $field->inputClass, 'placeholder' => $field->placeholder,'disabled' => $field->disabled,'autocomplete'=>$field->autoComplete, 'oninput'=>$field->oninput]) !!}
                                    @if($field->disabled)
                                    {!! Form::hidden($field->inputId,$field->inputValue) !!}
                                    @endif
                                @endif
                                @if($field->inputType === 'number')
                                    {!! Form::number($field->inputId,$field->inputValue,['class' => $field->inputClass, 'placeholder' => $field->placeholder,'disabled' => $field->disabled, 'oninput'=>$field->oninput]) !!}
                                @endif
                                @if($field->inputType === 'select')
                                    {!! Form::select($field->inputId,$field->selectValues,$field->selectedValue,['class' => $field->inputClass, 'placeholder' => $field->placeholder,'disabled' => $field->disabled]) !!}
                                    @if($field->disabled)
                                    {!! Form::hidden($field->inputId,$field->selectedValue) !!}
                                    @endif
                                @endif
                                @if($field->inputType === 'label')
                                    {!! Form::label($field->inputId,$field->labelValue, ['class' => $field->inputClass, 'placeholder' => $field->placeholder, 'disabled' => $field->disabled]) !!}
                                @endif
                                @if($field->inputType === 'radio')
                                    {!! Form::radio($field->inputId,$field->labelValue,['class' => $field->inputClass,'disabled' => $field->disabled]) !!}
                                @endif
                                @if($field->inputType === 'multiple-select')
                                    {!! Form::select($field->inputId,$field->selectValues,$field->selectedValue,['class' => $field->inputClass,'disabled' => $field->disabled]) !!}
                                @endif
                                @if($field->inputType === 'date')
                                {!! Form::date($field->inputId, $field->inputValue, [
                                    'onclick' => 'this.showPicker()',
                                    'class' => $field->inputClass,
                                    'disabled' => $field->disabled,
                                    'autocomplete' => 'off'
                                ]) !!}
                                @endif
                                @if($field->inputType === 'file_viewer')
                                        <div class="input-group mb-3">
                                            <div class="input-group-prepend">
                                                <a href="{{ $formField->fileUrl }}"><button type="button" class="btn btn-info"><i class="fa-solid fa-eye"></i></button></a>
                                            </div>
                                            <input type="text" class="form-control" disabled value="{{ $formField->labelValue }}">
                                        </div>
                                @endif
                                @if($field->inputType === 'point_geom_drawer')
                                    <div class="input-group mb-3">
                                         <div id="map">
                                         </div>
                                        <input type="hidden" name="latitude" id="latitude" class="form-control">
                                        <input type="hidden" name="longitude" id="longitude" class="form-control">
                                        @push('scripts')
                                            <script>
                                                $(document).ready(function () {
                                                    pointGeomDrawer(
                                                        {
                                                            workspace:'{{ Config::get("constants.GEOSERVER_WORKSPACE") }}',
                                                            geoserverUrl:'{{ Config::get("constants.GEOSERVER_URL") }}',
                                                            authKey:'{{ Config::get("constants.AUTH_KEY") }}',
                                                            mapID:'map'
                                                        }
                                                    )
                                                });
                                            </script>
                                        @endpush
                                    </div>
                                    @endif
                                @if($field->inputType === 'geom_viewer')
                                        <div class="input-group mb-3">
                                            <div id="map" style="width: 100%;height: 500px">
                                            </div>
                                            @push('scripts')
                                                <script>
                                                    $(document).ready(function () {
                                                        geomViewer(
                                                            {
                                                                workspace:'{{ Config::get("constants.GEOSERVER_WORKSPACE") }}',
                                                                geoserverUrl:'{{ Config::get("constants.GEOSERVER_URL") }}',
                                                                authKey:'{{ Config::get("constants.AUTH_KEY") }}',
                                                                mapID:'map',
                                                                geom: '{{ $formField->inputValue }}'
                                                            }
                                                        )
                                                    });
                                                </script>
                                            @endpush
                                        </div>
                                    @endif
                            </div>
                        </div>
                        @endforeach
                    @endif

                </div>
            </div>
    @endforeach
    </div>
@else
    @foreach($formFields as $formField)
    <div class="col-sm-12 col-md-8 col-lg-8">
        <div class="form-group row @if($formField->required) required @endif" @if(!empty($formField->hidden)) @if($formField->hidden === true) style="display: none" @endif @endif>
            {!! Form::label($formField->labelFor,$formField->label,['class' => $formField->labelClass,'disabled' => $formField->disabled]) !!}
            <div class="col-sm-4">
                @if($formField->inputType === 'text')
                    {!! Form::text($formField->inputId,$formField->inputValue,['class' => $formField->inputClass, 'placeholder' => $formField->placeholder,'disabled' => $formField->disabled,'autocomplete'=>$formField->autoComplete, 'oninput'=>$formField->oninput]) !!}
                     @if(!empty($formField->disabled))
                        {!! Form::hidden($formField->inputId, $formField->inputValue) !!}
                    @endif
                @endif
                @if($formField->inputType === 'textarea')
                    {!! Form::textarea($formField->inputId, $formField->inputValue, ['class' => 'form-control', 'disabled' => $formField->disabled , 'rows' => 4, 'cols' => 54, 'style' => 'resize:none']) !!}
                      @if($formField->disabled)
                            {!! Form::hidden($formField->inputId, $formField->inputValue) !!}
                        @endif
                @endif
                @if($formField->inputType === 'number')
                    {!! Form::number($formField->inputId,$formField->inputValue,['class' => $formField->inputClass, 'placeholder' => $formField->placeholder,'disabled' => $formField->disabled, 'oninput'=>$formField->oninput]) !!}
                @endif
                @if($formField->inputType === 'select')
                    {!! Form::select($formField->inputId,$formField->selectValues,$formField->selectedValue,['class' => $formField->inputClass, 'placeholder' => $formField->placeholder,'disabled' => $formField->disabled]) !!}
                    @if($formField->disabled)
                    {!! Form::hidden($formField->inputId,$formField->selectedValue) !!}
                    @endif
                    @endif
                @if($formField->inputType === 'label')
                    {!! Form::label($formField->inputId,$formField->labelValue,['class' => $formField->inputClass,'disabled' => $formField->disabled,]) !!}
                @endif
                @if($formField->inputType === 'radio')
                    {!! Form::radio($formField->inputId,$formField->labelValue,['class' => $formField->inputClass,'disabled' => $formField->disabled,]) !!}
                @endif
                @if($formField->inputType === 'multiple-select')
                    {!! Form::select($formField->inputId,$formField->selectValues,$formField->selectedValue,['class' => $formField->inputClass,'disabled' => $formField->disabled,]) !!}
                    @push('scripts')
                        <script>
                            $(document).ready(function() {
                                $('#{{ $formField->inputId }}').prepend('<option selected=""></option>').append('<option value="-1">Address Not Found</option>').select2({
                                    placeholder: '{{ $formField->placeholder }}',
                                    matcher: function(params, data) {
                                        if (data.id === "-1") {
                                            return data;
                                        } else {
                                            return $.fn.select2.defaults.defaults.matcher.apply(this, arguments);
                                        }
                                    },
                                    closeOnSelect: true,
                                    width: 'select'
                                });
                            });
                        </script>
                    @endpush
                @endif
                @if($formField->inputType === 'date_time')
                    {!! Form::datetimeLocal($formField->inputId,$formField->inputValue,['class' => $formField->inputClass]) !!}
                @endif
                @if($formField->inputType === 'date')
                {!! Form::date($formField->inputId, $formField->inputValue, ['onclick' => 'this.showPicker()', 'class' => $formField->inputClass, 'disabled' => $formField->disabled]) !!}
                @endif
                    @if($formField->inputType === 'year')
                        <input type="text" id="{{ $formField->inputId }}" name="{{ $formField->inputId }}" value="{{ $formField->inputValue }}" class="{{ $formField->inputClass }}" autocomplete="off" placeholder="{{ $formField->placeholder }}">
                        @push('scripts')
                            <script>
                                $(document).ready(function () {
                                    $('#{{$formField->inputId}}').datepicker({
                                        format: "yyyy",
                                        minViewMode: "years",
                                        autoclose: true,
                                        startDate:'today',
                                        container:$('#{{ $formField->inputId }}').parent()
                                    });
                                })
                            </script>
                        @endpush
                    @endif
                @if($formField->inputType === 'time')
                    {!! Form::time($formField->inputId,$formField->inputValue,['class' => $formField->inputClass]) !!}
                @endif
                @if($formField->inputType === 'hidden')
                    {!! Form::hidden($formField->inputId,$formField->inputValue,['class' => $formField->inputClass]) !!}
                    {!! Form::text($formField->inputId."_disabled",$formField->inputValue,['class' => $formField->inputClass,'disabled'=>'disabled','id'=>$formField->inputId."_disabled"]) !!}
                @endif
                @if($formField->inputType === 'image')
                    {!! Form::file($formField->inputId,['class' => $formField->inputClass]) !!}
                @endif
                @if($formField->inputType === 'file_viewer')
                    <div class="input-group mb-3">
                        <div class="input-group-prepend">
                        <a href="{{ $formField->fileUrl }}" target="_blank"><button type="button" class="btn btn-info" {{ !$formField->fileUrl ? 'disabled' : '' }}><i class="fa-solid fa-eye"></i></button></a>
                        </div>
                        <input type="text" class="form-control" disabled value="{{ $formField->labelValue }}">
                    </div>
                @endif
               @if($formField->inputType === 'file_upload')
                    @if($formField->inputId === 'house_image' )
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" name="{{ $formField->inputId }}" id="{{ $formField->inputId }}" accept="image/*">
                            <label class="custom-file-label" for="{{ $formField->inputId }}">
                                {{ $formField->inputValue ? basename($formField->inputValue) : 'Choose file' }}
                            </label>
                        </div>
                       @if(!empty($formField->inputValue))
                        <input type="hidden" name="{{ $formField->inputId }}_existing" value="{{ $formField->inputValue }}">
                        @endif
                        <small class="form-text" id="fileSizeHintImg">(Image (JPG, JPEG) size should not be more than 5MB)</small>
                    @elseif($formField->inputId === 'receipt_image')
                          <div class="custom-file">
                                <input type="file" class="custom-file-input" name="{{ $formField->inputId }}" id="{{ $formField->inputId }}" accept="image/*">
                            <label class="custom-file-label" for="{{ $formField->inputId }}">
                                {{ $formField->inputValue ? basename($formField->inputValue) : 'Choose file' }}
                            </label>
                        </div>
                       @if(!empty($formField->inputValue))
                        <input type="hidden" name="{{ $formField->inputId }}_existing" value="{{ $formField->inputValue }}">
                        @endif
                        <small class="form-text" id="fileSizeRintImg">(Image (JPG, JPEG) size should not be more than 5MB)</small>
                    @else
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" name="{{ $formField->inputId }}" id="{{ $formField->inputId }}">
                            <label class="custom-file-label" for="{{ $formField->labelFor }}">
                                {{ $formField->inputValue ?? 'Choose file' }}
                            </label>
                        </div>
                        <small class="form-text" id="fileSizeRintImg">(Image (JPG, JPEG) size should not be more than 5MB)</small>
                    @endif
                @endif
                @if($formField->inputType === 'point_geom_drawer')
                        <link rel="stylesheet" href="https://openlayers.org/en/v4.6.5/css/ol.css" type="text/css">
                        <link rel="stylesheet" href="https://unpkg.com/ol-layerswitcher@3.8.3/dist/ol-layerswitcher.css"/>
                        <div class="input-group mb-3">
                            <div id="map" style="width: 100%;height: 500px"></div>
                            <input type="hidden" name="latitude" id="latitude" class="form-control">
                            <input type="hidden" name="longitude" id="longitude" class="form-control">
                            @push('scripts')
                                <script src="{{ asset('/js/ol.js') }}" type="text/javascript"></script>
                                <script src="https://unpkg.com/ol-layerswitcher@3.8.3"></script>
                                <script src="{{ asset('js/map-functions.js') }}"></script>
                                <script>
                                    $(document).ready(function () {
                                        pointGeomDrawer({ workspace:'{{ Config::get("constants.GEOSERVER_WORKSPACE") }}', geoserverUrl:'{{ Config::get("constants.GEOSERVER_URL") }}', authKey:'{{ Config::get("constants.AUTH_KEY") }}', mapID:'map', geom: '{{ $formField->inputValue }}' })
                                    });
                                </script>
                            @endpush
                        </div>
                    @endif
                @if($formField->inputType === 'poly_geom_drawer')
                        <link rel="stylesheet" href="https://openlayers.org/en/v4.6.5/css/ol.css" type="text/css">
                        <link rel="stylesheet" href="https://unpkg.com/ol-layerswitcher@3.8.3/dist/ol-layerswitcher.css"/>
                        <div class="input-group mb-3">
                            <div id="map" style="width: 100%;height: 500px"></div>
                            <input type="hidden" name="{{ $formField->inputId }}" id="{{ $formField->inputId }}" value="{{ $formField->inputValue }}" >
                            @push('scripts')
                                <script src="{{ asset('/js/ol.js') }}" type="text/javascript"></script>
                                <script src="https://unpkg.com/ol-layerswitcher@3.8.3"></script>
                                <script src="{{ asset('js/map-functions.js') }}"></script>
                                <script>
                                    $(document).ready(function () {
                                        polyGeomDrawer({ workspace:'{{ Config::get("constants.GEOSERVER_WORKSPACE") }}', geoserverUrl:'{{ Config::get("constants.GEOSERVER_URL") }}', authKey:'{{ Config::get("constants.AUTH_KEY") }}', mapID:'map', geom: '{{ $formField->inputValue }}' })
                                    });
                                </script>
                            @endpush
                        </div>
                    @endif
                @if($formField->inputType === 'geom_viewer')
                        <link rel="stylesheet" href="https://openlayers.org/en/v4.6.5/css/ol.css" type="text/css">
                        <link rel="stylesheet" href="https://unpkg.com/ol-layerswitcher@3.8.3/dist/ol-layerswitcher.css"/>
                        <div class="input-group mb-3">
                            <div id="map" style="width: 100%;height: 500px"></div>
                            @push('scripts')
                                <script src="{{ asset('/js/ol.js') }}" type="text/javascript"></script>
                                <script src="https://unpkg.com/ol-layerswitcher@3.8.3"></script>
                                <script src="{{ asset('js/map-functions.js') }}"></script>
                                <script>
                                    $(document).ready(function () {
                                        geomViewer({ workspace:'{{ Config::get("constants.GEOSERVER_WORKSPACE") }}', geoserverUrl:'{{ Config::get("constants.GEOSERVER_URL") }}', authKey:'{{ Config::get("constants.AUTH_KEY") }}', mapID:'map', geom: '{{ $formField->labelValue }}' })
                                    });
                                </script>
                            @endpush
                        </div>
                    @endif
            </div>
        </div></div>
    @endforeach
@endif
@if(Request::is('*/create') || Request::is('*/edit') || Request::is('*/create/*') || !empty($submitButtonText))
    <div class="card-footer">
        @if(Request::is('*/create') || Request::is('*/edit') || Request::is('*/create/*'))
         <a href="{{ $indexAction }}" class="btn btn-info">{{ __('Back to List') }}</a>
        @endif
        @if(!empty($submitButtonText))
        {!! Form::submit($submitButtonText, ['class' => 'btn btn-info']) !!}
        @endif
    </div>
@endif

{{-- ✅ ANF styles only --}}
<style>
.anf-toggle-label {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.82rem;
    cursor: pointer;
    margin-bottom: 0;
    transition: all 0.2s;
    user-select: none;
}
.anf-badge {
    font-size: 0.62rem;
    font-weight: 700;
    padding: 2px 6px;
    border-radius: 4px;
    background: #e0a96d;
    color: #fff;
    transition: background 0.2s;
}
#anf_checkbox:checked + .anf-toggle-label .anf-badge { background: #e05252; }
#owner-details-card.anf-disabled {
    opacity: 0.4;
    pointer-events: none;
}
</style>