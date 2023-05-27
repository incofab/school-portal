<?php
$title = "Institution - All Classes | " . SITE_TITLE;
$confirmMsg = 'Are you sure?';
?>
@extends('institution.layout')

@section('dashboard_content')

<div class="app-title">
	<div>
		<h1>
			<i class="fa fa-dashboard"></i> Student Classes
		</h1>
		<p>List of all student classes in this Institution</p>
	</div>
	<ul class="app-breadcrumb breadcrumb">
		<li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i> <a href="{{route('institution.dashboard', $institution->id)}}">Dashboard</a></li>
		<li class="breadcrumb-item">Student Classes</li>
	</ul>
</div>
@include('common.message')
<div class="tile" id="all-students">
    <div class="tile-header clearfix mb-3">
    	<a href="{{route('institution.grade.create', $institution->id)}}" class="btn btn-primary float-right"><i class="fa fa-plus"></i> New</a>
    </div>
    <div class="tile-body">
    	<table class="table table-hover table-bordered" id="data-table" >
    		<thead>
    			<tr>
    				<th>S/No</th>
    				<th>Title</th>
    				<th>Description</th>
    				<th><i class="fa fa-bars p-2"></i></th>
    				<th>Exam</th>
    			</tr>
    		</thead>
    		<?php $i = 0; ?>
			@foreach($allRecords as $record)
    		<?php $i++; ?>
				<tr>
					<td>{{$i}}</td>
					<td>{{$record['title']}}</td>
					<td>{{$record['description']}}</td>
					<td>
						<a href="{{route('institution.grade.edit', [$institution->id, $record->id])}}" class="btn btn-primary btn-sm">
							<i class="fa fa-edit"></i> Edit</a>
						<a href="{{route('institution.student.index', [$institution->id, $record->id])}}" class="btn btn-warning btn-sm">
							<i class="fa fa-users"></i> View Student</a>
						<?php $deleteRoute = route('institution.grade.destroy', [$institution->id, $record->id]); $btnClasses = 'btn btn-danger btn-sm' ?>
						@include('common._delete_form')
					</td>
					<td>
						<a href="{{route('institution.exam.grade.create', [$institution->id, $record->id])}}" 
							class="btn btn-info btn-sm"><i class="fa fa-graduation-cap"></i> Register Exam</a>
					</td>
				</tr>
			@endforeach
		</table>
	</div>
	<div class="tile-footer">
		@include('common.paginate')
	</div>
</div>

@stop
