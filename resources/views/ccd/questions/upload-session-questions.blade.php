@extends('ccd.layout')

@section('dashboard_content')

<div>
	@include('ccd._breadcrumb', ['headerTitle' => 'Upload Session Questions'])
	<div class="justify-content-center">
    	<div class="tile">
			<div class="tile-title">Upload Session Questions</div>
			<div>
				Before uploading, make sure the excel questions are arranged along the following columns
				<div><b>A</b> => Questions No</div>
				<div><b>B</b> => Questions</div>
				<div><b>C</b> => Option A</div>
				<div><b>D</b> => Option B</div>
				<div><b>E</b> => Option C</div>
				<div><b>F</b> => Option D</div>
				<div><b>G</b> => Option E</div>
				<div><b>H</b> => Answer</div>
				<br>
				And the first entry should be on row 2
				<br>
				<small><i>Note: Formulars and formatted content may not appear as expected</i></small>
			</div>
			<hr>
			<form method="POST" action="{{instRoute('questions.upload.store', $courseSession)}}"
				enctype="multipart/form-data" >
				@include('common.form_message')
				@csrf
				<div><b>Title: </b> <span>{{$courseable->getName()}}</span></div>
				{{-- <div class="mt-1"><b>Session: </b> <span>{{$courseable->session}}</span></div> --}}
				<br>
				<div class="form-group">
					<label for="" >Question Content</label><br />
					<input type="file" class="form-control" name="file" value=""/>
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