@extends('app')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="header">
                        <h4 class="title">Session stats</h4>
                        <p class="category">Stats for current session</p>
                    </div>
                    <div class="content">
                        <div id="sessionChart" class="ct-chart ct-perfect-fourth"></div>

                        <div class="footer">
                            <div class="legend">
                                <i class="fa fa-circle text-info"></i> Earned
                                <i class="fa fa-circle text-danger"></i> Missing
                                {{--<i class="fa fa-circle text-warning"></i> Unsubscribe--}}
                            </div>
                            <hr>
                            <div class="stats">
                                <i class="fa fa-clock-o"></i> {{$devicesCreditsLast->created_at}}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card">
                    <div class="header">
                        <h4 class="title">Session device list ({{count($devices)}})</h4>
                        <p class="category">All connected USB devices</p>
                    </div>
                    <div class="content table-responsive table-full-width">
                        <table class="table table-hover table-striped">
                            <thead>
                            <th class="text-center">Phone</th>
                            <th class="text-center">Credits starting</th>
                            <th class="text-center">Credits limit</th>
                            <th class="text-center">Credits Earned</th>
                            <th class="text-center">Success rate</th>
                            </thead>
                            <tbody>
                            @php
                                $totalPercent = 0;
                            @endphp
                            @foreach($devices as $device)
                                @php
                                    $percentEarned = 100 - round(($device['credits_limit'] - $creditsCurrent[$device['phone']]['credits']) / ($device['credits_limit'] - $device['credits_start'])  * 100, 0);
                                    $totalPercent += $percentEarned;
                                @endphp
                                <tr>
                                    <td class="text-center">+{{$device['phone']}}</td>
                                    <td class="text-center">{{$device['credits_start']}}</td>
                                    <td class="text-center">{{$device['credits_limit']}}</td>
                                    <td class="text-center">{{$creditsCurrent[$device['phone']]['credits']}}</td>
                                    <td class="text-center {{($percentEarned > 85) ? 'color-green' : ''}} {{($percentEarned <= 60) ? 'color-red' : ''}} {{($percentEarned >= 60 && $percentEarned <= 85) ? 'color-middle' : ''}}">
                                        {{$percentEarned}} %
                                    </td>
                                </tr>
                            @endforeach
                            <tr>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td rowspan="4" class="text-center" style="font-weight: bold;">
                                    {{round($totalPercent / count($devices))}} %
                                </td>
                            </tr>
                            </tbody>
                        </table>

                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection

@section('js_after')

    <script type="text/javascript">
		$(document).ready(function () {

			Chartist.Pie('#sessionChart', {
				donut     : true,
				donutWidth: 40,
				startAngle: 0,
				total     : 100,
				showLabel : false,
				axisX     : {
					showGrid: false
				},
				labels    : {!! json_encode($statsChart['session']['labels']) !!},
				series    : {!! json_encode($statsChart['session']['series']) !!}
			});

		});
    </script>

@endsection