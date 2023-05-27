<?php
$title = "Admin - Profile | " . SITE_TITLE;
$page = 'manage';
$subCat = 'users'
?>
@extends('admin.layout')

@section('dashboard_content')

	<div>
		<ol class="breadcrumb">
			<li><a href="{{getAddr('admin_dashboard')}}"><i class="fa fa-dashboard"></i> Dashboard</a></li>
			<li><a href="{{getAddr('admin_view_all_users')}}"><i class="fa fa-users"></i> Users</a></li>
			<li class="active"><i class="fa fa-user"></i> Profile</li>
		</ol>
	</div>
	 
	<fieldset>
		<legend>Transactions</legend>
		<a href="<?= getAddr('admin_user_withdrawals', $userData[USERNAME])?>" class="btn btn-success btn-md">Withdrawals</a>
	</fieldset>
	<br />
	
	<div>
		<a href="<?= getAddr('admin_delete_user', $userData[TABLE_ID]) ?>" class="btn btn-danger"
			style='margin-left:5px' onclick="return confirm('Are you sure?')" 
			><i class='fa fa-trash fa-fw'></i> Delete</a>
			
		<a href="{{getAddr('admin_send_sms', $userData[PHONE_NO])}}" class="btn btn-success" 
			title="Send SMS to this user" >
			<i class='fa fa-edit'></i> Send SMS</a>
		<a href="{{getAddr('admin_send_notification', $userData[USERNAME])}}" class="btn btn-success" 
			title="Send Notification message to this user">
			<i class='fa fa-edit'></i> Send Notification</a>
	</div>
	
	@include('common.user_profile')
		

	
	

@stop