<?php
$title = "Edit Student - Institution | " . SITE_TITLE;
?>

@extends('institution.layout')

@section('dashboard_content')

<div class="app-title">
	<div>
		<h1>
			<i class="fa fa-dashboard"></i> Students
		</h1>
		<p>Update a student in this institution</p>
	</div>
	<ul class="app-breadcrumb breadcrumb">
		<li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i> <a href="{{route('institution.dashboard', $institution->id)}}">Dashboard</a></li>
		<li class="breadcrumb-item"><a href="{{route('institution.student.index', $institution->id)}}">Students</a></li>
		<li class="breadcrumb-item">Update</li>
	</ul>
</div>
@include('common.message')
<div>
	<div class="tile">
		<h3 class="tile-title">Register Student</h3>
		<form action="{{route('institution.student.update', [$institution->id, $data->id])}}" method="post">
    		@csrf
    		@method('PUT')
    		<div class="tile-body">
				<div class="form-group">
					<label class="control-label">Firstname</label> 
					<input type="text" id="" name="firstname" value="{{old('firstname', $data->firstname)}}" 
						placeholder="Firstname" class="form-control" >
				</div>
				<div class="form-group">
					<label class="control-label">Lastname</label> 
					<input type="text" id="" name="lastname" value="{{old('lastname', $data->lastname)}}" 
						placeholder="Lastname" class="form-control" >
				</div>
				<div class="form-group">
					<label class="control-label">Class</label> 
					<select name="grade_id" id="select-grade" class="form-control">
    					<option value="">Select Classs</option>
    					@foreach($allGrades as $grade)
    						<option value="{{$grade->id}}" 
    						<?= markSelected($grade->id, old('grade_id', $data->grade_id)) ?>
    						title="{{$grade->description}}" >{{$grade->title}}</option>
    					@endforeach
					</select>
				</div>
				<div class="form-group">
					<label class="control-label">Email [Optional]</label> 
					<input type="email" id="" name="email" value="{{old('email', $data->email)}}" 
						placeholder="Email" class="form-control">
				</div>
				<div class="form-group">
					<label class="control-label">Phone [optional]</label> 
					<input type="text" id="" name="phone" value="{{old('phone', $data->phone)}}" 
						placeholder="Reachable Mobile number" class="form-control">
				</div>
				<div class="form-group">
					<label class="control-label">Address [optional]</label>
					<textarea class="form-control" rows="3" name="address"
						placeholder="Address of the exam center">{{old('address', $data->address)}}</textarea>
				</div>
    		</div>
    		<div class="tile-footer">
    			<button class="btn btn-primary" type="submit">
    				<i class="fa fa-fw fa-lg fa-check-circle"></i> Update
    			</button>
    		</div>
		</form>
	</div>

</div>

@endsection