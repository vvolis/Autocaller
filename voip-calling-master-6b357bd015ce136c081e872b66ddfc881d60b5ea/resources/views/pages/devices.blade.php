@extends('app')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="header">
                        <h4 class="title">Device list ({{count($devices)}})</h4>
                        <p class="category">All connected USB devices</p>
                    </div>
                    <div class="content table-responsive table-full-width">
                        <table class="table table-hover table-striped">
                            <thead>
                            <th class="text-center">Path</th>
                            <th class="text-center">Port</th>
                            <th class="text-center">Phone</th>
                            <th class="text-center">Credits starting</th>
                            <th class="text-center">Credits limit</th>
                            </thead>
                            <tbody>
                            @foreach($devices as $device)
                                <tr>
                                    <td class="text-center">{{$device['path']}}</td>
                                    <td class="text-center">{{$device['port']}}</td>
                                    <td class="text-center">+{{$device['phone']}}</td>
                                    <td class="text-center">{{$device['credits_start']}}</td>
                                    <td class="text-center">{{$device['credits_limit']}}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>

                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection