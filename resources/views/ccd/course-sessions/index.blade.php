@extends('ccd.layout')

@section('dashboard_content')
@include('ccd._breadcrumb', ['headerTitle' => 'All Sessions', 
'crumbs' => [
	breadCrumb('Subjects', instRoute('courses.index', [$course?->exam_content_id])),
	breadCrumb('Sessions')->active()
]])
	<div class="tile full">
		<div class="tile-title">
			<div>
				<div class="float-left">Sessions {{$course ? ('for '.($course->code ?? $course->title)):''}}</div>
				@if (!empty($course))
					<a href="{{instRoute('course-sessions.create', [$course])}}" class="btn btn-success float-right" >
						<i class="fa fa-plus"></i> New
					</a>
				@endif
				<div class="clearfix"></div>
			</div>
		</div>
		<div class="table-responsive">
			<table class="table table-striped">
				<tr>
					<th>Subject</th>
					<th>Session</th>
					<th>Category</th>
					<th>General Instrunction</th>
					<th>Questions</th>
					<th></th>
				</tr>
				@foreach($allRecords as $record)
				<tr>
					<td>{{$record->course->code}}</td>
					<td>{{$record['session']}}</td>
					<td>{{$record->category}}</td>
					<td>{{$record->general_instructions}}</td>
					<td>{{$record->questions_count}}</td>
					<td>
						<a href="{{instRoute('questions.index', $record)}}" 
							class="btn btn-sm btn-link"> Questions </a>
						<a href="{{instRoute('passages.index', $record)}}" 
							class="btn btn-sm btn-link"> Passages </a>
						<a href="{{instRoute('instructions.index', $record)}}" 
							class="btn btn-sm btn-link"> Instructions </a>

						<a href="{{instRoute('questions.upload.create', $record)}}" 
							class="btn btn-sm btn-primary"> <i class="fa fa-upload"></i> </a>

						<a href="{{instRoute('course-sessions.edit', $record)}}" 
							class="btn btn-sm btn-success"> <i class="fa fa-edit"></i> </a>

						<a href="{{instRoute('course-sessions.destroy', $record)}}" 
							onclick="return confirm('Are you sure?')"
							class="btn btn-sm btn-danger"> <i class="fa fa-trash"></i> </a>
					</td>
				</tr>
				@endforeach
			</table>
		</div>
	</div>
@stop
