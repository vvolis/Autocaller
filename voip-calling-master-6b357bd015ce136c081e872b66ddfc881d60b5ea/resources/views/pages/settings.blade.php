@extends('app')

@section('content')
    @include('flash::message')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="header">
                        <h4 class="title">System settings</h4>
                        {{--<p class="category">Stats for current session</p>--}}
                    </div>
                    <div class="content">
                        @foreach($settings as $id => $value)
                            @if($value)
                                <a href="{{url('settings/job/'.$id.'/disable')}}"
                                   class="btn btn-danger btn-block btn-fill">
                                    Disable {{$id}}
                                </a>
                            @else
                                <a href="{{url('settings/job/'.$id.'/enable')}}"
                                   class="btn btn-info btn-block btn-fill">
                                    Enable {{$id}}
                                </a>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="header">
                        <h4 class="title">System Tools</h4>
                    </div>
                    <div class="content">
                        <a href="{{url('settings/delete_schedules')}}" class="btn btn-info btn-block btn-fill">
                            Reset schedules
                        </a>
                        <a href="{{url('settings/check_modems')}}" class="btn btn-info btn-block btn-fill">
                            Check modems
                        </a>
                        <a href="{{url('settings/read_usb')}}" class="btn btn-info btn-block btn-fill">
                            Read USBs
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="header">
                        <h4 class="title">Schedule logging
                            <i id="scheduleLogsLoading" class="pe-7s-config pe-spin"></i>
                        </h4>
                    </div>
                    <div class="content">
                        <div id="scheduleLogs" class="console-box"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="header">
                        <h4 class="title">System logging
                            <i id="systemLogsLoading" class="pe-7s-config pe-spin"></i>
                        </h4>
                    </div>
                    <div class="content">
                        <div id="systemLogs" class="console-box"></div>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection

@section('js_after')

    <script>

		function loadDailyLogs() {
			var $container = $("#scheduleLogs");
			var $containerLoading = $('#scheduleLogsLoading');

			$.ajax({
				url       : './settings/get_daily_logs/{{\Carbon\Carbon::now()->toDateString()}}',
				type      : "GET",
				beforeSend: function () {
					$containerLoading.show();
					$("#scheduleLogs").addClass('console-blur');
				},
				complete  : function () {
					$containerLoading.hide();
					$("#scheduleLogs").removeClass('console-blur');
					$container.scrollTop($container[0].scrollHeight);
				},
				success   : function (data) {
					$container.html('');
					$container.append(data);
				}
			});
		}

		function loadSystemLogs() {

			var $container = $("#systemLogs");
			var $containerLoading = $("#systemLogsLoading");

			$.ajax({
				url       : './settings/get_system_logs',
				type      : "GET",
				beforeSend: function () {
					$containerLoading.show();
					$("#systemLogs").addClass('console-blur');
				},
				complete  : function () {
					$containerLoading.hide();
					$("#systemLogs").removeClass('console-blur');
					$container.scrollTop($container[0].scrollHeight);
				},
				success   : function (data) {
					$container.html('');
					$container.append(data);
				}
			});
		}

		$(document).ready(function () {

			$.ajaxSetup({
				cache: false,
			});

			loadDailyLogs();
			loadSystemLogs();

			var refreshDaily = setInterval(function () {
				loadDailyLogs();
				loadSystemLogs();
			}, 7500);

		});
    </script>

@endsection
