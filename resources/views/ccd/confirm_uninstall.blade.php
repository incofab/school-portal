<?php
$donFlashMessages = true;
$title = 'Admin - Confirm Uninstall | ' . config('app.name');

// $errors = isset($errors) ? $errors : [];
// $post = isset($post) ? $post : [];
// $valErrors = \Session::getFlash('val_errors', []);
?>

@extends('ccd.layout')

@section('dashboard_content')

<div class="tile w-75 mx-auto">
	<div class="tile-body">
		<h2 class="text-center color_primary">Confirm Uninstall</h2>
		<div class="alert alert-warning">
			NOTE: Uninstalling {{$courseCode}} will remove all files related to this subject including any changes made to it.
			<br /><br />
			This operation will take some time and should not be interrupted
		</div>
		<form method="POST" action="" name="register" >
			@include('common.form_message')
			<div class="form-group">
				<label for="">Password</label>
				<input type="password" id="" name="<?= PASSWORD ?>" value="" 
					placeholder="Your password" class="form-control" >
			</div>
			<div class="form-group mt-4">
    			<input type="hidden" name="<?= CSRF_TOKEN ?>" value="<?= \Session::getCsrfValue() ?>" />
    			<input type="submit"  name="add" style="width: 60%; margin: auto;" 
    					class="btn btn-primary btn-block" value="Uninstall Now">
    			<div class="clearfix"></div>
			</div>
		</form>
	</div>
</div>

@endsection