@include('layouts.dashboard.chart-card',[
    'card_title' => __("Emptying Requests for the Next Four Weeks"),
    'export_chart_btn_id' => "exportproposedEmptyingDateContainmentsChart",
    'canvas_id' => "proposedEmptyingDateContainmentsChart"
])
@push('scripts')
<script>
const backgroundFill = {
    id: 'custom_canvas_background_color',
    beforeDraw: (chart, args, options) => {
        const {ctx} = chart;
        ctx.save();
        ctx.globalCompositeOperation = 'destination-over';
        ctx.fillStyle = options.color;
        ctx.fillRect(0, 0, chart.width, chart.height);
        ctx.restore();
    },
    defaults: {
        color: 'lightGreen'
    }
}

var ctx = document.getElementById("proposedEmptyingDateContainmentsChart");
var myChart = new Chart(ctx, {
  type: 'bar',
  data: {
    labels: @json(array_values(array_map(function($x) { return is_string($x) ? trim($x, '"\'') : $x; }, (array)($proposedEmptyingDateContainmentsChart['labels'] ?? [])))),
    plugins: [backgroundFill],
    datasets: [
        {
            label: "No. of requests",
            backgroundColor: "rgba(54, 162, 235,0.5)",
            hoverBackgroundColor: "rgba(54, 162, 235,0.7)",
            data: @json(array_values($proposedEmptyingDateContainmentsChart['values'] ?? [])),
        }
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
                   labelString: 'Weeks' // The label text
               },
       }],
        yAxes: [{
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
    }
  }
});

document.getElementById('exportproposedEmptyingDateContainmentsChart').addEventListener("click", downloadIMG);
  //donwload pdf from original canvas
  function downloadIMG() {
    var newCanvas = document.querySelector('#proposedEmptyingDateContainmentsChart');

    //create image from dummy canvas
    var newCanvasImg = newCanvas.toDataURL("image/jpeg", 1.0);
    var a = document.createElement('a');
    a.href =newCanvas.toDataURL("image/jpeg", 1.0);

    a.download = 'Emptying Requests for the Next Four Weeks.jpeg';

    // Trigger the download
    a.click();
      }
</script>
@endpush
