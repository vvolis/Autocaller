@extends('app')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="header">
                        <h4 class="title">Call scheduler</h4>
                        <p class="category">All calls</p>
                    </div>
                    <div class="content table-responsive table-full-width">
                        <table class="table table-hover table-striped">
                            <thead>
                            {{--<th class="text-center">ID</th>--}}
                            {{--<th class="text-center">Port</th>--}}
                            <th class="text-center">Pool</th>
                            <th class="text-center">Phone</th>
                            <th class="text-center">Call Phone</th>
                            <th class="text-center">Finished</th>
                            <th class="text-center">Status</th>
                            {{--<th class="text-center">Rec.</th>--}}
                            {{--<th class="text-center">Resets</th>--}}
                            {{--<th class="text-center">Err.</th>--}}
                            {{--<th class="text-center">E. Cred.</th>--}}
                            {{--<th class="text-center">R. Cred.</th>--}}
                            <th class="text-center">Start</th>
                            <th class="text-center">End</th>
                            {{--<th class="text-center">Date</th>--}}
                            </thead>
                            <tbody>
                            @foreach($calls as $call)
                                <tr>
                                    {{--<td class="text-center">{{$call['id']}}</td>--}}
                                    {{--<td class="text-center">{{$call['port']}}</td>--}}
                                    <td class="text-center">{{$call['pool']}}</td>
                                    <td class="text-center">{{$call['phone']}}</td>
                                    <td class="text-center">{{$call['call_phone']}}</td>
                                    <td class="text-center"><i class="{{$doneStatus[$call['call_finished']]}}"></i></td>
                                    <td class="text-center"><i class="{{$status[$call['call_status']]}}"></i></td>
                                    {{--<td class="text-center">{{$call['call_reconnects']}}</td>--}}
                                    {{--<td class="text-center">{{$call['call_resets']}}</td>--}}
                                    {{--<td class="text-center">{{$call['call_errors']}}</td>--}}
                                    {{--<td class="text-center">{{$call['credits_expected']}}</td>--}}
                                    {{--<td class="text-center">{{$call['credits_real']}}</td>--}}
                                    <td class="text-center">{{$call['call_start']->format('H:i')}}</td>
                                    <td class="text-center">{{$call['call_end']->format('H:i')}}</td>
                                    {{--<td class="text-center">{{$call['schedule_date']}}</td>--}}
                                </tr>
                            @endforeach
                            </tbody>
                        </table>

                    </div>
                </div>
                {{ $calls->links() }}
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="header">
                        <h4 class="title">Import schedule</h4>
                    </div>
                    <div class="content">
                        <form action="{{ route('import_excel') }}" method="post" enctype="multipart/form-data">
                            <input type="file" name="file" id="file">
                            <input type="submit" value="Upload" name="submit">
                            <input type="hidden" value="{{ csrf_token() }}" name="_token">
                        </form>
                    </div>
                    @if (session('inserted'))
                        <div class="alert alert-success">
                            Inserted : {{ session('inserted') }}
                        </div>
                    @endif
                    @if (session('status'))
                        <div class="alert alert-success">
                            {{ session('status') }}
                        </div>
                    @endif
                </div>

                <div class="card">
                    <div class="header">
                        <h4 class="title">Generator pool</h4>
                    </div>
                    <div class="content">
                        <form class="form-horizontal" method="POST" action="{{ route('postTest') }}">
                            {{ csrf_field() }}

                            <div class="form-group" style="margin: 0;">
                                <label for="carrier" class="control-label">Call pool</label>
                                {{--<input id="email" type="email" class="form-control" name="email" value="{{ old('email') }}" required autofocus>--}}
                                {{--									<input id="email" type="email" class="form-control" name="email" value="{{ old('email') }}" required autofocus>--}}
                                <select id="carrier" name="carrier" class="form-control">
                                    @foreach($callPools as $id => $value)
                                        <option value="{{$id}}">{{$value}}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group" style="margin: 0;">
                                <label for="call_length" class="control-label">Call length (min)</label>
                                <input class="form-control" name="call_length" id="call_length" value="3"/>
                            </div>
                            <div class="form-group" style="margin: 0;">
                                <label for="break_length" class="control-label">Break length (min)</label>
                                <input class="form-control" name="break_length" id="break_length" value="5"/>
                            </div>
                            <div class="form-group" style="margin: 0;">
                                <label for="email" class="control-label">Numbers</label>
                                {{--<input id="email" type="email" class="form-control" name="email" value="{{ old('email') }}" required autofocus>--}}
                                {{--									<input id="email" type="email" class="form-control" name="email" value="{{ old('email') }}" required autofocus>--}}
                                <textarea id="number_list" name="number_list" rows="10" class="form-control"></textarea>
                            </div>

                            <div class="form-group">
                                <div class="col-md-8 col-md-offset-4">
                                    <button class="btn btn-primary">
                                        Generate
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="header">
                        <h4 class="title">Generator phone</h4>
                    </div>
                    <div class="content">
                        <form class="form-horizontal" method="POST" action="{{ route('postTestSingle') }}">
                            {{ csrf_field() }}

                            <div class="form-group" style="margin: 0;">
                                <label for="single_number" class="control-label">Call from single</label>
                                {{--<input id="email" type="email" class="form-control" name="email" value="{{ old('email') }}" required autofocus>--}}
                                {{--									<input id="email" type="email" class="form-control" name="email" value="{{ old('email') }}" required autofocus>--}}
                                <select id="single_number" name="single_number" class="form-control">
                                    @foreach($callPhonePool as $number)
                                        <option value="{{$number['phone']}}">{{$number['phone']}} {{$number['carrier']}}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group" style="margin: 0;">
                                <label for="call_length" class="control-label">Call length (min)</label>
                                <input class="form-control" name="call_length" id="call_length" value="3"/>
                            </div>
                            <div class="form-group" style="margin: 0;">
                                <label for="break_length" class="control-label">Break length (min)</label>
                                <input class="form-control" name="break_length" id="break_length" value="5"/>
                            </div>
                            <div class="form-group" style="margin: 0;">
                                <label for="email" class="control-label">Numbers</label>
                                {{--<input id="email" type="email" class="form-control" name="email" value="{{ old('email') }}" required autofocus>--}}
                                {{--									<input id="email" type="email" class="form-control" name="email" value="{{ old('email') }}" required autofocus>--}}
                                <textarea id="number_list" name="number_list" rows="10" class="form-control"></textarea>
                            </div>

                            <div class="form-group">
                                <div class="col-md-8 col-md-offset-4">
                                    <button class="btn btn-primary">
                                        Generate
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection
