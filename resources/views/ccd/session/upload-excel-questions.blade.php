<?php
$title = 'Upload Question content';
?>

@extends('ccd.layout')

@section('content')

	 <div class="templatemo-content-widget white-bg">
		 <header class="text-center">
			<h2>Upload  Content</h2><hr />
		 </header>
		<form action="" method="post" enctype="multipart/form-data">			
			@csrf
			<div class="form-group">
				Upload Questions for 
				<strong>{{$courseSession->course->course_code}}, {{$courseSession->session}}</strong> session
			</div>
			<br />
			<div class="clearfix">
				<a href="{{assets('question-recording-template.xlsx', true)}}" class="btn btn-primary float-right">
					<i class="fa fa-download"></i> Download Template
				</a>
			</div>
			<br />
			<div class="form-group">
				<label for="" >Question Content (Excel)</label><br />
				<input type="file" class="form-control" name="content" value="" />
			</div>
			<br />
			<div class="form-group">
				<input type="submit" value="submit" class="templatemo-blue-button width-20" /><br /><br />
			</div>
		</form>
	</div>
			
			
@stop