@extends('ccd.layout')

@section('dashboard_content')

<div>
	@include('ccd._breadcrumb', ['headerTitle' => 'Content Upload'])
	<div class="justify-content-center">
    	<div class="tile">
			<div class="tile-title">Upload Subject Content</div>
			<hr>
			<form method="POST" action="{{instRoute('courses.upload-content.store', $course)}}"
				enctype="multipart/form-data" >
				@include('common.form_message')
				@csrf
				<div><b>Subject: </b> <span>{{$course->code}}</span></div>
				<div class="mt-1"><b>Subject Title: </b> <span>{{$course->title}}</span></div>
				<br>
				<div class="form-group">
					<label for="" >Content</label><br />
					<input type="file" class="form-control" name="content" value=""/>
				</div>
				<br>
				<div class="form-group">
					<input type="submit" name="add" style="width: 60%; margin: auto;" 
							onclick="return confirm('Are you sure?')"
							class="btn btn-primary btn-block" value="{{'Upload'}}">
					<div class="clearfix"></div>
				</div>
			</form>
		</div>
	</div>
</div>

@endsection