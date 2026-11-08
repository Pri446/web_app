@include('layouts.dashboard.chart-card',[
    'card_title' => "Building Floor Counts",
    'export_chart_btn_id' => "exportbuildingFloorCountPerWard",
    'canvas_id' => "buildingFloorCountPerWard"
]);
<style>
  #buildingFloorCountPerWard{
    max-height: 650px;
  }
  </style>
@push('scripts')
<script>

var ctx = document.getElementById("buildingFloorCountPerWard");
        var chartData = <?php echo json_encode($buildingFloorCountPerWard); ?>;
        var myChart = new Chart(ctx, {
      type: 'bar',
      data: {
            labels: @json(array_values(array_map(function($x) { return is_string($x) ? trim($x, '"\'') : $x; }, (array)($buildingFloorCountPerWard['labels'] ?? [])))),
            datasets: [
                @foreach($buildingFloorCountPerWard['datasets'] as $dataset)
                {
                    label: '{{ $dataset['label'] }}',
                    backgroundColor: '{{ $dataset['backgroundColor'] }}',
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
                            display: true,
                            labelString: 'Wards'
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

document.getElementById('exportbuildingFloorCountPerWard').addEventListener("click", downloadIMG);
  //donwload pdf from original canvas
  function downloadIMG() {
    var newCanvas = document.querySelector('#buildingFloorCountPerWard');

    //create image from dummy canvas
    var newCanvasImg = newCanvas.toDataURL("image/png", 1.0);
    var a = document.createElement('a');
    a.href =newCanvas.toDataURL("image/png", 1.0);

    a.download = 'Building Structures Floor Counts .png';

    // Trigger the download
    a.click();
      }
</script>
@endpush
