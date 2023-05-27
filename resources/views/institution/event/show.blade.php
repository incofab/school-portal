<?php
$title = "Preview | " . SITE_TITLE;

$dur = \App\Core\Settings::splitTime($event['duration']);
?>

@extends('institution.layout')

@section('dashboard_content')

<div class="app-title">
	<div>
		<h1>
			<i class="fa fa-dashboard"></i> Events
		</h1>
		<p>Preview Event</p>
	</div>
	<ul class="app-breadcrumb breadcrumb">
		<li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i> <a href="{{route('institution.dashboard', $institution->id)}}">Dashboard</a></li>
		<li class="breadcrumb-item"><a href="{{route('institution.event.index', $institution->id)}}">Events</a></li>
		<li class="breadcrumb-item">Preview Event</li>
	</ul>
</div>
<div>
	<div class="tile">
		<div class="tile-header clearfix">
        	<a href="{{route('institution.event.edit', [$institution->id, $event->id])}}" class="btn btn-primary btn-sm pull-right">
        		<i class="fa fa-pencil"></i> Edit
        	</a>
        </div>
		<h3 class="tile-title">{{$event['title']}}</h3>
		<div class="tile-body">
			<dl class="row">
				<dt class="col-md-3">Description</dt>
				<dd class="col-md-9">{{empty($event['description']) ? '-' : $event['description']}}</dd>
				<dt class="col-md-3 mt-3">Duration</dt>
				<dd class="col-md-9 mt-3"><?= "{$dur['hours']}hrs, {$dur['minutes']}mins, {$dur['seconds']}secs" ?></dd>
			</dl>
			<div class="mt-3 mb-1 h6">Subjects</div>
			<ul class="list-group">
				@foreach($eventSubjects as $subject)
				<?php 
				   $course = $subject['course']; 
				   $acadSession = $subject['session']; 
				?>
				<li class="list-group-item">{{$course->course_title.' - '.$acadSession->session}}</li>
				@endforeach
			</ul>
		</div>
	</div>
</div>

@endsection