@if(isset($sf1) && $sf1->isNotEmpty())
<div class="sf1">
    @if(is_numeric($sf1[0]->data_value))
    <div class="chart" id="{{ $chartId }}Container">
        <figure class="chart__figure">
            <canvas class="chart__canvas" id="{{ $chartId }}Canvas" width="160" height="140" aria-label="{{ __('Access to safely managed sanitation') }}" role="img"></canvas>
        </figure>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            createDoughnutChart(@json((float) $sf1[0]->data_value), '#29ab87', @json($chartId . 'Canvas'), @json($chartId . 'Container'));
        });
    </script>
    @else
        <div class="chart"><p>{{ __('No data') }}</p></div>
    @endif
    <p>{{ __('Access to safely managed sanitation') }}</p>
</div>
@endif
