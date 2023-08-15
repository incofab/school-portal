<?php
$title = 'Admin - Topics'; ?>
@extends('ccd.layout')

@section('dashboard_content')

	<div >
		@include('ccd._breadcrumb', [
			'headerTitle' => 'List Topics',
			'crumbs' => [
				breadCrumb('Topics')->active()
			]
		])
	</div>

	<div class="tile">
		<div class="tile-title clearfix">
			<div class="float-left">
				List Topics {{$course ? "for {$course->}" : ''}}
			</div>
			<a href="{{instRoute('topics.create', [$course])}}" class="btn btn-success float-right" >
				<i class="fa fa-plus"></i> New
			</a>
		</div>
		<table class="table table-striped">
			<tr>
				<th>Title</th>
				<th>Description</th>
				<th></th>
			</tr>
			@foreach($allRecords as $record)
				<tr>
					<td>{{$record['title']}}</td>
					<td>{{$record['description']}}</td>
					<td>
						<a href="{{instRoute('topics.edit', $record['id'])}}" 
							class="btn btn-sm btn-primary"> <i class="fa fa-edit"></i> Edit</a>
						<a href="{{instRoute('topics.destroy', $record['id'])}}" 
							onclick="return confirm('Are you sure?')"
							class="btn btn-sm btn-danger"> <i class="fa fa-trash"></i> Delete</a>
					</td>
				</tr>
			@endforeach
		</table>
	</div>
	

@stop
