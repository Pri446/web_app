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
                            <div class="clearfix float-right">
                                <div class="icheck-primary d-inline">
                                    <input type="checkbox" name="autofill" id="autofill" onclick="autoFillDetails()">
                                    <label for="autofill">
                                    {{ __('Same as Owner') }} 
                                    </label>
                                </div>
                            </div>
                        @endif
                    @endif
                </div>
                <div class="card-body">
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
                                {!! Form::label($field->inputId,$field->labelValue,['class' => $field->inputClass,'disabled' => $field->disabled]) !!}
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
                                                        geoserverUrl:'{{ url('/gis/') }}',

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
                                                            geoserverUrl:'{{ url('/gis/') }}',
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
                @endif
                @if($formField->inputType === 'textarea')
                    {!! Form::textarea($formField->inputId, $formField->inputValue, ['class' => 'form-control', 'placeholder' => $formField->placeholder, 'rows' => 4, 'cols' => 54, 'style' => 'resize:none']) !!}
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
                    {!! Form::label($formField->inputId,$formField->labelValue,['class' => $formField->inputClass,'disabled' => $formField->disabled]) !!}
                @endif
                @if($formField->inputType === 'radio')
                    {!! Form::radio($formField->inputId,$formField->labelValue,['class' => $formField->inputClass,'disabled' => $formField->disabled]) !!}
                @endif
                @if($formField->inputType === 'multiple-select')
                    {!! Form::select($formField->inputId,$formField->selectValues,$formField->selectedValue,['class' => $formField->inputClass,'disabled' => $formField->disabled]) !!}
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
                {!! Form::date($formField->inputId, $formField->inputValue, ['onclick' => 'this.showPicker()', 'class' => $formField->inputClass]) !!}
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
                        
                        @if( $formField->inputId === 'house_image')
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" name="{{ $formField->inputId }}" id="{{ $formField->inputId }}">
                            <label class="custom-file-label" for="{{ $formField->labelFor }}">{{ $formField->inputValue??"Choose file" }}</label>
                        </div>
                        <small class="form-text" id="fileSizeHintImg">(Image (JPG,JPEG) size should not be more than 5MB)</small>
                        @elseif ( $formField->inputId === 'receipt_image')
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" name="{{ $formField->inputId }}" id="{{ $formField->inputId }}">
                            <label class="custom-file-label" for="{{ $formField->labelFor }}">{{ $formField->inputValue??"Choose file" }}</label>
                        </div>
                        <small class="form-text" id="fileSizeRintImg">(Image (JPG,JPEG) size should not be more than 5MB)</small>
                        @else
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" name="{{ $formField->inputId }}" id="{{ $formField->inputId }}">
                            <label class="custom-file-label" for="{{ $formField->labelFor }}">{{ $formField->inputValue??"Choose file" }}</label>
                        </div>
                        @endif

                @endif
                @if($formField->inputType === 'point_geom_drawer')
                        <link rel="stylesheet" href="https://openlayers.org/en/v4.6.5/css/ol.css" type="text/css">
                        <link rel="stylesheet" href="https://unpkg.com/ol-layerswitcher@3.8.3/dist/ol-layerswitcher.css"/>
                        <div class="input-group mb-3">
                            <div id="map" style="width: 100%;height: 500px">
                            </div>
                            <input type="hidden" name="latitude" id="latitude" class="form-control">
                            <input type="hidden" name="longitude" id="longitude" class="form-control">
                            @push('scripts')
                                <script src="{{ asset('/js/ol.js') }}" type="text/javascript"></script>
                                <script src="https://unpkg.com/ol-layerswitcher@3.8.3"></script>
                                <script src="{{ asset('js/map-functions.js') }}"></script>
                                <script>
                                    $(document).ready(function () {
                                        pointGeomDrawer(
                                            {
                                                workspace:'{{ Config::get("constants.GEOSERVER_WORKSPACE") }}',
                                                geoserverUrl:'{{ url('/gis/') }}',
                                                mapID:'map',
                                                geom: '{{ $formField->inputValue }}'
                                            }
                                        )
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
                                        polyGeomDrawer(
                                            {
                                                workspace:'{{ Config::get("constants.GEOSERVER_WORKSPACE") }}',
                                                geoserverUrl:'{{ url('/gis/') }}',
                                                mapID:'map',
                                                geom: '{{ $formField->inputValue }}'
                                            }
                                        )
                                    });
                                </script>
                            @endpush
                        </div>
                    @endif
                @if($formField->inputType === 'geom_viewer')
                        <link rel="stylesheet" href="https://openlayers.org/en/v4.6.5/css/ol.css" type="text/css">
                        <link rel="stylesheet" href="https://unpkg.com/ol-layerswitcher@3.8.3/dist/ol-layerswitcher.css"/>
                        <div class="input-group mb-3">
                            <div id="map" style="width: 100%;height: 500px">
                            </div>
                            @push('scripts')
                                <script src="{{ asset('/js/ol.js') }}" type="text/javascript"></script>
                                <script src="https://unpkg.com/ol-layerswitcher@3.8.3"></script>
                                <script src="{{ asset('js/map-functions.js') }}"></script>
                                <script>
                                    $(document).ready(function () {
                                        geomViewer(
                                            {
                                                workspace:'{{ Config::get("constants.GEOSERVER_WORKSPACE") }}',
                                                geoserverUrl:'{{ url('/gis/') }}',
                                                mapID:'map',
                                                geom: '{{ $formField->labelValue }}'
                                            }
                                        )
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


