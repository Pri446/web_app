<div class="container">
    <div class="panell-group">
        
        <div class="card card-solid card-default panell panell-default" style="background-color: transparent !important;">
        @foreach ($subCategory_titles as $key=>$subCategory_title)
            <h3 class="panell-title" text-align="center">
                <centre><a>{{ $subCategory_title }}</a></centre>
            </h3>
            
            @for($i=0; $i<$param_listcount ; $i++)
                
                @foreach ($param_titles[$i] as $param_title)
                    <div class=" card-header with-borderpanell-heading my-5"> 
                        <h6 class="card-title panell-title">
                            <span data-toggle="collapse" href="#collapseparam{{$i}}" class="">{{ $param_title }}
                            </span>
                        </h6>
                    </div>
                    <div id="collapseparam{{$i}}" class="card-body panell-collapse collapse">
                        <table class="table table-bordered" width='100%'>
                        <tr>
                            <th colspan=4 width='50%' style="margin-left:auto; margin-right:auto;">Parameter: {{ $param_title }}</th>
                        </tr>
                        <tr class="" width='100%'>
                            <th width='50%'>Assessment Metric or Data Point	Unit</th>
                            <th width='15%'>Unit</th>
                            <th width='15%'>CWIS Outcome (CO) / Functions (CF)</th>
                            <th width='20%'>Data</th>
                        </tr>
                        @foreach ($param_details[$i] as $param_detail)
                        <tr class="" width='100%'>
                            <td width='50%'>{{ $param_detail->assmntmtrc_dtpnt }}</td>
                            <td width='15%'>{{ $param_detail->unit }}</td>
                            <td width='15%'>{{ $param_detail->co_cf }}</td>
                            <td width='20%'>{!! Form::text('data_value[]',$param_detail->data_value,['class' => 'form-control', 'placeholder' => 'Enter data here ..']) !!}</td>
                        </tr>
                        @endforeach
                        </table>
                    </div>
                @endforeach
                <br/>
            @endfor
        @endforeach
    </div>
    </div>

    <div class="footer">
        {!! Form::submit(__('Save'), ['class' => 'btn btn-info']) !!}
    </div><!-- /.card-footer -->
    
</div><!-- /.card-body -->





