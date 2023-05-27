<?php
$title = "Register Exam for Class | " . SITE_TITLE;
$subjects = [];
?>

@extends('institution.layout')

@section('dashboard_content')

<div class="app-title">
	<div>
		<h1>
			<i class="fa fa-dashboard"></i> Exams
		</h1>
		<p>Register Exam for Selected Class</p>
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
		<form action="{{route('institution.exam.grade.store', $institution->id)}}" method="post">
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
				
				<div class="form-group">
					<label class="control-label">Class</label> 
					<select name="grade_id" id="select-grade" class="form-control">
    					<option value="">Select Classs</option>
    					@foreach($allGrades as $grade)
    						<option value="{{$grade->id}}" <?= markSelected($grade->id, $gradeId) ?>
    						title="{{$grade->description}}" >{{$grade->title}}</option>
    					@endforeach
					</select>
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