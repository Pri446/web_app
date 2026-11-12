@include('layouts.dashboard.chart-card', [
    'card_title' => __("Performance of Municipal Treatment Plants for Last Five Years"),
    'export_chart_btn_id' => "exportTreatmentPlantChart",
    'canvas_id' => "treatmentPlantChart"
])

@push('scripts')
<script>
(function () {
    // Assuming the data comes from the $treatmentPlantTest variable
    var sqlResult = @json($treatmentPlantTest);

    // Extract unique treatment plant names and years from the data
    var treatmentPlantNames = [...new Set(sqlResult.map(item => item.treatment_plant_name))];
    var years = [...new Set(sqlResult.map(item => item.year))];

    // Define a fixed set of colors for each treatment plant
    var fixedColors = ["#ffb964", "#023047", "#219EBC", "#8ECAE6", "#9D0208", "#FFD166", "#06D6A0", "#FF6B6B"];
    var belowStandardColor = "#CCCCCC"; // Grey color for below standard

    // The legacy SQL names are inverted: "belowstandard" contains compliant
    // tests, while "standardmeet" contains the remaining non-compliant tests.
    var datasets = [];
    treatmentPlantNames.forEach((plantName, index) => {
    var compliantData = years.map(year => {
        const dataItem = sqlResult.find(item => item.treatment_plant_name === plantName && item.year === year);
        return dataItem ? Number(dataItem.belowstandard) : 0;
    });

    var nonCompliantData = years.map(year => {
        const dataItem = sqlResult.find(item => item.treatment_plant_name === plantName && item.year === year);
        return dataItem ? Number(dataItem.standardmeet) : 0;
    });

    datasets.push({
        label: plantName,
        backgroundColor: fixedColors[index % fixedColors.length],
        data: compliantData,
        stack: plantName,
    });

    if (nonCompliantData.some(value => value > 0)) {
        datasets.push({
            label: plantName + ' - Non Compliance',
            backgroundColor: belowStandardColor,
            borderColor: fixedColors[index % fixedColors.length],
            borderWidth: 2,
            data: nonCompliantData,
            stack: plantName,
        });
    }
});


    // Combine all datasets for chart data
    var chartData = {
        labels: years, // X-axis labels (the years)
        datasets: datasets, // Stacked datasets for all treatment plants
    };

    // Chart options
    var options = {
        scales: {
            xAxes: [{
                scaleLabel: {
                    display: true,
                    labelString: 'Year'
                }
            }],
            yAxes: [{
                ticks: {
                    beginAtZero: true,
                    precision: 0
                }
            }]
        },
        responsive: true,
        legend: {
            display: true,
            position: 'bottom',
            align: 'start',
            labels: {
                boxWidth: 10,
            },
        },
    };

    // Initialize the chart
    var ctx = document.getElementById('treatmentPlantChart').getContext('2d');
    var myChart = new Chart(ctx, {
        type: 'bar',
        data: chartData,
        options: options,
    });
}());

document.getElementById('exportTreatmentPlantChart').addEventListener("click", downloadIMG);

// Function to download the chart as an image
function downloadIMG() {
    var newCanvas = document.querySelector('#treatmentPlantChart');
    var newCanvasImg = newCanvas.toDataURL("image/png", 1.0);
    var a = document.createElement('a');
    a.href = newCanvasImg;
    a.download = 'Performance of Municipal Treatment Plants for Last Five Years.png';
    a.click();
}


</script>
@endpush
