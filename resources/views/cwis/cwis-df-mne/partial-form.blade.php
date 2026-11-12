<!-- Last Modified Date: 19-04-2024
Developed By: Innovative Solution Pvt. Ltd. (ISPL)  (© ISPL, 2024) -->
<div class="panel-group m-3" id="accordion" role="tablist" aria-multiselectable="true">
        <div class="panel panel-default">
                    <div class="card">
                        <div class="card-header" id="heading">
                            <h5 class="mb-0">
                                <a class="btn btn-link" data-toggle="collapse" data-target="#collapseparam" aria-expanded="true" aria-controls="collapse">
                               {{__('Equity')}}
                                </a>
                            </h5>
                        </div>
                        <div id="collapseparam" class="collapse show" aria-labelledby="heading" data-parent="#accordion">
                            <div class="card-body">
                                <table class="table table-bordered" width='100%'>
                                    <tr class="" width='100%'>
                                        <th width='50%'>{{__('Indicators')}}</th>
                                        <th width='10%'>{{__('Outcome')}}</th>
                                        <th width='20%'>{{__('Value')}}</th>
                                    </tr>
                                    <tr>
                                        <td>{{__('Ratio of LIC access to total population access')}}</td>
                                        <td>{{__("Equity")}}</td>
                                        <td><input type="text" class="form-control data-input" placeholder="{{__('Enter value in percent.')}}" min="0" max="100" step="1" name="EQ-1" value="{{ $data['EQ-1'] ?? '' }}" {{ $data['EQ-1'] !== null ? 'disabled' : '' }}>
                                        <input type="hidden" name="EQ-1_hidden" value="{{ isset($data['EQ-1']) ? $data['EQ-1'] : '' }}"></td>
                                    </tr>
                                    @foreach(($equityIndicators ?? collect())->where('indicator_code', '!=', 'EQ-1') as $indicator)
                                    <tr>
                                        <td>{{ $indicator->indicator_code }} - {{ __($indicator->label) }}</td>
                                        <td>{{ __('Equity') }}</td>
                                        <td><input type="text" class="form-control" value="{{ $data[$indicator->indicator_code] ?? __('No data') }}" aria-label="{{ $indicator->indicator_code }}" readonly></td>
                                    </tr>
                                    @endforeach
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header" id="headingOne">
                            <h5 class="mb-0">
                                <a class="btn btn-link" data-toggle="collapse" data-target="#collapseparam1" aria-expanded="true" aria-controls="collapseOne">
                                  {{__('Safety')}}
                                </a>
                            </h5>
                        </div>
                        <div id="collapseparam1" class="collapse show" aria-labelledby="headingOne" data-parent="#accordion">
                            <div class="card-body">
                                <table class="table table-bordered" width='100%'>
                                    <tr width='100%'>
                                        <th width='50%'>{{__('Indicators')}}</th>
                                        <th width='10%'>{{__('Outcome')}}</th>
                                        <th width='20%'>{{__('Value')}}</th>
                                    </tr>
                                    <tr>
                                        <td>{{ __('Access to safely managed sanitation') }}</td>
                                        <td>{{ __('Safety') }}</td>
                                        <td><input type="text" class="form-control" value="{{ $data['SF-1'] ?? __('No data') }}" aria-label="SF-1" readonly></td>
                                    </tr>
                                    <tr>
                                        <td>{{__('Population with access to safe individual toilets')}}</td>
                                        <td>{{__('Safety')}}</td>
                                        <td><input type="text" class="form-control data-input" placeholder="{{__('Enter value in percent.')}}" min="0" max="100" step="1" name="SF-1a"  value="{{ $data['SF-1a'] ?? '' }}" {{ $data['SF-1a'] !== null ? 'disabled' : '' }}>
                                        <input type="hidden" name="SF-1a_hidden" value="{{ isset($data['SF-1a']) ? $data['SF-1a'] : '' }}"></td>
                                    </tr>
                                    <tr>
                                        <td>{{__('Percentage of on-site sanitation that have been desludged')}}</td> 
                                        <td>{{__('Safety')}}</td>
                                        <td><input type="text" class="form-control data-input" placeholder="{{__('Enter value in percent.')}}" min="0" max="100" step="1" name="SF-1b"  value="{{ $data['SF-1b'] ?? '' }}" {{ $data['SF-1b'] !== null ? 'disabled' : '' }}>
                                        <input type="hidden" name="SF-1b_hidden" value="{{ isset($data['SF-1b']) ? $data['SF-1b'] : '' }}">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>{{__('Percentage of collected FS disposed at a treatment plant or at designated disposal site')}}</td>
                                        <td>{{__('Safety')}}</td>
                                        <td><input type="text" class="form-control data-input" placeholder="{{__('Enter value in percent.')}}" min="0" max="100" step="1" name="SF-1c" value="{{ $data['SF-1c'] ?? '' }}" {{ $data['SF-1c'] !== null ? 'disabled' : '' }}>
                                        <input type="hidden" name="SF-1c_hidden" value="{{ isset($data['SF-1c']) ? $data['SF-1c'] : '' }}"></td>
                                    </tr>
                                    <tr>
                                        <td>{{__('FS treatment capacity as a percentage of total FS generated from NSS connections (excluding safely disposed in situ)')}}</td>
                                        <td>{{__('Safety')}}</td>
                                        <td><input type="text" class="form-control data-input" placeholder="{{__('Enter value in percent.')}}" min="0" max="100" step="1" name="SF-1d" value="{{ $data['SF-1d'] ?? '' }}" {{ $data['SF-1d'] !== null ? 'disabled' : '' }}>
                                        <input type="hidden" name="SF-1d_hidden" value="{{ isset($data['SF-1d']) ? $data['SF-1d'] : '' }}"></td>
                                    </tr>
                                    <tr>
                                        <td>{{__('FS treatment capacity as a percentage of total FS collected from NSS connections')}}</td>
                                        <td>{{__('Safety')}}</td>
                                        <td><input type="text" class="form-control data-input" placeholder="{{__('Enter value in percent.')}}" min="0" max="100" step="1" name="SF-1e" value="{{ $data['SF-1e'] ?? '' }}" {{ $data['SF-1e'] !== null ? 'disabled' : ''  }}>
                                        <input type="hidden" name="SF-1e_hidden" value="{{ isset($data['SF-1e']) ? $data['SF-1e'] : '' }}"></td>
                                    </tr>
                                    <tr>
                                        <td>{{__('Wastewater treatment capacity as a percentage of total wastewater generated from sewered connections and greywater generated from non-sewered connections')}}</td>
                                        <td>{{__('Safety')}}</td>
                                        <td><input type="text" class="form-control data-input" placeholder="{{__('Enter value in percent.')}}" min="0" max="100" step="1" name="SF-1f" value="{{ $data['SF-1f'] ?? '' }}" {{ $data['SF-1f'] !== null ? 'disabled' : ''  }}>
                                        <input type="hidden" name="SF-1f_hidden" value="{{ isset($data['SF-1f']) ? $data['SF-1f'] : '' }}"></td>
                                    </tr>
                                    <tr>
                                        <td>{{__('Effectiveness of FS/WW treatment in meeting prescribed standards for effluent discharge and biosolids disposal')}}</td>
                                        <td>{{__('Safety')}}</td>
                                        <td><input type="text" class="form-control data-input" placeholder="{{__('Enter value in percent.')}}" min="0" max="100" step="1" name="SF-1g" value="{{ $data['SF-1g'] ?? ''}}" {{ $data['SF-1g'] !== null ? 'disabled' : ''  }}></td>
                                        <input type="hidden" name="SF-1g_hidden" value="{{ isset($data['SF-1g']) ? $data['SF-1g'] : '' }}">
                                    </tr>
                                    <tr>
                                        <td>{{__('Percentage LIC population with access to safe individual toilets')}}</td>
                                        <td>{{__('Safety')}}</td>
                                        <td><input type="text" class="form-control data-input" placeholder="{{__('Enter value in percent.')}}" min="0" max="100" step="1" name="SF-2a" value="{{ $data['SF-2a'] ?? '' }}" {{ $data['SF-2a'] !== null ? 'disabled' : ''  }}></td>
                                        <input type="hidden" name="SF-2a_hidden" value="{{ isset($data['SF-2a']) ? $data['SF-2a'] : '' }}">
                                    </tr>
                                    <tr>
                                        <td>{{__('Percentage of LIC, NSS, IHHLs that have been desludged')}}</td>
                                        <td>{{__('Safety')}}</td>
                                        <td><input type="text" class="form-control data-input" placeholder="{{__('Enter value in percent.')}}" min="0" max="100" step="1" name="SF-2b" value="{{ $data['SF-2b'] ?? '' }}" {{ $data['SF-2b'] !== null ? 'disabled' : ''  }}></td>
                                        <input type="hidden" name="SF-2b_hidden" value="{{ isset($data['SF-2b'])  ? $data['SF-2b'] : ''}}">
                                    </tr>
                                    <tr>
                                        <td>{{__('Percentage of collected FS (collected from LIC) disposed at treatment plant or designated disposal sites')}}</td>
                                        <td>{{__('Safety')}}</td>
                                        <td><input type="text" class="form-control data-input" placeholder="{{__('Enter value in percent.')}}" min="0" max="100" step="1" name="SF-2c" value="{{ $data['SF-2c'] ?? '' }}" {{ $data['SF-2c'] !== null ? 'disabled' : ''  }}></td>
                                        <input type="hidden" name="SF-2c_hidden" value="{{ isset($data['SF-2c']) ? $data['SF-2c'] : '' }}">
                                    </tr>
                                    <tr>
                                        <td>{{__('Percentage of dependent population (those without access to a private toilet/latrine) with access to safe shared facilities (CT/PT)')}}</td>
                                        <td>{{__('Safety')}}</td>
                                        <td><input type="text" class="form-control data-input" placeholder="{{__('Enter value in percent.')}}" min="0" max="100" step="1" name="SF-3b" value="{{ $data['SF-3b'] ?? '' }}" {{ $data['SF-3b'] !== null ? 'disabled' : ''  }}>
                                        <input type="hidden" name="SF-3b_hidden" value="{{ isset($data['SF-3b']) ? $data['SF-3b'] : '' }}"></td>
                                    </tr>
                                    <tr>
                                        <td>{{__('Percentage of CTs that adhere to principles of universal design')}}</td>
                                        <td>{{__('Safety')}}</td>
                                        <td><input type="text" class="form-control data-input" placeholder="{{__('Enter value in percent.')}}" min="0" max="100" step="1" name="SF-3" value="{{ $data['SF-3'] ?? '' }}" {{ $data['SF-3'] !== null ? 'disabled' : ''  }}>
                                        <input type="hidden" name="SF-3_hidden" value="{{ isset($data['SF-3']) ? $data['SF-3'] : '' }}"></td>
                                    </tr>
                                    <tr>
                                        <td>{{__('Percentage of users of CTs that are women')}}</td>
                                        <td>{{__('Safety')}}</td>
                                        <td><input type="text" class="form-control data-input" placeholder="{{__('Enter value in percent.')}}" min="0" max="100" step="1" name="SF-3c" value="{{ $data['SF-3c'] ?? '' }}" {{ $data['SF-3c'] !== null ? 'disabled' : ''  }}>
                                        <input type="hidden" name="SF-3c_hidden" value="{{ isset($data['SF-3c']) ? $data['SF-3c'] : '' }}"></td>
                                    </tr>
                                    <tr>
                                        <td>{{__('Average distance from the house to the closest CT (in meters)')}}</td>
                                        <td>{{__('Safety')}}</td>
                                        <td><input type="text" class="form-control data-input" placeholder="{{__('Enter value in percent.')}}" min="0" max="100" step="1" name="SF-3e" value="{{ $data['SF-3e'] ?? '' }}" {{ $data['SF-3e'] !== null ? 'disabled' : ''  }}>
                                        <input type="hidden" name="SF-3e_hidden" value="{{ isset($data['SF-3e']) ? $data['SF-3e'] : '' }}"></td>
                                    </tr>
                                    <tr>
                                        <td>{{__('Percentage of PTs where FS and WW generated is safely transported to TP or safely disposed in situ')}}</td>
                                        <td>{{__('Safety')}}</td>
                                        <td><input type="text" class="form-control data-input" placeholder="{{__('Enter value in percent.')}}" min="0" max="100" step="1" name="SF-4a" value="{{ $data['SF-4a'] ?? '' }}" {{ $data['SF-4a'] !== null ? 'disabled' : ''  }}>
                                        <input type="hidden" name="SF-4a_hidden" value="{{ isset($data['SF-4a']) ? $data['SF-4a'] : '' }}"></td>
                                    </tr>
                                    <tr>
                                        <td>{{__('Percentage of PTs that adhere to principles of universal design')}}</td>
                                        <td>{{__('Safety')}}</td>
                                        <td><input type="text" class="form-control data-input" placeholder="{{__('Enter value in percent.')}}" min="0" max="100" step="1" name="SF-4b" value="{{ $data['SF-4b'] ?? '' }}" {{ $data['SF-4b'] !== null ? 'disabled' : ''  }}>
                                        <input type="hidden" name="SF-4b_hidden" value="{{ isset($data['SF-4b']) ? $data['SF-4b'] : '' }}"></td>
                                    </tr>
                                    <tr>
                                        <td>{{__('Percentage of users of PTs that are women')}}</td>
                                        <td>{{__('Safety')}}</td>
                                        <td><input type="text" class="form-control data-input" placeholder="{{__('Enter value in percent.')}}" min="0" max="100" step="1" name="SF-4d" value="{{ $data['SF-4d'] ?? '' }}" {{ $data['SF-4d'] !== null ? 'disabled' : ''  }}>
                                        <input type="hidden" name="SF-4d_hidden" value="{{ isset($data['SF-4d']) ? $data['SF-4d'] : '' }}"></td>
                                    </tr>
                                    <tr>
                                        <td>{{__('Percentage of educational institutions where FS/WW generated is safely transported to TP or safely disposed in situ')}}</td>
                                        <td>{{__('Safety')}}</td>
                                        <td><input type="text" class="form-control data-input" placeholder="{{__('Enter value in percent.')}}" min="0" max="100" step="1" name="SF-5" value="{{ $data['SF-5'] ?? '' }}" {{ $data['SF-5'] !== null ? 'disabled' : ''  }}>
                                        <input type="hidden" name="SF-5_hidden" value="{{ isset($data['SF-5']) ? $data['SF-5'] : '' }}"></td>
                                    </tr>
                                    <tr>
                                        <td>{{__('Percentage of healthcare facilities where FS/WW generated is safely transported to TP or safely disposed in situ')}}</td>
                                        <td>{{__('Safety')}}</td>
                                        <td><input type="text" class="form-control data-input" placeholder="{{__('Enter value in percent.')}}" min="0" max="100" step="1" name="SF-6" value="{{ $data['SF-6'] ?? '' }}" {{ $data['SF-6'] !== null ? 'disabled' : ''  }}>
                                        <input type="hidden" name="SF-6_hidden" value="{{ isset($data['SF-6']) ? $data['SF-6'] : '' }}"></td>
                                    </tr>
                                    <tr>
                                        <td>{{__('Percentage of desludging services completed mechanically or semi-mechanically (by a gulper)')}}</td>
                                        <td>{{__('Safety')}}</td>
                                        <td><input type="text" class="form-control data-input" placeholder="{{__('Enter value in percent.')}}" min="0" max="100" step="1" name="SF-7" value="{{ $data['SF-7'] ?? '' }}" {{ $data['SF-7'] !== null ? 'disabled' : ''  }}>
                                        <input type="hidden" name="SF-7_hidden" value="{{ isset($data['SF-7']) ? $data['SF-7'] : '' }}"></td>
                                    </tr>
                                    <tr>
                                        <td>{{__('Percentage of tests which are in compliance with water quality standards for fecal coliform')}}</td>
                                        <td>{{__('Safety')}}</td>
                                        <td><input type="text" class="form-control data-input" placeholder="{{__('Enter value in percent.')}}" min="0" max="100" step="1" name="SF-9" value="{{ $data['SF-9'] ?? '' }}" {{ $data['SF-7'] !== null ? 'disabled' : ''  }}>
                                        <input type="hidden" name="SF-7_hidden" value="{{ isset($data['SF-9']) ? $data['SF-9'] : '' }}"></td>
                                    </tr>
                                   
                                </table>
                            </div>
                        </div>
                    </div>

                      
             
        </div>

    <!-- Static Button Logic -->
    <!-- <div class="footer">
        {!! Form::hidden('year', $year) !!}
        {!! Form::submit(__('Save'), ['class' => 'btn btn-info']) !!}
    </div> -->
</div>
