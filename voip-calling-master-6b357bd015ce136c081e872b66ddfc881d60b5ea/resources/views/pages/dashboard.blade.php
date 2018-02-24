@extends('app')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="header">
                        <h4 class="title">Session stats</h4>
                        <p class="category">Stats now - {{$smallStats['now']}} / {{$smallStats['total']}} </p>
                    </div>
                    <div class="content">

                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card">
                    <div class="header">
                        <h4 class="title">Credits per hours</h4>
                    </div>
                    <div class="content">
                        <div id="chartHours" class="ct-chart"></div>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection

@section('js_after')

    <script type="text/javascript">
		$(document).ready(function () {

			var dataSales = {
				labels: {!! json_encode($stats['credits_line']['labels']) !!},
				series: [{!! json_encode($stats['credits_line']['series']) !!}]
			};

			var optionsSales = {
				lineSmooth: true,
				low: 0,
				high: {{$max}},
				showArea: true,
				height: "245px",
				axisX: {
					showGrid: false,
				},
				lineSmooth: Chartist.Interpolation.simple({
					divisor: 10
				}),
				showLine: true,
				showPoint: true,
			};

			var responsiveSales = [
				['screen and (max-width: 640px)', {
					axisX: {
						labelInterpolationFnc: function (value) {
							return value[0];
						}
					}
				}]
			];

			Chartist.Line('#chartHours', dataSales, optionsSales, responsiveSales);

		});
    </script>

@endsection