<!DOCTYPE html>
<html>

    <head>
        <style>
              @page {
      size: A4;
      margin: 0.5in;
    }

    body {
      padding: 0.5in;
    }
               .header {
    
      text-align: center; /* Add margin to the header for separation */
    }
   
            table {
                letter-spacing: 0.2px;
                border-collapse: collapse;
                width: 100%;
            }

            td,
            th {
                text-align: left;
                padding: 6px;
            }

            /* tr:nth-child(even) {
                background-color: #ddddde;
                border : 0.5px solid;
            } */
            .text-right {
                text-align: right !important;
            }
          
        </style>
        <title>{{ __('Application Report') }}</title>
    </head>

    <body>
    <div class="container">
            <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('img/logo-imis.png'))) }}" class="logo" style=" width: 120px;">
                <div class="header">
                    <h1 class="heading" style="text-transform:uppercase; margin: 0;">{{ __('Municipality') }}</h1>
                    <h2 style="text-transform:uppercase; margin: 10px; ">{{ __('Application Report') }}</h2>
                    <!-- <h3 style=" text-transform:uppercase; margin: 0;">Integrated Municipal Information System</h3> -->
                </div>
         
            <table class="table" width="100%" style="margin-top: 20px; border-collapse: collapse;">
                <tr>
                <td style="text-align:left;font-size: 18px; margin: 0; border: none;" >{{ __('Application ID') }}: {{$application->id}} </td>
                <td style="float:right;font-size: 18px; margin: 0; border: none;">{{ __('Application Date') }}: {{$application->application_date->format('Y-m-d')}}</td>
                </tr>
            </table>

        </div>
 

        <table style="width: 100%; letter-spacing: 0.2px">
        <thead><p style="text-align:left;font-size : 19px; font-weight: bold;background-color: #ddddde; padding: 2px"> {{ __('Building Details') }} </p></thead>
        <tbody>
        <tr>
            @if(empty($application->emptying))
            <th style="width: 20%;" rowspan="7">
            <div style="text-align: center;">
                <img src="{{'data:image/png;base64,'.base64_encode(file_get_contents(public_path('storage/emptyings/houses/$appplication->emptying->house_image')))}}" height="100px" />
            </div>
            </th>
            @else
            <th style="width: 20%;" rowspan="8">
                <div style="text-align: center;">
                    <img src="{{'data:image/png;base64,'.base64_encode(file_get_contents(public_path('img/report-icon/house-image.png')))}}" width="100px" style="display: block; margin: 0 auto;" />
                </div>
            </th>

            @endif
            <td style="width: 20%; font-size : 18px;">{{ __('BIN') }}</td>
            <td style="width: 20%; font-size : 18px;">{{$application->buildings->bin ?? '-'}}</td>
        </tr>
        <tr>
            <td style=" font-size : 18px;">{{ __('Structure Type') }}</td>
            <td  style=" font-size : 18px;">{{$application->buildings->StructureType->type?? '-'}}</td>
        </tr>
        <tr>
            <td style=" font-size : 18px;">{{ __('Ward') }}</td>
            <td style=" font-size : 18px;">{{$application->buildings->ward?? '-'}}</td>
        </tr>
        <tr>
            <td style=" font-size : 18px;">{{ __('Number of Floors') }}</td>
            <td style=" font-size : 18px;">{{$application->buildings->floor_count?? '-'}}</td>
        </tr>
        {{-- Option 2: operational reports use applicant fields only. Encrypted
             customer/owner PII is not implicitly decrypted for this PDF. --}}
        <tr>
            <td style=" font-size : 18px;">{{ __('Applicant Name') }}</td>
            <td style=" font-size : 18px;">{{$application->applicant_name?? '-'}}</td>
        </tr>
        <tr>
            <td style=" font-size : 18px;">{{ __('Applicant Contact') }}</td>
            <td style=" font-size : 18px;">{{$application->applicant_contact?? '-'}}</td>
        </tr>
     
    </tbody>
</table>

            @foreach($containment as $containments)
          
            <table style="width: 100%;">

                        <thead> <p style="text-align:left;font-size : 19px; font-weight: bold;background-color: #ddddde; padding: 2px"> {{ __('Containment Details') }} </p>
                </thead>
                <tbody>
                    <tr>
                    
                        <th style="width: 20%;" rowspan="7">
                        <div style="text-align: center;">
                            <img src="{{'data:image/png;base64,'.base64_encode(file_get_contents(public_path('img/report-icon/containment.png')))}}" width="100px" />
                        </div>
                        </th>
                     
                        <td style="width: 20%; font-size : 18px;">{{ __('Containment ID') }}</td>
                        <td style="width: 20%; font-size : 18px;">{{$containments->id?? '-'}}</td>
                    </tr>
                    <tr>
        
                        <td style=" font-size : 18px;">{{ __('Containment Type') }}</td>
                        <td style=" font-size : 18px;">{{$containments->type?? '-'}}</td>
                    </tr>
                    <tr>
                        <td style=" font-size : 18px;">{{ __('Containment Location') }}</td>
                        <td style=" font-size : 18px;">{{$containments->location?? '-'}}</td>
                    </tr>
                    
                </tbody>
            </table>
        @endforeach

        <table style="width: 100%;">
                        <thead>  <p style="text-align:left;font-size : 19px; font-weight: bold;background-color: #ddddde; padding: 2px">{{ __('Emptying Details') }}  </p>
                </thead>
                <tbody>
                    <tr>
                    
                        <th style="width: 20%;" rowspan="7">
                        <div style="text-align: center;">
                            <img src="{{'data:image/png;base64,'.base64_encode(file_get_contents(public_path('img/report-icon/emptying.png')))}}" width="100px" />
                        </div>
                        </th>
                    
                        <td style="width: 20%; font-size : 18px;">{{ __('Service Provider Name') }}</td>
                        <td style="width: 20%; font-size : 18px;">{{$application->emptying->service_provider()->withTrashed()->first()->company_name?? '-'}}</td>
                    </tr>
                    <tr>
                        <td style=" font-size : 18px;">{{ __('Total Sludge Volume (m³)') }} </td>
                        <td style=" font-size : 18px;">{{$application->emptying->volume_of_sludge?? '-'}}</td>
                    </tr>
                    <tr>
                        <td style=" font-size : 18px;">{{ __('Emptied Date') }}</td>
                        <td style=" font-size : 18px;">{{$application->emptying->emptied_date->format('Y-m-d')?? '-'}}</td>
                    </tr>
                    <tr>
                    <td style=" font-size : 18px;">{{ __('Emptied Time') }}</td>
                    <td style=" font-size : 18px;">{{$application->emptying->start_time?? '-'}}</td>
                </tr>
                {{--   
                <!-- <tr>
                    <th colspan="4">Vaccutug Name</th>
                    @if($data->vacutug->name)
                    <th colspan="4">{{$data->vacutug->name}}</th>
                    @else
                    <th colspan="4">NA </th>           
                    @endif     
                </tr> --> 
                --}}
                <tr>
                    <td style=" font-size : 18px;">{{ __('Name of Driver') }}</td>
                    <td style=" font-size : 18px;">{{$application->emptying->employee_info_driver->name?? '-'}}</td>
                </tr>
                
               {{-- 
                <!-- <tr>
                    <th>Name of Emptier</th>
                    <th>{{$application->emptying->employee_info_emptier->name}}</th>
                </tr> -->
                --}}
                <tr>
                    <td style=" font-size : 18px;">{{ __('Total Cost') }}</td>
                    <td style=" font-size : 18px;">{{number_format($application->emptying->total_cost)?? '-'}}</td>
                </tr>
                <tr>
                    <td style=" font-size : 18px;">{{ __('Disposal Treatment Plant') }}</td>
                    <td style=" font-size : 18px;">{{$application->emptying->treatmentPlant->name?? '-'}}</td>
                </tr>
                </tbody>
            </table>
    

       @if(!empty($application->sludge_collection))
       <table style="width: 100%;">

            <thead>
                <p style="text-align:left;font-size : 19px; font-weight: bold;background-color: #ddddde; padding: 2px">{{ __('Sludge Collection Details') }} </p>
            </thead>
            <tbody>
                <tr>
                <th style="width: 20%;" rowspan="7">
                <div style="text-align: center;">
                    <img src="{{'data:image/png;base64,'.base64_encode(file_get_contents(public_path('img/report-icon/sludge.png')))}}" width="100px"/> 
               </div></th>
                    <td style="width: 20%;  font-size : 18px;">{{ __('Disposal Place') }}</td>
                        <td style="width: 20%; font-size : 18px;">{{$application->sludge_collection->treatmentplants->name?? '-'}}</td>
                </tr>
                <tr>
                    <td style=" font-size : 18px;">{{ __('Disposal Date') }}</td>
                    <td style=" font-size : 18px;">{{$application->sludge_collection->date?? '-'}}</td>

                </tr>
                <tr>
                    <td style=" font-size : 18px;">{{ __('Disposal Time') }}</td>
                    <td style="font-size: 18px;">{{ (date('h:i A', strtotime($application->sludge_collection->entry_time)) ?? '-') . '-' . (date('h:i A', strtotime($application->sludge_collection->exit_time)) ?? '-') }}</td>
                </tr>
            </tbody>
        </table>  
        @endif

    </body>


</html>
