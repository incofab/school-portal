<?php
$title = "Admin - Edit Institution | " . SITE_TITLE;
?>

@extends('admin.layout')

@section('dashboard_content')

<div class="app-title">
	<div>
		<h1>
			<i class="fa fa-dashboard"></i> Institution
		</h1>
		<p>Update an Institution</p>
	</div>
	<ul class="app-breadcrumb breadcrumb">
		<li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i> <a href="{{route('admin.dashboard')}}">Dashboard</a></li>
		<li class="breadcrumb-item"><a href="{{route('admin.institution.index')}}">Institution</a></li>
		<li class="breadcrumb-item">Edit</li>
	</ul>
</div>
@include('common.message')
<div>
	<div class="tile">
		<h3 class="tile-title">Edit Institution</h3>
		<form action="{{route('admin.institution.update', $data->id)}}" method="post">
			@csrf
			@method('PUT')
    		<div class="tile-body">
				<div class="form-group">
					<label class="control-label">Institution Name</label> 
					<input type="text" id="" name="name" value="{{old('name', $data->name)}}" 
						placeholder="Name of the Institution" class="form-control" >
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
						placeholder="Address of the Institution">{{old('address', $data->address)}}</textarea>
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