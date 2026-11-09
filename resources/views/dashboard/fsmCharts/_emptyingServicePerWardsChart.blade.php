<!-- Last Modified Date: 18-04-2024
Developed By: Innovative Solution Pvt. Ltd. (ISPL)   -->
@include('layouts.dashboard.chart-card',[
    'card_title' => __("Summary of Applications, Emptying Services, Sludge Disposal, and Feedback by Ward"),
    'export_chart_btn_id' => "exportemptyingServicePerWardsChart",
    'canvas_id' => "emptyingServicePerWardsChart"
])
@push('scripts')
<script>
var ctx = document.getElementById("emptyingServicePerWardsChart");
var myChart = new Chart(ctx, {
  type: 'bar',
  data: {
    labels: @json(array_values(array_map(function($x) { return is_string($x) ? trim($x, '"\'') : $x; }, (array)($emptyingServicePerWardsChart['labels'] ?? [])))),
    datasets: [
        @foreach($emptyingServicePerWardsChart['datasets'] as $dataset)
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
        display: true,
        labelString: 'Wards',
        //fontSize: 10,
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
    }

  }
});
document.getElementById('exportemptyingServicePerWardsChart').addEventListener("click", downloadIMG);
  //donwload pdf from original canvas
  function downloadIMG() {
    var newCanvas = document.querySelector('#emptyingServicePerWardsChart');

    //create image from dummy canvas
    var newCanvasImg = newCanvas.toDataURL("image/png", 1.0);
    var a = document.createElement('a');
    a.href =newCanvas.toDataURL("image/png", 1.0);

    a.download = 'Summary of Applications, Emptying Services, Sludge Disposal, and Feedback by Ward.png';

    // Trigger the download
    a.click();
      }
</script>
@endpush
