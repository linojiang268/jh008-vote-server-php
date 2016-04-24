@extends('layouts.main')

@section('title', '活动不存在')

@section('stylesheet')

@endsection

@section('content')
     <div class="m_1">
        @if (!$errors->isEmpty())
            {{ $errors->first() }}
        @endif
     </div>
@endsection
