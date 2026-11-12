@include('layouts.dashboard.chart-card', [
    'card_title' => __("Distribution of SWM Service by Ward"),
    'export_chart_btn_id' => "exportSwmPresenceWard",
    'canvas_id' => "swmPresenceWardChart"
])

@push('scripts')
<script>
(function () {
    // Data from controller
    var sqlResult = @json($swmPresenceward);

    // Extract ward names and counts
    var wardLabels = sqlResult.map(item => item.ward);
    var buildingsWithSWM = sqlResult.map(item => item.buildings_with_swm_customer_id);
    var buildingsWithoutSWM = sqlResult.map(item => item.buildings_without_swm_customer_id);

    // Chart data setup
    var chartData = {
        labels: wardLabels,
        datasets: [
            {
                label: "Yes",
                backgroundColor: "#89CFF0",
                data: buildingsWithSWM
            },
            {
                label: "No",
                backgroundColor: " #808080",
                data: buildingsWithoutSWM
            }
        ]
    };

    var options = {
        scales: {
            x: { stacked: true },
            y: { stacked: true },
        },
        responsive: true,
        plugins: {
            legend: {
                display: true,
                position: 'bottom',
                labels: { boxWidth: 10 }
            }
        },

   scales: {
     xAxes: [{
               scaleLabel: {
                   display: true, // Enable the scale label
                   labelString: 'Wards' // The label text
               }
           }],
       }
    };

    var ctx = document.getElementById('swmPresenceWardChart').getContext('2d');
    var myChart = new Chart(ctx, {
        type: 'bar',
        data: chartData,
        options: options,
    });

    // Download as PNG
    document.getElementById('exportSwmPresenceWard').addEventListener("click", function() {
        var newCanvas = document.getElementById('swmPresenceWardChart');
        var newCanvasImg = newCanvas.toDataURL("image/png", 1.0);
        var a = document.createElement('a');
        a.href = newCanvasImg;
        a.download = 'Distribution of SWM Service by Ward.png';
        a.click();
    });
}());
</script>
@endpush
