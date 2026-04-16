@extends('ccd.layout')

@section('dashboard_content')

<div>
	@include('ccd._breadcrumb', [
		'headerTitle' => 'Theory Questions',
		'crumbs' => [
			breadCrumb('Theory Questions', instRoute('theory-questions.index', [$courseable->getMorphedId()])),
			breadCrumb(($edit ? 'Update' : 'Create') . ' Theory Question')->active()
		]
	])
	<div class="justify-content-center">
		<div class="tile">
			<div class="tile-title">{{$edit ? 'Update' : 'Create'}} Theory Question</div>
			<form method="POST" action="{{$edit ? instRoute('theory-questions.update', [$edit]) : instRoute('theory-questions.store', [$courseable->getMorphedId()])}}">
				@include('common.form_message')
				@csrf
				@if ($edit)
					@method('PUT')
				@endif
				<div class="font-weight-bold">
					<div>
						<span>Course: </span>
						<span class="ml-2">{{$courseable->getName()}}</span>
					</div>
				</div>
				<hr class="my-3">

				<div class="row">
					<div class="col-md-3">
						<div class="form-group">
							<label for="" >Question No</label>
							<input type="number" name="question_no" value="{{old('question_no', $questionNumber)}}" class="form-control" />
						</div>
					</div>
					<div class="col-md-3">
						<div class="form-group">
							<label for="" >Question Sub No</label>
							<input type="text" name="question_sub_number" value="{{old('question_sub_number', $edit?->question_sub_number)}}" class="form-control" placeholder="Eg. a" />
						</div>
					</div>
					<div class="col-md-3">
						<div class="form-group">
							<label for="" >Marks</label>
							<input type="number" step="1" min="1" name="marks" value="{{old('marks', $edit?->marks ?? 1)}}" class="form-control" />
						</div>
					</div>
				</div>

				<div class="form-group">
					<label for="" >Question</label>
					<textarea name="question" rows="4" class="form-control useEditor"><?= old(
       'question',
       $edit?->question
     ) ?></textarea>
				</div>

				<div class="form-group">
					<label for="" >Answer</label>
					<textarea name="answer" rows="4" class="form-control useEditor"><?= old(
       'answer',
       $edit?->answer
     ) ?></textarea>
				</div>

				<div class="form-group">
					<label for="" >Marking Scheme</label>
					<textarea name="marking_scheme" rows="4" class="form-control useEditor"><?= old(
       'marking_scheme',
       $edit?->marking_scheme
     ) ?></textarea>
				</div>

				<div class="form-group">
					<input type="submit" name="add" style="width: 60%; margin: auto;" 
							class="btn btn-primary btn-block" value="Submit">
					<div class="clearfix"></div>
				</div>
			</form>
		</div>
	</div>
</div>
@include('common._tinymce')

@endsection
