<?php
$title = "Edit Student Class - Institution | " . SITE_TITLE;
?>

@extends('institution.layout')

@section('dashboard_content')

<div class="app-title">
	<div>
		<h1>
			<i class="fa fa-dashboard"></i> Student Classes
		</h1>
		<p>Update Student Class</p>
	</div>
	<ul class="app-breadcrumb breadcrumb">
		<li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i> <a href="{{route('institution.dashboard', $institution->id)}}">Dashboard</a></li>
		<li class="breadcrumb-item"><a href="{{route('institution.grade.index', $institution->id)}}">Classes</a></li>
		<li class="breadcrumb-item">Update</li>
	</ul>
</div>
@include('common.message')
<div>
	<div class="tile">
		<h3 class="tile-title">Update Class</h3>
		<form action="{{route('institution.grade.update', [$institution->id, $data->id])}}" method="post">
    		@csrf
    		@method('PUT')
    		<div class="tile-body">
				<div class="form-group">
					<label class="control-label">Title</label> 
					<input type="text" id="" name="title" value="{{old('title', $data->title)}}" 
						placeholder="Class Name" class="form-control" >
				</div>
				<div class="form-group">
					<label class="control-label">Description</label>
					<textarea name="description" id="" rows="3" class="form-control"
					>{{old('description', $data->description)}}</textarea> 
				</div>
    		</div>
    		<div class="tile-footer">
    			<button class="btn btn-primary" type="submit">
    				<i class="fa fa-fw fa-lg fa-check-circle"></i>Update
    			</button>
    		</div>
		</form>
	</div>

</div>

@endsection