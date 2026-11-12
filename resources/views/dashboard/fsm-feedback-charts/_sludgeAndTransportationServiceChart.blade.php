@include('layouts.dashboard.chart-card',[
    'card_title' => "Sludge Collection And Transportation Service Quality",
    'export_chart_btn_id' => "exportsludgeAndTransportationServiceChart",
    'canvas_id' => "sludgeAndTransportationServiceChart"
])
@push('scripts')
<script>
var ctx = document.getElementById("sludgeAndTransportationServiceChart");
var myChart = new Chart(ctx, {
  type: 'doughnut',
  data: {
    labels: @json(array_values(array_map(function($x) { return is_string($x) ? trim($x, '"\'') : $x; }, (array)($sludgeAndTransportationServiceChart['labels'] ?? [])))),
    datasets: [
        {
            label: "Building Structures by building use",
            backgroundColor: @json(array_values(array_map(function($x) { return is_string($x) ? trim($x, '"\'') : $x; }, (array)($sludgeAndTransportationServiceChart['colors'] ?? [])))),
            hoverBackgroundColor: @json(array_values(array_map(function($x) { return is_string($x) ? trim($x, '"\'') : $x; }, (array)($sludgeAndTransportationServiceChart['colors'] ?? [])))),
            borderWidth: 1,
            data: @json(array_values($sludgeAndTransportationServiceChart['values'] ?? [])),
        }
    ]
},
  options: {
    animation:{
      animateScale:true
    }
  }
});
document.getElementById('exportsludgeAndTransportationServiceChart').addEventListener("click", downloadIMG);
  //donwload pdf from original canvas
  function downloadIMG() {
    var newCanvas = document.querySelector('#sludgeAndTransportationServiceChart');

    //create image from dummy canvas
    var newCanvasImg = newCanvas.toDataURL("image/png", 1.0);
    var a = document.createElement('a');
    a.href =newCanvas.toDataURL("image/png", 1.0);

    a.download = 'Sludge Collection and Transportation Service Quality.png';

    // Trigger the download
    a.click();
      }
</script>
@endpush
