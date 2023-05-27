<?php
$title = "Exam Result | " . SITE_TITLE;
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
		<form class="login-form" action="{{route('home.exam.view-result')}}" method="get">
			<h3 class="login-head">
				<i class="fa fa-lg fa-fw fa-certificate"></i> 
				View My Result
			</h3>
        	<br />
			<div class="form-group">
				<label class="control-label">Exam Number</label> 
				<input class="form-control" type="text" placeholder="Exam No" name="exam_no">
			</div>
			<br />
			<div class="form-group btn-container">
				<button class="btn btn-primary btn-block">
					<i class="fa fa-sign-in fa-lg fa-fw"></i>View Result
				</button>
			</div>
		</form>
	</div>
</section>

<script type="text/javascript">

</script>

@endsection
