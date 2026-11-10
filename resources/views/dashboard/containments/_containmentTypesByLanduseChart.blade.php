@include('layouts.dashboard.chart-card',[
    'card_title' => __("Containment Types Categorized by Land Use"),
    'export_chart_btn_id' => "exportcontainmentTypesByLanduseChart",
    'canvas_id' => "containmentTypesByLanduseChart"
])
@push('scripts')
<script>
var ctx = document.getElementById("containmentTypesByLanduseChart");
var myChart = new Chart(ctx, {
  type: 'bar',
  data: {
    labels: @json(array_values(array_map(function($x) { return is_string($x) ? trim($x, '"\'') : $x; }, (array)($containmentTypesByLanduseChart['labels'] ?? [])))),
    datasets: [
        @foreach($containmentTypesByLanduseChart['datasets'] as $dataset)
        {
            label: @json($dataset['label'] ?? null),
            backgroundColor: @json($dataset['color'] ?? null),
            data: @json(array_values($dataset['data'] ?? [])),
            values: @json($dataset['value'])
        },
        @endforeach
    ]
},
  options: {
    animation:{
      animateScale:true
    },
    responsive: true,
      legend: {
         display: true,
         position: 'bottom',
         align: 'start',
         labels: {
              boxWidth: 10
          }
      },
    scales: {
        xAxes: [{
            stacked: true,
            ticks: {
                beginAtZero: true
            },
            scaleLabel: {
                    display: true, // Enable the scale label
                    labelString: 'Land Use Type' // The label text
                },
        }],
        yAxes: [{
            stacked: true,
            ticks: {
                 beginAtZero: true,
                 userCallback: function(label, index, labels) {
                     // when the floored value is the same as the value we have a whole number
                     if (Math.floor(label) === label) {
                         return label;
                     }

                 },
            }
        }]
    },
    tooltips: {
        mode: 'index',
        callbacks: {
            label: function (tooltipItem, data) {
                var allData = data.datasets[tooltipItem.datasetIndex].data;
                var allValues = data.datasets[tooltipItem.datasetIndex].values;
                var tooltipLabel = data.datasets[tooltipItem.datasetIndex].label;
                var tooltipData = allData[tooltipItem.index];
                var tooltipValue = allValues[tooltipItem.index];
                return tooltipLabel + ": " + tooltipData.toFixed(2) + "% : "+tooltipValue;
            },
        }
    }
  }
});
document.getElementById('exportcontainmentTypesByLanduseChart').addEventListener("click", downloadIMG);
  //donwload pdf from original canvas
  function downloadIMG() {
    var newCanvas = document.querySelector('#containmentTypesByLanduseChart');

    //create image from dummy canvas
    var newCanvasImg = newCanvas.toDataURL("image/png", 1.0);
    var a = document.createElement('a');
    a.href =newCanvas.toDataURL("image/png", 1.0);

    a.download = 'Containment Types Categorized by Land Use.png';

    // Trigger the download
    a.click();
      }
</script>
@endpush
