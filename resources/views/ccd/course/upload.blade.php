<?php
$title = 'Upload content';
?>

@extends('ccd.layout')

@section('content')

	 <div class="templatemo-content-widget white-bg">
		 <header class="text-center">
			<h2>Upload Content</h2><hr />
		 </header>
		<form action="{{route('ccd.course.upload.store', [$institution->id, $course->id])}}" method="post" enctype="multipart/form-data">			
			@csrf
			<div class="form-group">
				Upload <strong>{{$course->course_code}}</strong> content
			</div>
			<div class="form-group">
				<label for="" >Content File (zip)</label><br />
				<input type="file" class="form-control" name="content" value="" />
			</div>
			<br />
			<div class="form-group">
				<input type="submit" value="submit" class="templatemo-blue-button width-20" /><br /><br />
			</div>
		</form>
	</div>
			
			
@stop