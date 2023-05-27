<?php
$title = "Admin - Users | " . SITE_TITLE;



?>
@extends('admin.layout')

@section('dashboard_content')

<div class="app-title">
	<div>
		<h1>
			<i class="fa fa-dashboard"></i> Users
		</h1>
		<p>List of all Users</p>
	</div>
	<ul class="app-breadcrumb breadcrumb">
		<li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i> <a href="{{route('admin.dashboard')}}">Dashboard</a></li>
		<li class="breadcrumb-item">Users</li>
	</ul>
</div>
@include('common.message')
<div class="tile">
    <div class="tile-header clearfix mb-3">
    	<form action="{{route('admin.user.search')}}" method="get">
    		<div class="form-group">
    			<div class="input-group">
    				<input type="text" name="search_user" class="form-control" placeholder="Search by Username, Name, Email, Phone..." >
    				<span class="input-group-btn" style="padding: 0;">
    					<button type="submit" class="btn btn-success" >
    						<i class="fa fa-search fa-fw"></i> search
    					</button>
    				</span>
    				
    			</div>
    		</div>
    	</form>
    </div>
	<div class="tile-body">
		<table class="table table-hover table-striped">
			<tr>
				<th>name</th>
				<th>Username</th>
				<th>Phone</th>
				<th>Email</th>
				<th></th>
			</tr>
			@foreach($allRecords as $record)
				<tr>
					<td>{{$record['name']}}</td>
					<td>{{$record['username']}}</td>
					<td>{{$record['phone']}}</td>
					<td>{{$record['email']}}</td>
				</tr>
			@endforeach
		</table>
	</div>
</div>
	

@stop
