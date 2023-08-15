<?php
$title = 'Admin - Change Password'; ?>
@extends('ccd.layout')

@section('dashboard_content')

<style>
    #changepassword{
        background-color: #fff;
        border-radius: 5px;
        padding: 15px;
        border: 1px solid #c5c5c5;
    }
    label{
        font-weight: normal;
    }
</style>
<div id="changepassword-cover">
	<div class="row" style="margin: 0;" >
	<br /><br />
		<div class="col-sm-6 col-sm-offset-3 col-md-6 col-md-offset-3" id="changepassword" >
			<h3 class="text-center">Change Password</h3>
			<form method="POST" action="" name="changepassword">
				<br />
				<div class="form-group" >
					<label for="">Old Password</label>
					<input type="password" name="password" 
						placeholder="Old password" required class="form-control" >
				</div>
				<div class="form-group" >
					<label for="">New Password</label>
					<input type="Password" name="new_password" placeholder="New password" 
						required="required" class="form-control" />
				</div>
				<div class="form-group" >
					<label for="">Confirm Password</label>
					<input type="Password" name="new_password_confirmation" placeholder="Confirm password" 
						required="required" class="form-control" />
				</div>
				<div class="form-group" >
    				<input type="hidden" name="login" value="true" />
    				<input type="submit"  name="changepassword" class="btn btn-primary float-right" value="Change Password">
				</div>
			</form>
			<br /><br />
		</div>
	</div>
	<br /><br />
</div>

@endsection