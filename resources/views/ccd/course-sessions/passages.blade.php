@extends('ccd.layout')

@section('dashboard_content')

<div>
	@include('ccd._breadcrumb', [
		'headerTitle' => 'Passages',
		'crumbs' => [
			// breadCrumb('Sessions', instRoute('course-sessions.index', [$courseSession->course_id])),
			breadCrumb('Passages')->active()
		]
	])
	<div>
		<div class="justify-content-center">
			<div class="tile">
				<div class="tile-title">{{$edit ? 'Update' : 'Create'}} Passage</div>
				<form 
					method="POST"
					action="{{$edit ? instRoute('passages.update', [$edit]) : instRoute('passages.store', [$courseable->getMorphedId()])}}" 
					name="register" >
					@include('common.form_message')
					@csrf
					@if ($edit)
						@method('PUT')
					@endif
					<div class="font-weight-bold">
						<div>
							<span>Title: </span>
							<span class="ml-2">{{$courseable->getName()}}</span>
						</div>
					</div>
					<hr class="my-3">
					
					<div class="form-group">
						<label for="" >Passage</label>
						<textarea name="passage" id="" rows="4" class="form-control" 
							><?= old('passage', $edit?->passage) ?></textarea>
					</div>
					<div class="row">
						<div class="col-6">
							<div class="form-group">
								<label for="" >From</label><br />
								<input type="number" name="from" value="{{old('from', $edit?->from)}}"  class="form-control" />
							</div>
						</div>
						<div class="col-6">
							<div class="form-group">
								<label for="" >To</label><br />
								<input type="number" name="to" value="{{old('to', $edit?->to)}}"  class="form-control" />
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
			<div><strong class="text-md">All Passages</strong></div>
			@foreach ($allRecords as $record)
				<div class="tile full mt-1">
					<div>{{$record->passage}}</div>
					<div>From: {{$record->from}} - To: {{$record->to}}</div>
					<hr class="my-1">
					<div>
						<a href="{{instRoute('passages.index', [$courseable->getMorphedId(), $record])}}" class="btn btn-sm btn-primary">Edit</a>
						<a href="{{instRoute('passages.destroy', [$record])}}" 
						onclick="return confirm('Are you sure?')"
						class="btn btn-sm btn-danger">Delete</a>
					</div>
				</div>
			@endforeach
		</div>
	</div>
</div>

@endsection