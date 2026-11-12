@include('layouts.dashboard.chart-card',[
    'card_title' => __("Customer Satisfaction with FSM Service Quality"),
    'export_chart_btn_id' => "exportfsmSrvcQltyChart",

    'canvas_id' => "fsmSrvcQltyChart"
])

@push('scripts')
<script>
var ctx = document.getElementById("fsmSrvcQltyChart");
var myChart = new Chart(ctx, {
  type: 'doughnut',
  data: {
    labels: @json(array_values(array_map(function($x) { return is_string($x) ? trim($x, '"\'') : $x; }, (array)($fsmSrvcQltyChart['labels'] ?? [])))),
    datasets: [
        {
            label: "Building Structures by building use",
            backgroundColor: @json(array_values(array_map(function($x) { return is_string($x) ? trim($x, '"\'') : $x; }, (array)($fsmSrvcQltyChart['colors'] ?? [])))),
            hoverBackgroundColor: @json(array_values(array_map(function($x) { return is_string($x) ? trim($x, '"\'') : $x; }, (array)($fsmSrvcQltyChart['hoverBackgroundColor'] ?? [])))),
            data: @json(array_values($fsmSrvcQltyChart['values'] ?? [])),
        }
    ]
},
  options: {
    animation:{
      animateScale:true
    }
  }
});
document.getElementById('exportfsmSrvcQltyChart').addEventListener("click", downloadIMG);
document.getElementById('year');
  //donwload pdf from original canvas
  function downloadIMG() {
    var newCanvas = document.querySelector('#fsmSrvcQltyChart');

    //create image from dummy canvas
    var newCanvasImg = newCanvas.toDataURL("image/png", 1.0);
    var a = document.createElement('a');
    a.href =newCanvas.toDataURL("image/png", 1.0);

    a.download = ' Customer Satisfaction with FSM Service Quality.png';

    // Trigger the download
    a.click();
      }
</script>
@endpush
