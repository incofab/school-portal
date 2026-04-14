@extends('ccd.layout')

@section('dashboard_content')

<div>
	@include('ccd._breadcrumb', [
		'headerTitle' => 'Theory Questions',
		'crumbs' => [
			breadCrumb('Sessions', instRoute('course-sessions.index', [$courseSession->course_id])),
			breadCrumb('Theory Questions')->active()
		]
	])
	<div>
		<div class="tile full p-0">
			<div class="tile-title p-2">
				<div class="clearfix">
					<div class="float-left">All Theory Questions for {{$courseSession->getName()}}</div>
					<a href="{{instRoute('theory-questions.create', [$courseSession])}}" class="btn btn-success float-right" >
						<i class="fa fa-plus"></i> New
					</a>
				</div>
			</div>
		</div>
		<div id="theory-questions-container">
		@foreach ($allRecords as $record)
			<div class="tile full mt-1 theory-questions">
				<div class="row">
					<div class="col-12">
						<div class="row">
							<div class="col-md-3">
								<div><b>No: {{$record->question_number}}{{$record->question_sub_number ? $record->question_sub_number : ''}}</b></div>
							</div>
							<div class="col-md-3">
								<div><b>Marks: {{$record->marks}}</b></div>
							</div>
						</div>
						<div>{!!$record->question!!}</div>
						<hr class="my-1">
						<div class="my-2">
							<div><b>Answer</b></div>
							<div>{!!$record->answer!!}</div>
						</div>
						@if ($record->marking_scheme)
							<div class="my-2">
								<div><b>Marking Scheme</b></div>
								<div>{!!$record->marking_scheme!!}</div>
							</div>
						@endif
						<div>
							<a href="{{instRoute('theory-questions.edit', [$record])}}" class="btn btn-sm btn-primary">Edit</a>
							<a href="{{instRoute('theory-questions.destroy', [$record])}}" 
							onclick="return confirm('Are you sure?')"
							class="btn btn-sm btn-danger">Delete</a>
						</div>
					</div>
				</div>
			</div>
		@endforeach
		</div>
		@include('common.paginate', ['paginatedData' => $allRecords])
	</div>
</div>

@endsection
