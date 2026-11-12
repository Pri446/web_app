@include('layouts.dashboard.chart-card',[


    'card_title' => __("Building Connections to Sanitation System Types"),
    'export_chart_btn_id' => "exportsanitationSystemsChart",
    'canvas_id' => "sanitationSystemsChart"
])
@push('scripts')
<style>
#sanitationSystemsChart {
    height: 600px !important;
}
</style>
<script>
var ctx = document.getElementById("sanitationSystemsChart");
var myChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: @json(array_values(array_map(function($x) { return is_string($x) ? trim($x, '"\'') : $x; }, (array)($sanitationSystemsChart['labels'] ?? [])))),
        datasets: [
            {
                label: "No. of buildings",
                backgroundColor: "rgba(54, 162, 235,0.5)",
                hoverBackgroundColor: "rgba(54, 162, 235,0.7)",
                data: @json(array_values($sanitationSystemsChart['values'] ?? [])),
            }
        ]
    },
    options: {
        animation: {
            animateScale: true
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
                    labelString: 'Sanitation System' // The label text
                }
            }],
            yAxes: [{
                ticks: {
                    beginAtZero: true,
                    userCallback: function(label, index, labels) {
                        // Only return whole numbers
                        if (Math.floor(label) === label) {
                            return label;
                        }
                    }
                }
            }]
        }
    }
});
document.getElementById('exportsanitationSystemsChart').addEventListener("click", downloadIMG);
// Download image as PNG
function downloadIMG() {
    var newCanvas = document.querySelector('#sanitationSystemsChart');
    var newCanvasImg = newCanvas.toDataURL("image/png", 1.0);
    var a = document.createElement('a');
    a.href = newCanvas.toDataURL("image/png", 1.0);
    a.download = 'Building Connections to Sanitation system Types.png';
    a.click();
}
</script>
@endpush
