@php($chart_height = $chart_height ?? (!empty($fsm_chart_cards) ? 400 : null))
<div class="card card-outline card-info dashboard-chart-card {{ $chart_card_class ?? '' }}">
    <div class="card-header">
        <h3 class="card-title">{{ $card_title }}</h3>
        <div class="card-tools">
            <!-- Buttons, labels, and many other things can be placed here! -->
            
            {{-- <select id="{{ $year_id }}">
                            <option value="">All Years</option>
                            <option value="2019">2019</option>
                            <option value="2020">2020</option>
                            <option value="2021">2021</option>
                            <option value="2022">2022</option>
                            <option value="2023">2023</option>
                          </select>
                 --}}
            <!-- This will cause the card to collapse when clicked -->
            <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
            <!-- This will cause the card to maximize when clicked -->
            <button type="button" class="btn btn-tool" data-card-widget="maximize"><i class="fas fa-expand"></i></button>
            <!-- This will download the chart as an image -->
            <button id="{{ $export_chart_btn_id }}" type="button" class="btn btn-box-tool"><i class="fa-solid fa-image"> </i></button>
        </div>
        <!-- /.card-tools -->
    </div>
    <!-- /.card-header -->
    <div class="card-body collapse show">
        @isset($chart_height)
            <div style="position: relative; height: {{ $chart_height }}px;">
                <canvas id="{{ $canvas_id }}"></canvas>
            </div>
        @else
            <canvas id="{{ $canvas_id }}" style="height:250px"></canvas>
        @endisset
    </div>
    <!-- /.card-body -->
</div>
<!-- /.card -->
