@include('layouts.dashboard.chart-card',[
    'card_title' => "Hotspots by Wards",
    'export_chart_btn_id' => "exporthotspotsPerWardChart",
    'canvas_id' => "hotspotsPerWardChart"
])
@push('scripts')
    <script>
        var ctx = document.getElementById("hotspotsPerWardChart");
        var myChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: @json(array_values(array_map(function($x) { return is_string($x) ? trim($x, '"\'') : $x; }, (array)($hotspotsPerWardChart['labels'] ?? [])))),
                datasets: [
                    {
                        label: "No. of Hotspots",
                        backgroundColor: "rgba(54, 162, 235,0.5)",
                        hoverBackgroundColor: "rgba(54, 162, 235,0.7)",
                        data: @json(array_values($hotspotsPerWardChart['values'] ?? [])),
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
        document.getElementById('exporthotspotsPerWardChart').addEventListener("click", downloadIMG);
        //donwload pdf from original canvas
        function downloadIMG() {
            var newCanvas = document.querySelector('#hotspotsPerWardChart');

            //create image from dummy canvas
            var newCanvasImg = newCanvas.toDataURL("image/png", 1.0);
            var a = document.createElement('a');
            a.href =newCanvas.toDataURL("image/png", 1.0);

            a.download = 'Hotspots by Wards.png';

            // Trigger the download
            a.click();
        }
    </script>
@endpush
