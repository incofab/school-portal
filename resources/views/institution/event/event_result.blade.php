<?php
$title = "Exam Center - Events Results | " . SITE_TITLE;
?>
@extends('institution.layout')

@section('dashboard_content')

<div class="app-title">
	<div>
		<h1>
			<i class="fa fa-dashboard"></i> Events
		</h1>
		<p>{{$event['title']}} Results</p>
	</div>
	<ul class="app-breadcrumb breadcrumb">
		<li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i> <a href="{{route('institution.dashboard', $institution->id)}}">Dashboard</a></li>
		<li class="breadcrumb-item">Event Results</li>
	</ul>
</div>
	
<div class="tile">
	<div class="tile-header clearfix mb-3">
    	<a href="{{route('institution.event.result-download', [$institution->id, $event['id']])}}" 
    	onclick="return confirm('Download result as Spreadsheet now?')"
    	class="btn btn-primary pull-right"><i class="fa fa-download"></i> Download As Spreadsheet</a>
    </div>
    <div class="tile-body">
    	<table class="table table-hover table-bordered" id="data-table" >
    		<thead>
    			<tr>
    				<th>S/No</th>
    				<th>Name</th>
    				<th>Student ID</th>
    				<th>Exam No</th>
    				<th>Subjects</th>
    				<th>Correct Answers</th>
    				<th>Score</th>
    			</tr>
    		</thead>
    		<?php $i = 0; ?>
			@foreach($allRecords as $record)
			<?php
			 $i++; 
			?>
			<tr>
				<td>{{$i}}</td>
				<td>{{$record['name']}}</td>
				<td>{{$record['student_id']}}</td>
				<td>{{$record['exam_no']}}</td>
				<td>{{$record['subjects']}}</td>
				<td>{{$record['total_score']}}/{{$record['total_num_of_questions']}}</td>
				<td>{{$record['total_score_percent']}}/{{$record['total_num_of_questions_percent']}}</td>
			</tr>
			@endforeach
		</table>
	</div>
	<div class="tile-footer">
		@include('common.paginate')
	</div>
</div>


@stop
