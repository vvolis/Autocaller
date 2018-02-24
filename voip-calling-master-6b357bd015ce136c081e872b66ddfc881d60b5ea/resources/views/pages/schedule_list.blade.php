@extends('app')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="header">
                        <h4 class="title">Call scheduler</h4>
                        <p class="category">All calls</p>
                    </div>
                    <div class="content table-responsive table-full-width">
                        <table class="table table-hover table-striped">
                            <thead>
                            <th class="text-center">ID</th>
                            {{--<th class="text-center">Port</th>--}}
                            <th class="text-center">Phone</th>
                            <th class="text-center">Call Phone</th>
                            <th class="text-center">Finished</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Rec.</th>
                            {{--<th class="text-center">Resets</th>--}}
                            {{--<th class="text-center">Err.</th>--}}
                            <th class="text-center">E. Cred.</th>
                            {{--<th class="text-center">R. Cred.</th>--}}
                            <th class="text-center">Start</th>
                            <th class="text-center">End</th>
                            <th class="text-center">Date</th>
                            </thead>
                            <tbody>
                            @foreach($calls as $call)
                                <tr>
                                    <td class="text-center">{{$call['id']}}</td>
                                    {{--<td class="text-center">{{$call['port']}}</td>--}}
                                    <td class="text-center">{{$call['phone']}}</td>
                                    <td class="text-center">{{$call['call_phone']}}</td>
                                    <td class="text-center"><i class="{{$doneStatus[$call['call_finished']]}}"></i></td>
                                    <td class="text-center"><i class="{{$status[$call['call_status']]}}"></i></td>
                                    <td class="text-center">{{$call['call_reconnects']}}</td>
                                    {{--<td class="text-center">{{$call['call_resets']}}</td>--}}
                                    {{--<td class="text-center">{{$call['call_errors']}}</td>--}}
                                    <td class="text-center">{{$call['credits_expected']}}</td>
                                    {{--<td class="text-center">{{$call['credits_real']}}</td>--}}
                                    <td class="text-center">{{$call['call_start']->format('H:i')}}</td>
                                    <td class="text-center">{{$call['call_end']->format('H:i')}}</td>
                                    <td class="text-center">{{$call['schedule_date']}}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>

                    </div>
                </div>
                {{ $calls->links() }}
            </div>

        </div>
    </div>
@endsection