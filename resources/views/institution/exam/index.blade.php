<?php
$title = 'Institution - All Registered Exams | ' . SITE_TITLE;
$confirmMsg = 'Are you sure?';

// dDie($allRecords->toArray());
?>
@extends('institution.layout')

@section('dashboard_content')

<div class="app-title">
	<div>
		<h1>
			<i class="fa fa-dashboard"></i> Exams
		</h1>
		<p>List of Student Exams</p>
	</div>
	<ul class="app-breadcrumb breadcrumb">
		<li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i> <a href="{{route('institution.dashboard', $institution->id)}}">Dashboard</a></li>
		<li class="breadcrumb-item">Exams</li>
	</ul>
</div>
@include('common.message')
<div class="tile">
    <div class="tile-header clearfix mb-3">
    	<a href="{{route('institution.exam.create', $institution->id)}}" class="btn btn-primary float-left">
    		<i class="fa fa-plus"></i> Add
    	</a>
    	<div class="form-group row float-right">
			<label for="select-grade" class="col-sm-5 col-form-label">Select Event</label>
			<div class="col-sm-7">
				<select name="event_id" id="select-event" class="form-control">
					<option value="">All Events</option>
					@foreach($allEvents as $event)
						<option value="{{$event->id}}" <?= markSelected($event->id, $eventId) ?>
						title="{{$event->description}}" >{{$event->title}}</option>
					@endforeach
				</select>
			</div>
		</div>
    </div>
    <div class="tile-body">
    	<table class="table table-hover table-bordered" id="data-table" >
    		<thead>
    			<tr>
    				<th>Student Name</th>
    				<th>Exam No</th>
    				<th>Event</th>
<!--     				<th>Subjects</th> -->
    				<th>Duration</th>
    				<th>Status</th>
    				<th><i class="fa fa-bars p-2"></i></th>
    			</tr>
    		</thead>
			@foreach($allRecords as $record)
			<?php
   $examSubjects = $record['examSubjects'];
   $event = $record['event'];
   $mySubjects = '';
   foreach ($examSubjects as $subject) {
     $session = $subject['session'];

     $mySubjects .=
       $session['course']['course_code'] . " ({$session['session']}), ";
   }
   $mySubjects = rtrim($mySubjects, ', ');
   $examNo = $record['exam_no'];
   $studentId = $record['student_id'];
   $student = $record['student'];
   ?>
				<tr title="Subjects: [{{$mySubjects}}]" >
					<td>{{$student['lastname']}} {{$student['firstname']}}</td>
					<td>{{$record['exam_no']}}</td>
					<td>
						<a href="{{route('institution.event.show', [$institution->id, $event->id])}}" 
							class="btn-link">{{$event['title']}}</a>
					</td>
					<?php
/*
					<td>{{$mySubjects}}</td>
					*/
?>
					<td>{{\App\Core\Settings::splitTime($event['duration'], true)}}</td>
					<td>
						@if($record['status'] == 'active')
							@if(empty($record['start_time']))
								<button class="btn btn-success">Ready</button>
							@else
								<button class="btn btn-success">{{$record['status']}}</button>
							@endif
						@elseif($record['status'] == 'paused')
						<button class="btn btn-warning">{{$record[STATUS]}}</button>						
						@elseif($record['status'] == 'suspended')
						<button class="btn btn-danger">{{$record['status']}}</button>						
						@else
						<a href="{{route('home.exam.view-result', [$examNo])}}" class="btn btn-link">View Results</a>
						@endif
					</td>
					<td>
						<i class="fa fa-bars p-2 pointer"
						   tabindex="0"
						   role="button" 
                           data-html="true" 
                           data-toggle="popover" 
                           title="Options" 
                           data-placement="bottom"
                           data-content="<div>
                            <?php
/*
                            <div><small><i class='fa fa-pencil'></i> 
                            <a onclick='return confirmAction()' href='{{route('center_edit_exam', [$record[TABLE_ID]])}}' class='btn btn-link'>Edit</a></small></div>
                            */
?>
                            <div><small><i class='fa fa-circle-o'></i> 
                            <a href='{{route('institution.exam.extend', [$institution->id, $record['exam_no']])}}' class='btn btn-link'>Extend Exam Time</a></small></div>
                            <div><small><i class='fa fa-trash'></i> 
                            	<a onclick='return confirmAction()' href='{{route('institution.exam.destroy', [$institution->id, $record['id']])}}' class='btn btn-link text-danger'>Delete</a></small></div>
                            </div>
                            "></i>
					</td>
				</tr>
			@endforeach
		</table>
	</div>
	<div class="tile-footer">
		@include('common.paginate')
	</div>
</div>

<script type="text/javascript">

var baseUrl = {!!json_encode(route('institution.exam.index', $institution->id))!!};

$(function () {
//   $('[data-toggle="popover"]').popover();
  var popOverSettings = {
// 	    placement: 'bottom',
// 	    container: 'body',
// 	    html: true,
	    selector: '[data-toggle="popover"]', //Sepcify the selector here
// 	    content: function () {
// 	        return $('#popover-content').html();
// 	    }
	}
	
	$('#data-table').popover(popOverSettings);
	
	
	$('#select-event').on('change', function(e) {
		
		var selectedEventId = $(this).val();
		
		if(!selectedEventId){
			window.location.href = baseUrl;
			return;
		}
		
		window.location.href = baseUrl + '/' + selectedEventId;
	});
});

function confirmAction() {
	return confirm('{{$confirmMsg}}');
}
</script>

@stop
