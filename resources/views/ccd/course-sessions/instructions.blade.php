@extends('ccd.layout')

@section('dashboard_content')

<div>
	@include('ccd._breadcrumb', [
		'headerTitle' => 'Instructions',
		'crumbs' => [
			breadCrumb('Sessions', instRoute('course-sessions.index', [$courseSession->course_id])),
			breadCrumb('Instructions')->active()
		]
	])
	<div>
		<div class="justify-content-center">
			<div class="tile">
				<div class="tile-title">{{$edit ? 'Update' : 'Create'}} Instructions</div>
				<form method="POST" action="{{$edit ? instRoute('instructions.update', [$edit]) : instRoute('instructions.store', [$courseSession])}}" >
					@include('common.form_message')
					@csrf
					@if ($edit)
						@method('PUT')
					@endif
					<div class="font-weight-bold">
						<div>
							<span>Course: </span>
							<span class="ml-2">{{$courseSession->course->code}}</span>
						</div>
						<div class="mt-1">
							<span>Session: </span>
							<span class="ml-2">{{$courseSession->session}}</span>
						</div>
					</div>
					<hr class="my-2">
					<div class="form-group">
						<label for="" >Instruction</label>
						<textarea name="instruction" id="" rows="4" class="form-control" 
							><?= old('instruction', $edit?->instruction) ?></textarea>
					</div>
					<div class="row">
						<div class="col-6">
							<div class="form-group">
								<label for="" >From</label><br />
								<input type="number" name="from_" value="<?= old(
          'from_',
          $edit?->from_
        ) ?>"  class="form-control" />
							</div>
						</div>
						<div class="col-6">
							<div class="form-group">
								<label for="" >To</label><br />
								<input type="number" name="to_" value="<?= old(
          'to_',
          $edit?->to_
        ) ?>"  class="form-control" />
							</div>
						</div>
					</div>
					
					<div class="form-group">
						<input type="submit" name="add" style="width: 60%; margin: auto;" 
								class="btn btn-primary btn-block" value="{{empty($edit) ? 'Add' : 'Update'}}">
						<div class="clearfix"></div>
					</div>
				</form>
			</div>
		</div>
		<div>
			<div><strong class="text-md">All Instructions</strong></div>
			@foreach ($allRecords as $record)
				<div class="tile full mt-1">
					<div>{{$record->instruction}}</div>
					<div>From: {{$record->from_}} - To: {{$record->to_}}</div>
					<hr class="my-1">
					<div>
						<a href="{{instRoute('instructions.index', [$courseSession, $record])}}" class="btn btn-sm btn-primary">Edit</a>
						<a href="{{instRoute('instructions.destroy', [$record])}}" 
							onclick="return confirm('Are you sure?')"
							class="btn btn-sm btn-danger">Delete</a>
					</div>
				</div>
			@endforeach
		</div>
	</div>
</div>

@endsection