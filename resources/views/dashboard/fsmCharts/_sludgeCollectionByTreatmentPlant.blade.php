@include('layouts.dashboard.chart-card',[
    'card_title' => __("Sludge Collection Trends by Treatment Plants Over the Last 5 Years"),
    'export_chart_btn_id' => "exportsludgeCollectionByTreatmentPlantChart",
    'canvas_id' => "sludgeCollectionByTreatmentPlantChart"
])
@push('scripts')

<script>
(function () {
    // Assume you have a JSON result from the SQL query
    var sqlResult = @json($sludgeCollectionByTreatmentPlantChart);

    // Extract unique treatment_plant_names
    var treatmentPlantNames = [...new Set(sqlResult.map(item => item.treatment_plant_name))];

    // Define a fixed set of colors
    var fixedColors = ["#ffb964", "#023047", "#219EBC", "#8ECAE6","#9D0208","#FFD166","#06D6A0","#FF6B6B"];

    // Organize data for Chart.js
    var datasets = treatmentPlantNames.map((plantName, index) => ({
        label: plantName,
        backgroundColor: fixedColors[index % fixedColors.length],
        data: sqlResult
            .filter(item => item.treatment_plant_name === plantName)
            .map(item => item.sum_volume),
    }));

    var chartData = {
        labels: [...new Set(sqlResult.map(item => item.year))],
        datasets: datasets,
    };

    var options = {

        scales: {
            x: { stacked: true },
            y: { stacked: true },
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
                scaleLabel: {
                    display: true, // Enable the scale label
                    labelString: 'Year' // The label text
                }
            }],
        }
    };
    var ctx = document.getElementById('sludgeCollectionByTreatmentPlantChart').getContext('2d');
    var myChart = new Chart(ctx, {
        type: 'bar',
        data: chartData,
        options: options,
    });
}());
document.getElementById('exportsludgeCollectionByTreatmentPlantChart').addEventListener("click", downloadIMG);
  //donwload pdf from original canvas
  function downloadIMG() {
    var newCanvas = document.querySelector('#sludgeCollectionByTreatmentPlantChart');

    //create image from dummy canvas
    var newCanvasImg = newCanvas.toDataURL("image/png", 1.0);
    var a = document.createElement('a');
    a.href =newCanvas.toDataURL("image/png", 1.0);

    a.download = 'Sludge Collection Trends by Treatment Plants Over the Last 5 Years.png';

    // Trigger the download
    a.click();
      }
</script>
@endpush
