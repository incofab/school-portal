@extends('ccd.layout')

@section('dashboard_content')

<div>
	@include('ccd._breadcrumb', [
		'headerTitle' => 'Passages',
		'crumbs' => [
			breadCrumb('Sessions', instRoute('course-sessions.index', [$courseSession->course_id])),
			breadCrumb('Questions')->active()
		]
	])
	<div>
		<div class="tile full p-0">
			<div class="tile-title p-2">
				<div class="clearfix">
					<div class="float-left">All Questions for {{$courseSession->course->code}} {{$courseSession->session}}</div>
					<a href="{{instRoute('questions.create', [$courseSession])}}" class="btn btn-success float-right" >
						<i class="fa fa-plus"></i> New
					</a>
				</div>
			</div>
		</div>
		<div id="questions-container">
		@foreach ($allRecords as $record)
			<div class="tile full mt-1 questions">
				<div class="row">
					<div class="col-8">
						<div class="row">
							<div class="col-md-3">
								<div><b>No: {{$record->question_no}}</b></div>
							</div>
							@if ($record->topic)
								<div class="col-md-9">
									<div><b>Topic: {{$record->topic?->title}}</b></div>
								</div>
							@endif
						</div>
						<div>{!!$record->question!!}</div>
						<hr class="my-1">
						<div class="row options">
							<div class="col-md-6"><b>A)</b> <span class="option-text">{!!$record->option_a!!}</span></div>
							<div class="col-md-6"><b>B)</b> <span class="option-text">{!!$record->option_b!!}</span></div>
							<div class="col-md-6"><b>C)</b> <span class="option-text">{!!$record->option_c!!}</span></div>
							<div class="col-md-6"><b>D)</b> <span class="option-text">{!!$record->option_d!!}</span></div>
							@if ($record->option_e)
								<div class="col-md-6"><b>E)</b> <span class="option-text">{!!$record->option_e!!}</span></div>
							@endif
						</div>
						<hr class="my-1">
						<div class="my-2"><b>Answer: {!!$record->answer!!}</b></div>
						@if ($record->answer_meta)
							<div class="my-2">
								<div>Explanation</div>
								<div>{!!$record->answer_meta!!}</div>
							</div>
						@endif
						<div>
							<a href="{{instRoute('questions.edit', [$record])}}" class="btn btn-sm btn-primary">Edit</a>
							<a href="{{instRoute('questions.destroy', [$record])}}" 
							onclick="return confirm('Are you sure?')"
							class="btn btn-sm btn-danger">Delete</a>
						</div>
					</div>
					{{-- 
					@if ($record->questionCorrection)
					<div class="col-4"
						style="background-color: {{($record->questionCorrection?->answer == $record->answer) ? '#d4fbdd' : '#fad2d6'}}">
						<div class="p-2">
							<div>
								<b>Correction:</b> {{$record->questionCorrection?->answer}} 
							</div>
							<div class="my-2"><b>Explanation:</b> {{$record->questionCorrection?->explanation}}</div>
							<div><b>Comment:</b> {{$record->questionCorrection?->comment}}</div>
						</div>
						<br>
						@if (!$record->questionCorrection->is_resolved)
							<a href="{{instRoute('question-corrections.mark-as-resolved', $record->questionCorrection->id)}}"
								class="btn btn-sm btn-success">
								Mark as Resolved
							</a>
						@else
						<div class="p-2 text-primary">
							<b>RESOLVED</b>
						</div>
						@endif
					</div>
					@endif
					 --}}
				</div>
			</div>
		@endforeach
		</div>
	</div>
</div>

@include('ccd.questions._handle_image_base_url_script', ['courseSession' => $courseSession])

<script>
function handleImages() {
	$('#questions-container').find('.questions img').each(function(i, e) {
		var $img = $(e);
		var src = $img.attr('src');
		var alt = $img.attr('alt');
		$img.attr('src', getImageBaseUrl(src, alt));
	});
}
handleImages();
</script>
<style>
.options > *{
	margin-top: 7px;
	margin-bottom: 4px;
}
.option-text{
	display: inline-block;
	margin-left: 5px;
}
</style>
@endsection