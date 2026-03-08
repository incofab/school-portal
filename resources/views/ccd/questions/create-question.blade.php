@extends('ccd.layout')

@section('dashboard_content')

<div>
	@include('ccd._breadcrumb', [
		'headerTitle' => 'Passages',
		'crumbs' => [
			// breadCrumb('Sessions', instRoute('course-sessions.index', [$courseSession->course_id])),
			breadCrumb('Questions', instRoute('questions.index', [$courseable->getMorphedId()])),
			breadCrumb('Create Question')->active()
		]
	])
	<div class="justify-content-center">
		<div class="tile">
			<div class="tile-title">{{$edit ? 'Update' : 'Create'}} Question</div>
			<form method="POST" action="{{$edit ? instRoute('questions.update', [$edit]) : instRoute('questions.store', [$courseable->getMorphedId()])}}"
				name="record-question"
				enctype="multipart/form-data"
			>
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
				
				<div class="form-group">
					<label for="" >Question No</label>
					<input type="number" name="question_no" value="{{$questionNo}}"  
						{{$edit ? '' : 'readonly'}}
						class="form-control" style="max-width: 80px" />
				</div>
				
				{{-- <div class="form-group">
					<label for="" >Topic</label><br />
					<select name="topic_id" id="" class="form-control">
						<option value="">Topic</option>
						@foreach($topics as $topic)
						<option value="{{$topic['id']}}" title="{{$topic['description']}}"
							@selected($topic->id == old('topic_id', $edit?->topic_id))
							>
							{{$topic->title}}
						</option>
						@endforeach
					</select>
				</div> --}}

				<div class="form-group">
					<label for="" >Question</label>
					<textarea name="question" id="" rows="4" class="form-control useEditor" 
						><?= old('question', $edit?->question) ?></textarea>
				</div>

				<div class="row">
					<div class="col-md-6">
						<div class="form-group">
							<label for="" class="mb-0">Option A</label>
							<textarea name="option_a" rows="3" class="form-control useEditor" 
								>{{old('option_a', $edit?->option_a)}}</textarea>
						</div>
					</div>
					<div class="col-md-6">
						<div class="form-group">
							<label for="" class="mb-0" >Option B</label>
							<textarea name="option_b" rows="3" class="form-control useEditor" 
								>{{old('option_b', $edit?->option_b)}}</textarea>
						</div>
					</div>
					<div class="col-md-6">
						<div class="form-group">
							<label for="" class="mb-0" >Option C</label>
							<textarea name="option_c" rows="3" class="form-control useEditor" 
								>{{old('option_c', $edit?->option_c)}}</textarea>
						</div>
					</div>
					<div class="col-md-6">
						<div class="form-group">
							<label for="" class="mb-0" >Option D</label>
							<textarea name="option_d" rows="3" class="form-control useEditor" 
								>{{old('option_d', $edit?->option_d)}}</textarea>
						</div>
					</div>
					<div class="col-md-6">
						<div class="form-group">
							<label for="" class="mb-0" >Option E</label>
							<textarea name="option_e" rows="3" class="form-control useEditor" 
								>{{old('option_e', $edit?->option_e)}}</textarea>
						</div>
					</div>
				</div>
				
				<div class="form-group mt-2">
					<div><label>Answer</label></div>
					<div class="form-check-inline my-1 rounded" style="background: rgba(0, 0, 0, 0.2)">
						<label class="form-check-label px-3 py-2" for="answer_a">
							<input type="radio" class="form-check-input" name="answer" 
								@checked(old('answer', $edit?->answer) === 'A')
								value="A" id="answer_a"> A
						</label>
					</div>
					<div class="form-check-inline mx-2 my-1 rounded" style="background: rgba(0, 0, 0, 0.2)">
						<label class="form-check-label px-3 py-2 " for="answer_b">
							<input type="radio" class="form-check-input" name="answer" 
							@checked(old('answer', $edit?->answer) === 'B')
								value="B" id="answer_b"> B
						</label>
					</div>
					<div class="form-check-inline mx-2 my-1 rounded" style="background: rgba(0, 0, 0, 0.2)">
						<label class="form-check-label px-3 py-2" for="answer_c">
							<input type="radio" class="form-check-input" name="answer" 
							@checked(old('answer', $edit?->answer) === 'C')
								value="C" id="answer_c"> C
						</label>
					</div>
					<div class="form-check-inline mx-2 my-1 rounded" style="background: rgba(0, 0, 0, 0.2)">
						<label class="form-check-label px-3 py-2" for="answer_d">
							<input type="radio" class="form-check-input" name="answer" 
							@checked(old('answer', $edit?->answer) === 'D')
								value="D" id="answer_d"> D
						</label>
					</div>
					<div class="form-check-inline my-1 rounded" style="background: rgba(0, 0, 0, 0.2)">
						<label class="form-check-label px-3 py-2" for="answer_e">
							<input type="radio" class="form-check-input" name="answer" 
							@checked(old('answer', $edit?->answer) === 'E')
								value="E" id="answer_e"> E
						</label>
					</div>
				</div>

				<div class="form-group">
					<label for="" >Answer Explanation</label>
					<textarea name="answer_meta" id="" rows="4" class="form-control useEditor" 
						><?= old('answer_meta', $edit?->answer_meta) ?></textarea>
				</div>

				<div class="form-group">
					<input type="submit" name="add" style="width: 60%; margin: auto;" 
							class="btn btn-primary btn-block" value="Submit">
					<div class="clearfix"></div>
				</div>
				<input type="file" name="question_payload" class="d-none" />
			</form>
		</div>
	</div>
</div>
<style>
	.form-check-inline, .form-check-inline > *{
		cursor: pointer;
	}
</style>
@include('common._tinymce')
<script>
	(function () {
		var form = document.querySelector('form[name="record-question"]');
		if (!form || !window.File || !window.Blob || !window.DataTransfer) {
			return;
		}
		var payloadInput = form.querySelector('input[name="question_payload"]');
		if (!payloadInput) {
			return;
		}

		form.addEventListener('submit', function () {
			if (window.tinymce) {
				window.tinymce.triggerSave();
			}
			var payload = {};
			var fields = form.querySelectorAll('[name]');
			fields.forEach(function (field) {
				if (field.name === 'question_payload' || field.name === '_token' || field.name === '_method') {
					return;
				}
				if (field.type === 'radio') {
					if (!field.checked) {
						return;
					}
				}
				if (field.type === 'file') {
					return;
				}
				payload[field.name] = field.value;
			});
			var blob = new Blob([JSON.stringify(payload)], {type: 'text/plain'});
			var fileName = 'question-' + Date.now() + '.txt';
			var file = new File([blob], fileName, {type: 'text/plain'});
			var dataTransfer = new DataTransfer();
			dataTransfer.items.add(file);
			payloadInput.files = dataTransfer.files;

			fields.forEach(function (field) {
				if (field.name === 'question_payload' || field.name === '_token' || field.name === '_method') {
					return;
				}
				field.setAttribute('data-original-name', field.name);
				field.removeAttribute('name');
			});
		});
	})();
</script>
{{-- 
<script src="https://cdn.tiny.cloud/1/x5fywb7rhiv5vwkhx145opfx4rsh70ytqkiq2mizrg73qwc2/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
<script>
tinymce.init({
	selector: '.useEditor',
	plugins: 'image code charmap',

	charmap_append: [
		[0x2600, 'sun'],
		[0x20A6, 'naira'],
		[0x2601, 'cloud']
	],

	// enable title field in the Image dialog
	image_title: true, 

	toolbar: 'undo redo | link image | code | bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,',
});
</script> --}}

@endsection
