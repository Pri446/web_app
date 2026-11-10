<!-- Last Modified Date: 16-04-2024
Developed By: Innovative Solution Pvt. Ltd. (ISPL)  (© ISPL, 2024) -->
@include('layouts.dashboard.chart-card',[
    'card_title' => __("Wardwise Total Road Length (m)"),
    'export_chart_btn_id' => "exportroadLengthPerWardChart",
    'canvas_id' => "roadLengthPerWardChart"
])
@push('scripts')
    <script>
        var ctx = document.getElementById("roadLengthPerWardChart");
        var myChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: @json(array_values(array_map(function($x) { return is_string($x) ? trim($x, '"\'') : $x; }, (array)($roadLengthPerWardChart['labels'] ?? [])))),
                datasets: [
                    {
                        label: "Road Length(m)",
                        backgroundColor: "rgba(54, 162, 235,0.5)",
                        hoverBackgroundColor: "rgba(54, 162, 235,0.7)",
                        data: @json(array_values($roadLengthPerWardChart['values'] ?? [])),
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
                            labelString: 'Wards'
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
        document.getElementById('exportroadLengthPerWardChart').addEventListener("click", downloadIMG);
        //donwload pdf from original canvas
        function downloadIMG() {
            var newCanvas = document.querySelector('#roadLengthPerWardChart');

            //create image from dummy canvas
            var newCanvasImg = newCanvas.toDataURL("image/png", 1.0);
            var a = document.createElement('a');
            a.href =newCanvas.toDataURL("image/png", 1.0);

            a.download = 'Ward-Wise Total Road Length (in Meters).png';

            // Trigger the download
            a.click();
        }
    </script>
@endpush
