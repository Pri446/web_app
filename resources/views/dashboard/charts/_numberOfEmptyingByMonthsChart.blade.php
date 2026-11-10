@include('layouts.dashboard.chart-card',[
    'card_title' => __("Comparison of Emptying Requests from Low Income and Other Communities"),
    'export_chart_btn_id' => "exportnumberOfEmptyingbyMonthsChart",
    'canvas_id' => "numberOfEmptyingbyMonthsChart"
])
@push('scripts')
<script>
var ctx = document.getElementById("numberOfEmptyingbyMonthsChart");
var myChart = new Chart(ctx, {
  type: 'bar',
  data: {
    labels: @json(array_values(array_map(function($x) { return is_string($x) ? trim($x, '"\'') : $x; }, (array)($numberOfEmptyingbyMonthsChart['labels'] ?? [])))),
    datasets: [
        @foreach($numberOfEmptyingbyMonthsChart['datasets'] as $dataset)
        {
            stack: @json($dataset['stack'] ?? null),
            label: @json($dataset['label'] ?? null),
            backgroundColor: @json($dataset['color'] ?? null),
            data: @json(array_values($dataset['data'] ?? [])),
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
         labels: {
              boxWidth: 10
          }
      },
    scales: {
      xAxes: [{

           scaleLabel: {
                   display: true, // Enable the scale label
                   labelString: 'Month' // The label text
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
        // callbacks: {
        //     label: function (tooltipItem, data) {
        //         var allData = data.datasets[tooltipItem.datasetIndex].data;
        //         var allValues = data.datasets[tooltipItem.datasetIndex].values;
        //         var tooltipLabel = data.datasets[tooltipItem.datasetIndex].label;
        //         var tooltipData = allData[tooltipItem.index];
        //         var tooltipValue = allValues[tooltipItem.index];
        //         return tooltipLabel + ": " + tooltipData + "% : "+tooltipValue;
        //     },
        // }
    }
  }
});
document.getElementById('exportnumberOfEmptyingbyMonthsChart').addEventListener("click", downloadIMG);
  //donwload pdf from original canvas
  function downloadIMG() {
    var newCanvas = document.querySelector('#numberOfEmptyingbyMonthsChart');

    //create image from dummy canvas
    var newCanvasImg = newCanvas.toDataURL("image/png", 1.0);
    var a = document.createElement('a');
    a.href =newCanvas.toDataURL("image/png", 1.0);

    a.download = ' Comparison of Emptying Requests from Low Income and Other Communities.png';

    // Trigger the download
    a.click();
      }
</script>
@endpush
