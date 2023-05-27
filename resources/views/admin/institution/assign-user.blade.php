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
		<p>Add User to Institution</p>
	</div>
	<ul class="app-breadcrumb breadcrumb">
		<li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i> <a href="{{route('admin.dashboard')}}">Dashboard</a></li>
		<li class="breadcrumb-item"><a href="{{route('admin.institution.index')}}">Institution</a></li>
		<li class="breadcrumb-item">Institution User</li>
	</ul>
</div>
@include('common.message')
<div>
	<div class="tile">
		<h3 class="tile-title">Add Institution User</h3>
		<form action="{{route('admin.institution.assign-user', $data->id)}}" method="post">
			@csrf
    		<div class="tile-body">
    			<div>
    				Add a user to this institution <strong>{{$data->name}}</strong>
    			</div>
    			<br />
				<div class="form-group">
					<label class="control-label">Username</label> 
					<input type="text" id="" name="username" value="{{old('username')}}" 
						placeholder="Username, phone or email" class="form-control" >
				</div>
    		</div>
    		<div class="tile-footer">
    			<button class="btn btn-primary" type="submit">
    				<i class="fa fa-fw fa-lg fa-check-circle"></i>Add Now
    			</button>
    		</div>
		</form>
	</div>

</div>

@endsection