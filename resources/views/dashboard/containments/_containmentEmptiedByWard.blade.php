<div class="box box-primary">
  <div class="box-header with-border">
    <h3 class="box-title">Containment Emptied by Ward
      <div class="box-tools float-right">
        <button id="exportcontainmentEmptiedByWardChart" type="button" class="btn btn-box-tool"><i class="fa-solid fa-image"> </i></button>
        <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
      </div>
    </h3>
  </div>
  <div class="box-body">
    <canvas id="containmentEmptiedByWardChart" style="height:250px"></canvas>
  </div>
  <!-- /.box-body -->
</div>
<!-- /.box -->

@push('scripts')
<script>
var ctx = document.getElementById("containmentEmptiedByWardChart");
var myChart = new Chart(ctx, {
  type: 'bar',
  data: {
    labels: @json(array_values(array_map(function($x) { return is_string($x) ? trim($x, '"\'') : $x; }, (array)($containmentEmptiedByWardChart['labels'] ?? [])))),
    datasets: [
        {
            label: "No. of contatainments",
            backgroundColor: "rgba(90, 155, 212,0.2)",
            borderColor: "rgba(90, 155, 212,1)",
            borderWidth: 1,
            hoverBackgroundColor: "rgba(90, 155, 212,0.4)",
            hoverBorderColor: "rgba(90, 155, 212,1)",
            data: @json(array_values($containmentEmptiedByWardChart['values'] ?? [])),
        }
    ]
},
  options: {
    animation:{
      animateScale:true
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
document.getElementById('exportcontainmentEmptiedByWardChart').addEventListener("click", downloadIMG);
  //donwload pdf from original canvas
  function downloadIMG() {
    var newCanvas = document.querySelector('#containmentEmptiedByWardChart');

    //create image from dummy canvas
    var newCanvasImg = newCanvas.toDataURL("image/png", 1.0);
    var a = document.createElement('a');
    a.href =newCanvas.toDataURL("image/png", 1.0);
   
    a.download = 'Number of Application by Help desk.png';

    // Trigger the download
    a.click();
      }
</script>
@endpush