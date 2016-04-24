@extends('layouts.main')

@section('title', '活动明细')

@section('stylesheet')
    <link rel="stylesheet" href="/static/css/finance/finance.css"/>
@endsection

@section('content')
    <div class="section-bd section-indexDetail">
    	<h2><span class="activity-title">{{$activity['title']}}</span>活动成员明细</h2>
        <div class="table-w payment-table">
        </div>
    </div>
@endsection

@section('javascript')
    <script type="text/javascript" src="/static/js/finance/indexDetail.js?201511031515"></script>
    <script>
    	var activity_id = {{$activity['id']}};
    	console.log(activity_id);
    </script>
@endsection
