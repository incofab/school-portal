@extends('ccd.layout')

@section('dashboard_content')

<div>
	@include('ccd._breadcrumb', [
		'headerTitle' => 'Passages',
		'crumbs' => [
			breadCrumb('Sessions', instRoute('course-sessions.index', [$courseSession->course_id])),
			breadCrumb('Questions', instRoute('questions.index', [$courseSession->id])),
			breadCrumb('Create Question')->active()
		]
	])
	<div class="justify-content-center">
		<div class="tile">
			<div class="tile-title">{{$edit ? 'Update' : 'Create'}} Question</div>
			<form method="POST" action="{{$edit ? instRoute('questions.update', [$edit]) : instRoute('questions.store', [$courseSession])}}"
				name="record-question"
			>
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
					<div class="mt-2">
						<span>Session: </span>
						<span class="ml-2">{{$courseSession->session}}</span>
					</div>
				</div>
				<hr class="my-3">
				
				<div class="form-group">
					<label for="" >Question No</label>
					<input type="number" name="question_no" value="{{$questionNo}}"  
						readonly="{{$edit ? false : true}}"
						class="form-control" style="max-width: 80px" />
				</div>
				
				<div class="form-group">
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
				</div>

				<div class="form-group">
					<label for="" >Question</label>
					<textarea name="question" id="" rows="4" class="form-control useEditor" 
						><?= old('question', $edit?->question) ?></textarea>
				</div>

				<div class="row">
					<div class="col-6">
						<div class="form-group">
							<label for="" >Option A</label>
							<textarea name="option_a" rows="3" class="form-control useEditor" 
								>{{old('option_a', $edit?->option_a)}}</textarea>
						</div>
					</div>
					<div class="col-6">
						<div class="form-group">
							<label for="" >Option B</label>
							<textarea name="option_b" rows="3" class="form-control useEditor" 
								>{{old('option_b', $edit?->option_b)}}</textarea>
						</div>
					</div>
					<div class="col-6">
						<div class="form-group">
							<label for="" >Option C</label>
							<textarea name="option_c" rows="3" class="form-control useEditor" 
								>{{old('option_c', $edit?->option_c)}}</textarea>
						</div>
					</div>
					<div class="col-6">
						<div class="form-group">
							<label for="" >Option D</label>
							<textarea name="option_d" rows="3" class="form-control useEditor" 
								>{{old('option_d', $edit?->option_d)}}</textarea>
						</div>
					</div>
					<div class="col-6">
						<div class="form-group">
							<label for="" >Option E</label>
							<textarea name="option_e" rows="3" class="form-control useEditor" 
								>{{old('option_e', $edit?->option_e)}}</textarea>
						</div>
					</div>
				</div>
				
				<div class="form-group mt-2">
					<div><label>Answer</label></div>
					<div class="form-check-inline px-3 py-1 rounded" style="background: rgba(0, 0, 0, 0.2)">
						<label class="form-check-label">
							<input type="radio" class="form-check-input" name="answer" 
								@checked(old('answer', $edit?->answer) === 'A')
								value="A"> A
						</label>
					</div>
					<div class="form-check-inline ml-2 px-3 py-1 rounded" style="background: rgba(0, 0, 0, 0.2)">
						<label class="form-check-label">
							<input type="radio" class="form-check-input" name="answer" 
							@checked(old('answer', $edit?->answer) === 'B')
								value="B"> B
						</label>
					</div>
					<div class="form-check-inline ml-2 px-3 py-1 rounded" style="background: rgba(0, 0, 0, 0.2)">
						<label class="form-check-label">
							<input type="radio" class="form-check-input" name="answer" 
							@checked(old('answer', $edit?->answer) === 'C')
								value="C"> C
						</label>
					</div>
					<div class="form-check-inline ml-2 px-3 py-1 rounded" style="background: rgba(0, 0, 0, 0.2)">
						<label class="form-check-label">
							<input type="radio" class="form-check-input" name="answer" 
							@checked(old('answer', $edit?->answer) === 'D')
								value="D"> D
						</label>
					</div>
					<div class="form-check-inline ml-2 px-3 py-1 rounded" style="background: rgba(0, 0, 0, 0.2)">
						<label class="form-check-label">
							<input type="radio" class="form-check-input" name="answer" 
							@checked(old('answer', $edit?->answer) === 'E')
								value="E"> E
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
							class="btn btn-primary btn-block" value="{{empty($edit) ? 'Add' : 'Update'}}">
					<div class="clearfix"></div>
				</div>
			</form>
		</div>
	</div>
</div>
<style>
	.form-check-inline, .form-check-inline > *{
		cursor: pointer;
	}
</style>
@include('ccd.questions._handle_image_base_url_script', ['courseSession' => $courseSession])
<script>
	function handleImages() {
		$('form[name="record-question"]').find('.useEditor').each(function(i, ele) {
			var $ele = $(ele);
			var varStr = $ele.val();
			var $parsedHtml = $($.parseHTML(`<div>${varStr}</div>`));
			$parsedHtml.find('img').each(function(j, e) {
				var $img = $(e);
				var src = $img.attr('src');
				var alt = $img.attr('alt');
				$img.attr('src', getImageBaseUrl(src, alt));
			});
			$ele.val($parsedHtml.html());
		});
	}
</script>

@include('ccd._question_tinymce', [
	'uploadURL' => instRoute('api.questions.image-upload', $courseSession),
	'imagePath' => asset(config('app.image-content-folder')."{$courseSession->course_id}/{$courseSession->id}")
])

@if(!isset($edit))
	<script src="{{asset('js/add-question.js')}}"></script>
	<script>
		var addQuestionAPI = "{{instRoute('api.questions.store', $courseSession)}}";
		var currentQuestionNo = {{$questionNo}};
	</script>
@endif

@endsection