<?php
$title = "Enter Student ID | " . SITE_TITLE;
$donFlashMessages = true;
$errors = isset($errors) ? $errors : $sessionModel->getFlash('error');
$post = isset($post) ? $post : [];
$valErrors = $sessionModel->getFlash('val_errors', []);
if($valErrors) $errors = null;
?>

@extends('centers.layout')

@section('dashboard_content')

<div class="app-title">
	<div>
		<h1>
			<i class="fa fa-dashboard"></i> Exams
		</h1>
		<p>Register Student for Exam</p>
	</div>
	<ul class="app-breadcrumb breadcrumb">
		<li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i> <a href="{{getAddr('admin_dashboard')}}">Dashboard</a></li>
		<li class="breadcrumb-item"><a href="{{getAddr('center_view_all_exams')}}">Exams</a></li>
		<li class="breadcrumb-item">Enter Student ID</li>
	</ul>
</div>
<div>
	<div class="tile">
		<h3 class="tile-title">Enter Student ID</h3>
    	@if($valErrors)
    	<div class="alert alert-danger">
    		@foreach($valErrors as $vError)
    			<p><i class="fa fa-star" style="color: #cc4141;"></i> {{implode('<br />', $vError)}}</p>
    		@endforeach
    	</div>
    	@endif
    	@if($errors)
    	<div class="alert alert-danger">
   			<div><i class="fa fa-star" style="color: #cc4141;"></i> {{$errors}}</div>
    	</div>
    	@endif
		<form action="" method="get">
    		<div class="tile-body">
				<div class="form-group w-75">
					<label class="control-label">Student ID</label> 
					<input class="form-control" type="text" placeholder="Enter Student ID" name="{{STUDENT_ID}}" 
						value="<?= getValue($post, STUDENT_ID)  ?>" >
				</div>
    		</div>
    		<div class="tile-footer">
    			<button class="btn btn-primary" type="submit">
    				<i class="fa fa-fw fa-lg fa-check-circle"></i>Submit
    			</button>
    		</div>
		</form>
	</div>
</div>

@endsection