<?php
$title = "Login Student for Exam | " . SITE_TITLE;
?>

@extends('vali_layout') 

@section('body')

<section class="material-half-bg">
	<div class="cover"></div>
</section>
<section class="login-content">
	<div class="logo">
		<h1>{{SITE_TITLE}}</h1>
	</div>
	<div class="login-box">
		<form class="login-form" action="" method="get">
			<h3 class="login-head">
                <small>
                    <small>
                    	<i class="fa fa-lg fa-fw fa-graduation-cap"></i> &nbsp;&nbsp;
                    </small>
                    Exam Page - {{date('Y')}} 
                </small>
			</h3>
        	<br /><br />
			@include('common.message')
			<div class="form-group">
				<label class="control-label">Exam Number</label> 
				<input class="form-control" type="text" placeholder="Exam No" name="exam_no">
			</div>
			<br />
			<div class="form-group btn-container">
				<button class="btn btn-primary btn-block">
					<i class="fa fa-sign-in fa-lg fa-fw"></i>Start Exam
				</button>
			</div>
		</form>
	</div>
</section>

@endsection
