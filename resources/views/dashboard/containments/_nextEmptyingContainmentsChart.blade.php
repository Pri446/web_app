<div class="box box-primary">
  <div class="box-header with-border">
    <h3 class="box-title">Expected Emptying Date By Year-Month
      <div class="box-tools float-right">
        <button id="exportnextEmptyingContainmentsChart" type="button" class="btn btn-box-tool"><i class="fa-solid fa-image"> </i></button>
        <button type="button" class="btn btn-box-tool"data-toggle="collapse" data-target="#nextEmptyingContainmentsChart1"><i class="fa fa-minus"></i></button>
      </div>
    </h3>
  </div>
  <div class="box-body collapse show" id =nextEmptyingContainmentsChart1>
    <canvas id="nextEmptyingContainmentsChart" style="height:250px"></canvas>
  </div>
  <!-- /.box-body -->
</div>
<!-- /.box -->

@push('scripts')
<script>
var ctx = document.getElementById("nextEmptyingContainmentsChart");
var myChart = new Chart(ctx, {
  type: 'bar',
  data: {
    labels: @json(array_values(array_map(function($x) { return is_string($x) ? trim($x, '"\'') : $x; }, (array)($nextEmptyingContainmentsChart['labels'] ?? [])))),
    datasets: [
        {
            label: "No. of containments",
            backgroundColor: "rgba(90, 155, 212,0.2)",
            borderColor: "rgba(90, 155, 212,1)",
            borderWidth: 1,
            hoverBackgroundColor: "rgba(90, 155, 212,0.4)",
            hoverBorderColor: "rgba(90, 155, 212,1)",
            data: @json(array_values($nextEmptyingContainmentsChart['values'] ?? [])),
        }
    ]
},
  options: {
    animation:{
      animateScale:true
    },
    scales: {
        yAxes: [{
            ticks: {
                beginAtZero: true,
                steps: 10,
                stepValue: 5,
                max: 20
            }
        }]
    }
  }
});
document.getElementById('exportnextEmptyingContainmentsChart').addEventListener("click", downloadIMG);
  //donwload pdf from original canvas
  function downloadIMG() {
    var newCanvas = document.querySelector('#nextEmptyingContainmentsChart');

    //create image from dummy canvas
    var newCanvasImg = newCanvas.toDataURL("image/png", 1.0);
    var a = document.createElement('a');
    a.href =newCanvas.toDataURL("image/png", 1.0);
   
    a.download = 'Expected Emptying Date by Year-Month.png';

    // Trigger the download
    a.click();
      }
</script>
@endpush