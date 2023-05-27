<?php
$title = "Extend Student's exam time | " . SITE_TITLE;
?>

@extends('institution.layout')

@section('dashboard_content')

<div class="app-title">
	<div>
		<h1>
			<i class="fa fa-dashboard"></i> Exams
		</h1>
		<p>Extend Student's exam time</p>
	</div>
	<ul class="app-breadcrumb breadcrumb">
		<li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i> <a href="{{route('institution.dashboard', $institution->id)}}">Dashboard</a></li>
		<li class="breadcrumb-item"><a href="{{route('institution.exam.index', $institution->id)}}">Exams</a></li>
		<li class="breadcrumb-item">Extend time</li>
	</ul>
</div>
<div>
	<div class="tile">
		<h3 class="tile-title">{{$student->firstname}} {{$student->lastname}}</h3>
		@include('common.message')
		<div>
			<p><span>Time Remaining: </span> {{$timeRemaining}}</p>
		</div>
		<form action="" method="post">
			@csrf
    		<div class="tile-body">
				<div class="form-group w-75">
					<label class="control-label">Extended remainig time by (mins):</label> 
					<input class="form-control" type="number" placeholder="Extend time" name="extend_time" 
						value="{{old('extend_time')}}" >
				</div>
    		</div>
    		<div class="tile-footer">
    			<button class="btn btn-primary" type="submit">
    				<i class="fa fa-fw fa-lg fa-check-circle"></i>Submit
    			</button>
    		</div>
		</form>
	</div>
</div>

@endsection