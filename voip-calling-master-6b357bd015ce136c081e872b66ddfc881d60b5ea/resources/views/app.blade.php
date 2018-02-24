<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
    <title>VOIP</title>
    <meta content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0' name='viewport'/>
    <meta name="viewport" content="width=device-width"/>
    <link href="./assets/css/bootstrap.min.css" rel="stylesheet"/>
    <link href="./assets/css/light-bootstrap-dashboard.css" rel="stylesheet"/>
    <link href='https://fonts.googleapis.com/css?family=Roboto:400,700,300' rel='stylesheet' type='text/css'>
    <link href="https://fonts.googleapis.com/css?family=Inconsolata:400,700" rel="stylesheet">
    <link href="./assets/css/pe-icon-7-stroke.css" rel="stylesheet"/>
    <link href="./assets/css/pe-icon-7-stroke-helper.css" rel="stylesheet"/>
    <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css" rel="stylesheet">
    <style>
        .color-green {
            color: darkgreen;
        }

        .color-wait {
            color: dodgerblue;
        }

        .color-red {
            color: darkred;
        }

        .color-middle {
            color: #1F77D0;
        }

        .console-box {
            height: 300px;
            overflow: scroll;
            white-space: nowrap;
            line-height: 1;
            background: #000;
            color: #fff;
            padding: 10px;
            border-radius: 5px;
            font-family: 'Inconsolata', monospace;
        }

        .console-blur {
            -webkit-filter: blur(1px);
            -moz-filter: blur(1px);
            -o-filter: blur(1px);
            -ms-filter: blur(1px);
            filter: blur(1px);
        }

        .ct-label {
            fill: rgba(255, 255, 255, 1);
        }

        .sidebar .nav > li.active-pro {
            position: absolute;
            width: 100%;
            bottom: 10px;
        }

        .navbar {
            margin-bottom: 0px;
        }
    </style>
</head>
<body>

<div class="wrapper">
    {{--@if (Auth::check())--}}
    <div class="sidebar" data-color="purple">
        <div class="sidebar-wrapper">

                <ul class="nav">


                    {{--<li class="{{Request::is('/') ? 'active' : ''}}">--}}
                        {{--<a href="{{url('/')}}">--}}
                            {{--<i class="pe-7s-graph1"></i>--}}
                            {{--<p>Dashboard</p>--}}
                        {{--</a>--}}
                    {{--</li>--}}

                    <li class="{{Request::is('1234') ? 'active' : ''}}">
                        <a href="{{url('1234')}}">
                            <i class="pe-7s-graph1"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>

                    <li class="{{Request::is('calls') ? 'active' : ''}}">
                        <a href="{{url('calls')}}">
                            <i class="pe-7s-headphones"></i>
                            <p>Scheduler</p>
                        </a>
                    </li>

                    <li class="{{Request::is('calls_active') ? 'active' : ''}}">
                        <a href="{{url('calls_active')}}">
                            <i class="pe-7s-headphones"></i>
                            <p>Active</p>
                        </a>
                    </li>

                    <li class="{{Request::is('settings') ? 'active' : ''}}">
                        <a href="{{url('settings')}}">
                            <i class="pe-7s-edit"></i>
                            <p>Settings</p>
                        </a>
                    </li>
                    @if (Auth::check())
                    <li class="{{Request::is('credits') ? 'active' : ''}}">
                        <a href="{{url('credits')}}">
                            <i class="pe-7s-call"></i>
                            <p>Session</p>
                        </a>
                    </li>

                    <li class="{{Request::is('calls') ? 'active' : ''}}">
                        <a href="{{url('calls')}}">
                            <i class="pe-7s-headphones"></i>
                            <p>Scheduler</p>
                        </a>
                    </li>

                    <li class="{{Request::is('devices') ? 'active' : ''}}">
                        <a href="{{url('devices')}}">
                            <i class="pe-7s-usb"></i>
                            <p>Devices</p>
                        </a>
                    </li>

                    <li class="{{Request::is('settings') ? 'active' : ''}}">
                        <a href="{{url('settings')}}">
                            <i class="pe-7s-edit"></i>
                            <p>Settings</p>
                        </a>
                    </li>

                    <li class="active active-pro">
                        <a href="{{ route('logout') }}">
                            <i class="pe-7s-rocket"></i>
                            <p>Logout</p>
                        </a>
                    </li>
                    @endif
                </ul>

        </div>
    </div>
    {{--@endif--}}

    {{--    @if (Auth::check())--}}
    <nav class="navbar navbar-default navbar-fixed visible-xs">
        <div class="container-fluid">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle" data-toggle="collapse"
                        data-target="#navigation-example-2">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>

            </div>
            <div class="collapse navbar-collapse">
                <ul class="nav navbar-nav navbar-right"></ul>
            </div>
        </div>
    </nav>

    <div class="main-panel">
        <div class="content">
            @yield('content')
        </div>
    </div>
    {{--@else--}}
    {{--<div style="margin-top: 20px;">--}}
    {{--@yield('content')--}}
    {{--</div>--}}
    {{--@endif--}}

</div>

</body>
<script src="./assets/js/jquery-1.10.2.js"></script>
<script src="./assets/js/bootstrap.min.js"></script>
<script src="./assets/js/chartist.min.js"></script>
<script src="./assets/js/light-bootstrap-dashboard.js"></script>
@yield('js_after')
</html>
