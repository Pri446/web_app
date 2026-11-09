<!-- Last Modified Date: 18-04-2024
Developed By: Innovative Solution Pvt. Ltd. (ISPL)   -->
@include('layouts.dashboard.chart-card',[

    'card_title' => __("Wardwise Distribution of Containment Types"),
    'export_chart_btn_id' => "exportcontainmentTypesPerWardChart",
    'canvas_id' => "containmentTypesPerWardChart"
])
@push('scripts')
<script>
var ctx = document.getElementById("containmentTypesPerWardChart");
var myChart = new Chart(ctx, {
  type: 'bar',
  data: {
    labels: @json(array_values(array_map(function($x) { return is_string($x) ? trim($x, '"\'') : $x; }, (array)($containmentTypesPerWardChart['labels'] ?? [])))),
    datasets: [
        @foreach($containmentTypesPerWardChart['datasets'] as $dataset)
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
                scaleLabel: {
                display: true,
                labelString: 'Wards',
        //fontSize: 10,
      },
            stacked: true,
            ticks: {
                beginAtZero: true
            }
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

                 }
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
                //return tooltipLabel + ": " + tooltipData.toFixed(2) + "% : "+tooltipValue;
                return tooltipLabel + ": " +tooltipValue;
            },
        }
    },
    plugins: {
      datalabels: {
        color: 'white',
        font: {
          weight: 'bold'
        },
        formatter: function(value, context) {
          return Math.round(value) + '%';
        }
      }
    }
  }
});
document.getElementById('exportcontainmentTypesPerWardChart').addEventListener("click", downloadIMG);
  //donwload pdf from original canvas
  function downloadIMG() {
    var newCanvas = document.querySelector('#containmentTypesPerWardChart');

    //create image from dummy canvas
    var newCanvasImg = newCanvas.toDataURL("image/png", 1.0);
    var a = document.createElement('a');
    a.href =newCanvas.toDataURL("image/png", 1.0);

    a.download = 'Wardwise Distribution of Containment Types by Wards.png';

    // Trigger the download
    a.click();
      }
</script>
@endpush
