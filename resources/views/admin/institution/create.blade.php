<?php
$title = "Admin - Create Institution | " . SITE_TITLE;
?>

@extends('admin.layout')

@section('dashboard_content')

<div class="app-title">
	<div>
		<h1>
			<i class="fa fa-dashboard"></i> Institution
		</h1>
		<p>Register an Institution</p>
	</div>
	<ul class="app-breadcrumb breadcrumb">
		<li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i> <a href="{{route('admin.dashboard')}}">Dashboard</a></li>
		<li class="breadcrumb-item"><a href="{{route('admin.institution.index')}}">Institution</a></li>
		<li class="breadcrumb-item">Register</li>
	</ul>
</div>
<div>
	<div class="tile">
		<h3 class="tile-title">Register Institution</h3>
		<form action="{{route('admin.institution.store')}}" method="post">
			@csrf
    		<div class="tile-body">
				<div class="form-group">
					<label class="control-label">Institution Name</label> 
					<input type="text" id="" name="name" value="{{old('name')}}" 
						placeholder="Name of the Institution" class="form-control" >
				</div>
				<div class="form-group">
					<label class="control-label">Email [Optional]</label> 
					<input type="email" id="" name="email" value="{{old('email')}}" 
						placeholder="Email" class="form-control">
				</div>
				<div class="form-group">
					<label class="control-label">Phone [optional]</label> 
					<input type="text" id="" name="phone" value="{{old('phone')}}" 
						placeholder="Reachable Mobile number" class="form-control">
				</div>
				<div class="form-group">
					<label class="control-label">Address [optional]</label>
					<textarea class="form-control" rows="3" name="address"
						placeholder="Address of the Institution">{{old('address')}}</textarea>
				</div>
    		</div>
    		<div class="tile-footer">
    			<button class="btn btn-primary" type="submit">
    				<i class="fa fa-fw fa-lg fa-check-circle"></i>Register
    			</button>
    		</div>
		</form>
	</div>

</div>

@endsection