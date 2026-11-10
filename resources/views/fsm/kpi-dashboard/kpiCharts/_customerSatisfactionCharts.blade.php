@include('layouts.dashboard.chart-card',[
    'card_title' => __("Customer Satisfaction"),
    'export_chart_btn_id' => "exportcustomerSatisfaction",
    'canvas_id' => "customerSatisfactionCharts"
])
@push('scripts')
 <script>
var ctx = document.getElementById("customerSatisfactionCharts");
var myChart = new Chart(ctx, {
  type: 'bar',
  data: {
    labels: @json(array_values(array_map(function($x) { return is_string($x) ? trim($x, '"\'') : $x; }, $customerSatisfactionCharts['labels'] ?? []))),
    datasets: [
        {
          type: "bar",
            label: "Targets",
            backgroundColor: "rgba(251, 176, 64,0.8)",
            hoverBackgroundColor: "rgba(251, 176, 64,0.9)",
            data: @json(array_values($customerSatisfactionCharts['target_values'] ?? [])),
        },
        {
          type: "bar",
            label: "Achievements",
            backgroundColor: "rgba(153, 202, 60,0.8)",
            hoverBackgroundColor: "rgba(153, 202, 60,0.9)",
            fill: false,
            data: @json(array_values($customerSatisfactionCharts['achievement_values'] ?? [])),
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
                   display: true, 
                   labelString: 'Years' 
               },
        }],
        yAxes: [{
          scaleLabel: {
                   display: true, 
                   labelString: 'Percent' 
               },
            ticks: {
                beginAtZero: true
            }
        }]
    }
  }
});
document.getElementById('exportcustomerSatisfaction').addEventListener("click", downloadIMG);
  //donwload pdf from original canvas
  function downloadIMG() {
    var newCanvas = document.querySelector('#customerSatisfactionCharts');

    //create image from dummy canvas
    var newCanvasImg = newCanvas.toDataURL("image/png", 1.0);
    var a = document.createElement('a');
    a.href =newCanvas.toDataURL("image/png", 1.0);
   
    a.download = 'Customer Satisfaction.png';

    // Trigger the download
    a.click();
      }
</script> 
@endpush 