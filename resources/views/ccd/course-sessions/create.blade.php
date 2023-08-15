@extends('ccd.layout')

@section('dashboard_content')

<div>
	@include('ccd._breadcrumb', ['headerTitle' => 'Add/Update Course Session'])
	<div class="justify-content-center">
    	<div class="tile">
			<div class="tile-title">{{$edit ? 'Update' : 'Create'}} Course Session</div>
			<form method="POST" action="{{$edit ? instRoute('course-sessions.update', [$edit]) : instRoute('course-sessions.store', [$course])}}" name="register" >
				@include('common.form_message')
				@csrf
				@if ($edit)
					@method('PUT')
				@endif
				<div class="font-weight-bold">
					<span>Course: </span>
					<span class="ml-2">{{$course->code}}</span>
				</div>
				<hr class="my-3">
				<div class="form-group">
					<label for="" >Course Year</label><br />
					<input type="text" name="session" value="{{old('session',$edit?->session)}}"  class="form-control" />
				</div>
				
				<div class="form-group">
					<label for="" >Category</label><br />
					<input type="text" name="category" value="{{old('category', $edit?->category)}}" class="form-control"/>
				</div>
				
				<div class="form-group">
					<label for="" >General Instructions</label>
					<textarea name="general_instructions" id="" rows="4" class="form-control" 
						><?= old('general_instructions', $edit?->general_instructions) ?></textarea>
				</div>
				
				<div class="form-group">
					<input type="submit" name="add" style="width: 60%; margin: auto;" 
							class="btn btn-primary btn-block" value="{{empty($edit) ? 'Add' : 'Update'}}">
					<div class="clearfix"></div>
				</div>
			</form>
		</div>
	</div>
</div>

@endsection