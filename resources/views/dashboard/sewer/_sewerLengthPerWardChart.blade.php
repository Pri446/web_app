<!-- Last Modified Date: 16-04-2024
Developed By: Innovative Solution Pvt. Ltd. (ISPL)  (© ISPL, 2024) -->
@include('layouts.dashboard.chart-card',[
    'card_title' => __("Wardwise Sewer Network Length (m)"),
    'export_chart_btn_id' => "exportSewerLengthPerWardChart",
    'canvas_id' => "sewerLengthPerWardChart"
])
@push('scripts')
    <script>
        var ctx = document.getElementById("sewerLengthPerWardChart");
        var myChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: @json(array_values(array_map(function($x) { return is_string($x) ? trim($x, '"\'') : $x; }, (array)($sewerLengthPerWardChart['labels'] ?? [])))),
                datasets: [
                    {
                        label: "Sewer Length (m)",
                        backgroundColor: "rgba(54, 162, 235,0.5)",
                        hoverBackgroundColor: "rgba(54, 162, 235,0.7)",
                        data: @json(array_values($sewerLengthPerWardChart['values'] ?? [])),
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
        document.getElementById('exportSewerLengthPerWardChart').addEventListener("click", downloadIMG);
        //donwload pdf from original canvas
        function downloadIMG() {
            var newCanvas = document.querySelector('#sewerLengthPerWardChart');

            //create image from dummy canvas
            var newCanvasImg = newCanvas.toDataURL("image/png", 1.0);
            var a = document.createElement('a');
            a.href =newCanvas.toDataURL("image/png", 1.0);

            a.download = 'Ward-Wise Sewer Network Length (in Meters).png';

            // Trigger the download
            a.click();
        }
    </script>
@endpush
