<?php


?>

@extends('ccd.layout')

@section('content')

	 <div class="templatemo-content-widget white-bg">
	 
		 <header class="text-center">
			<h2>Register Course</h2><hr />
		 </header>

		<form action="{{route('ccd.course.store', $institution->id)}}" method="post" >
			@csrf
			<div class="form-group">
				<label>Course Code</label><br />
				<input type="text" name="course_code" value="{{old('course_code')}}"  class="form-control" />
			</div>
			<div class="form-group">
				<label for="" >Course Fullname</label><br />
				<input type="text" name="course_title" value="{{old('course_title')}}" class="form-control"/>
			</div>
			<div class="form-group">
				<label for="" >Description</label><br />
				<textarea name="description" id="" cols="60" rows="6" class="useEditor form-control">{{old('description')}}</textarea>
			</div>
			<br />
			<div class="form-group">
				<input type="submit" value="submit" class="templatemo-blue-button width-20" />
			</div>
			<br /><br />
					
		</form>
	</div>
			
			
@stop