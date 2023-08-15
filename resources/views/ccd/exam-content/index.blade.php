<?php
$title = 'Admin - All Exam Content'; ?>
@extends('ccd.layout')

@section('dashboard_content')

	<div class="app-title">
    	<div >
    		<ol class="breadcrumb">
    			<li><a href="{{instRoute('dashboard')}}"><i class="fa fa-dashboard"></i> Dashboard</a></li>
    			<li class="active ml-2"><i class="fa fa-users"></i> Exam Content</li>
    		</ol>
    		<h4 class="">Available Exam Content/Body</h4>
    	</div>
	</div>
	<div class="row justify-content-center">
    	<div class="tile" style="width: 100%">
    		<a href="<?= route(
        'exam-contents.create'
      ) ?>" class="btn btn-success float-right mb-2" >
    				<i class="fa fa-plus"></i> <span>Register New</span>					
    		 </a>
    		<h2 class="tile-title">Available Exam Content</h2>
			<div class="table-responsive">
    		<table class="table table-striped table-bordered">
        			<tr>
        				<th>Institution</th>
        				<th>Exam Body/Name</th>
        				<th>Exam fullname</th>
        				<th>Courses</th>
        				<th></th>
        			</tr>
        			@foreach($allRecords as $record)
        				<tr>
        					<td>{{$record['institution']}}</td>
        					<td>{{$record['exam_name']}}</td>
        					<td>{{$record['fullname']}}</td>
        					<td>
        						<a href="{{instRoute('courses.index', $record['id'])}}" 
        							class="btn-link"> View Courses</a>
        					</td>
        					<td>
        						<a href="{{instRoute('exam-contents.edit', $record['id'])}}" 
        							class="btn btn-sm btn-danger"> <i class="fa fa-edit"></i> Edit</a>
        						<a href="{{instRoute('exam-contents.destroy', $record['id'])}}" 
        							onclick="return confirm('Delete? Be careful because this will delete all courses and sessions under it')"
        							class="btn btn-sm btn-danger"> <i class="fa fa-trash"></i> Delete</a>
        						@if(\App\Support\OutputJson::isOutputJsonAvailable($record))
        						<a href="{{instRoute('exam-contents.delete-json', [$record['id']])}}" 
        							onclick="return confirm('Delete already compiled json?')"
        							class="btn btn-sm btn-warning mr-2"> <i class="fa fa-download"></i> Delete JSON </a>
        						@else
        						<a href="{{instRoute('exam-contents.compile-json', $record['id'])}}" 
        							onclick="return confirm('Compile and output json?')"
        							class="btn btn-sm btn-warning mr-2"> <i class="fa fa-download"></i> Compile to JSON </a>
        						@endif
        					</td>
        				</tr>
        			@endforeach
        		</table>
			</div>
    	</div>	
	</div>
	

@stop
