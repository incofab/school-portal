<?php
$title = "Register Exam | " . SITE_TITLE;
$subjects = [];
?>

@extends('institution.layout')

@section('dashboard_content')

<div class="app-title">
	<div>
		<h1>
			<i class="fa fa-dashboard"></i> Exams
		</h1>
		<p>Register Exam</p>
	</div>
	<ul class="app-breadcrumb breadcrumb">
		<li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i> <a href="{{route('institution.dashboard', $institution->id)}}">Dashboard</a></li>
		<li class="breadcrumb-item"><a href="{{route('institution.exam.index', $institution->id)}}">Exams</a></li>
		<li class="breadcrumb-item">Register</li>
	</ul>
</div>
@include('common.message')
<div>
	<div class="tile">
		<h3 class="tile-title">Register Exam</h3>
		@if(!empty($student))
		<div class="">
			<dl class="row">
				<dt class="col-sm-3">Name</dt>
				<dd class="col-sm-9">{{$student['lastname']}} {{$student['firstname']}}</dd>
				<dt class="col-sm-3">Class</dt>
				<dd class="col-sm-9">{{Arr::get($student->grade, 'title')}}</dd>
				<dt class="col-sm-3">Student ID</dt>
				<dd class="col-sm-9">{{$student['student_id']}}</dd>
			</dl>
		</div>
		@endif
		<form action="{{route('institution.exam.store', $institution->id)}}" method="post">
    		@csrf
    		<div class="tile-body">
				<div class="form-group w-75">
					<label class="control-label">Event</label>
					<select name="event_id" required="required" class="form-control" >
						@foreach($events as $event)
						<option value="{{$event['id']}}" 
						  <?= markSelected(old('event_id'), $event['id']) ?>>{{$event['title']}}</option>
						<?php
						  $subjects[$event['id']] = $event->eventSubjects()->with('course')->get()->toArray();
						?>
						@endforeach
					</select>
				</div>

				<div class="form-group w-75" >
					<label class="control-label">Subjects</label>
					<select name="course_session_id[]" id="select-subjects" required="required" 
						class="form-control" multiple="multiple" >
					</select>
				</div>
				
				<div class="form-group w-75" >
					<label class="control-label">Student ID</label>
					<input type="text" name="student_id" class="form-control" 
					value="{{old('student_id', $student_id)}}" />
				</div>
    		</div>
    		<div class="tile-footer">
    			<button class="btn btn-primary" type="submit">
    				<i class="fa fa-fw fa-lg fa-check-circle"></i>Register
    			</button>
    		</div>
		</form>
	</div>
</div>
<script type="text/javascript" src="{{assets('lib/select2.min.js')}}"></script>
<script type="text/javascript">
$('#select-subjects').select2();
var eventSubjects = {!!json_encode($subjects)!!};
$(function() {
	eventSelected($('form select[name="event_id"]'));
	$('form select[name="event_id"]').on('change', function(e) {
		eventSelected($(this));
	});
});
function eventSelected(obj) {
	var eventId = obj.val();
	var subjects = eventSubjects[eventId];
	var s = '';
	subjects.forEach(function(subject, i) {
		s += '<option value="'+subject.course_session_id+'">'+subject.course.course_title+'</option>';
	});
	$('#select-subjects').html(s);
	$('form .select2-selection__rendered').html(''); // For Select 2 plugin
}
</script>

@endsection