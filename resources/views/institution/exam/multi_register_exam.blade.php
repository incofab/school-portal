<?php

$title = "Multi Register Exam | " . SITE_TITLE;
$donFlashMessages = true;
$errors = isset($errors) ? $errors : $sessionModel->getFlash('error');
$post = isset($post) ? $post : [];
$valErrors = $sessionModel->getFlash('val_errors', []);
if($valErrors) $errors = null;
$subjects = [];
?>

@extends('centers.layout')

@section('dashboard_content')

<div class="app-title">
	<div>
		<h1>
			<i class="fa fa-dashboard"></i> Exams
		</h1>
		<p>Register all students for {{$event[TITLE]}}</p>
	</div>
	<ul class="app-breadcrumb breadcrumb">
		<li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i> <a href="{{getAddr('center_dashboard')}}">Dashboard</a></li>
		<li class="breadcrumb-item"><a href="{{getAddr('center_view_all_exams')}}">Exams</a></li>
		<li class="breadcrumb-item">Multi Exam Register</li>
	</ul>
</div>
<div>
	<div class="tile">
		<h3 class="tile-title">Register Exam for {{$event[TITLE]}}</h3>
		<div class="">
			<dl class="row">
				<dt class="col-sm-3">Event</dt>
				<dd class="col-sm-9">{{$event[TITLE]}}</dd>
				<dt class="col-sm-3">Duration</dt>
				<dd class="col-sm-9">{{\App\Core\Settings::splitTime($event[DURATION], true)}}</dd>
			</dl>
		</div>
    	@if($valErrors)
    	<div class="alert alert-danger">
    		@foreach($valErrors as $vError)
    			<p><i class="fa fa-star" style="color: #cc4141;"></i> {{implode('<br />', $vError)}}</p>
    		@endforeach
    	</div>
    	@endif
    	@if($errors)
    	<div class="alert alert-danger">
   			<div><i class="fa fa-star" style="color: #cc4141;"></i> {{$errors}}</div>
    	</div>
    	@endif
		<form action="" method="post">
    		<div class="tile-body" style="overflow: auto;">
        	<table class="table table-hover table-bordered" id="data-table" >
        		<thead>
        			<tr>
        				<th>Student Name</th>
        				<th>Exam Subjects</th>
        			</tr>
        		</thead>
				<tbody>
				<?php $i = 0; ?>
				@foreach($students as $student)
				<tr>
					<td>
						{{$student[LASTNAME]}} {{$student[FIRSTNAME]}}
						<input type="hidden" name="{{STUDENT_ID}}[{{$i}}]" value="{{$student[STUDENT_ID]}}" />
					</td>
					<td>
        				<div class="form-group w-100" style="max-width: 500px" >
        					<select name="{{COURSE_SESSION_ID}}[{{$i}}][]"
        						class="form-control select-subjects" multiple="multiple" >
        						@foreach($eventSubjects as $eventSubject)
        						<option value="{{$eventSubject[COURSE_SESSION_ID]}}" >{{$eventSubject['course'][COURSE_CODE]}}</option>
        						@endforeach
        					</select>
        				</div>
					</td>
				<?php $i++; ?>
				@endforeach
				</tbody>
			</table>
    		</div>
    		<div class="tile-footer">
    			<button class="btn btn-primary" type="submit" onclick="return confirm('Note: This will take some time and should not be interrupted. \n\nContinue?')">
    				<i class="fa fa-fw fa-lg fa-check-circle"></i>Register
    			</button>
    			&nbsp;&nbsp;&nbsp;
    			<a class="btn btn-secondary" href="{{getAddr('center_view_all_events')}}"><i class="fa fa-fw fa-lg fa-times-circle"></i>Cancel</a>
    		</div>
		</form>
	</div>
</div>
<script type="text/javascript" src="{{assets('lib/select2.min.js')}}"></script>
<script type="text/javascript">
$('.select-subjects').select2();
// var eventSubjects = {{json_encode($subjects)}};
$(function() {
// 	eventSelected($('form select[name="event_id"]'));
// 	$('form select[name="event_id"]').on('change', function(e) {
// 		eventSelected($(this));
// 	});
});
// function eventSelected(obj) {
// 	var eventId = obj.val();
// 	var subjects = eventSubjects[eventId];
// 	var s = '';
// 	subjects.forEach(function(subject, i) {
// 		s += '<option value="'+subject.course_session_id+'">'+subject.course.course_title+'</option>';
// 	});
// 	$('#select-subjects').html(s);
// 	$('form .select2-selection__rendered').html(''); // For Select 2 plugin
// }
</script>

@endsection