<?php
$title = "Institution - All Events | " . SITE_TITLE;
$confirmMsg = 'Are you sure?';
?>
@extends('institution.layout')

@section('dashboard_content')

<div class="app-title">
	<div>
		<h1>
			<i class="fa fa-dashboard"></i> Events
		</h1>
		<p>List of all Events</p>
	</div>
	<ul class="app-breadcrumb breadcrumb">
		<li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i> <a href="{{route('institution.dashboard', $institution->id)}}">Dashboard</a></li>
		<li class="breadcrumb-item">Exams</li>
	</ul>
</div>
@include('common.message')
<div class="tile">
    <div class="tile-header clearfix mb-3">
    	<a href="{{route('institution.event.create', $institution->id)}}" class="btn btn-primary pull-right"><i class="fa fa-plus"></i> New</a>
    </div>
    <div class="tile-body">
    	<table class="table table-hover table-bordered" id="data-table" >
    		<thead>
    			<tr>
    				<th>S/No</th>
    				<th>Title</th>
    				<th>Description</th>
    				<th>Duration</th>
    				<th><i class="fa fa-bars p-2"></i></th>
    			</tr>
    		</thead>
    		<?php $i = 0; ?>
			@foreach($allRecords as $record)
			<?php 
			 $eventSubjects = $record['eventSubjects'];
			 $subjects = '';
			 foreach ($eventSubjects as $subject) {
			     $subjects .= $subject['course_code'] . ',';
			 }
			 $subjects = rtrim($subjects, ',');
			 $dur = \App\Core\Settings::splitTime($record['duration']);
			 $i++;
			?>
				<tr title="Subjects: [{{$subjects}}]" >
					<td>{{$i}}</td>
					<td>{{$record['title']}}</td>
					<td>{{$record['description']}}</td>
					<td><?= "{$dur['hours']}hrs, {$dur['minutes']}mins, {$dur['seconds']}secs" ?></td>
					<td>
						<i class="fa fa-bars p-2 pointer"
						   tabindex="0"
						   role="button" 
                           data-html="true" 
                           data-toggle="popover" 
                           title="Options" 
                           data-placement="left"
                           data-content="<div>
                            <div><small><i class='fa fa-eye'></i> 
                            	<a href='{{route('institution.event.show', [$institution->id, $record['id']])}}' class='btn btn-link'>Preview</a>
                            </small></div>
                            
                            <div><small><i class='fa fa-graduation-cap'></i> 
                            	<a href='{{route('institution.exam.index', [$institution->id, $record['id']])}}' class='btn btn-link'>Exams</a>
                            </small></div>
                            
                            <div><small><i class='fa fa-edit'></i> 
                            	<a href='{{route('institution.exam.create', [$institution->id])}}' class='btn btn-link'>Register Students for Exam</a>
                            </small></div>
                            
                            <div><small><i class='fa fa-edit'></i> 
                            	<a href='{{route('institution.event.edit', [$institution->id, $record['id']])}}' class='btn btn-link'>Edit</a>
                            </small></div>
                            
                            <div><small><i class='fa fa-chart-bar'></i> 
                            	<a class='btn btn-link' href='{{route('institution.event.result', [$institution->id, $record['id']])}}' >View Result</a>
                            </small></div>
                            
                            <div><small><i class='fa fa-trash'></i> 
                            	<?php //$deleteRoute = route('institution.event.destroy', [$institution->id, $record['id']])?>
                            	{{--@include('common._delete_form')--}}
                            	<a onclick='return confirmAction()' href='{{route('institution.event.destroy', [$institution->id, $record['id']])}}' class='btn btn-link text-danger'>Delete</a>
                        	</small></div>
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
$(function () {
//   $('[data-toggle="popover"]').popover();
  var popOverSettings = {
//	 	    placement: 'bottom',
//	 	    container: 'body',
//	 	    html: true,
		    selector: '[data-toggle="popover"]', //Sepcify the selector here
//	 	    content: function () {
//	 	        return $('#popover-content').html();
//	 	    }
	}
	
	$('#data-table').popover(popOverSettings);
});
function confirmAction() {
	return confirm('{{$confirmMsg}}');
}
</script>

@stop
