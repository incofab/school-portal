
@extends('ccd.layout')

@section('content')

<div class="templatemo-content-widget white-bg">
 	<div class="panel-heading templatemo-position-relative">
 		<h2>{{ $course->course_code }} questions for the session {{ $session->session }}</h2>
 	</div>
	@foreach($allCourseYearQuestions as $question)
		@include('ccd.common.preview_single_question')
	@endforeach
</div>

<script type="text/javascript">
var imgBaseDir = '<?= route('home').IMG_OUTPUT_PUBLIC_PATH; ?>';
$(function(){
	$('.question-container').find('img').each(function(i, ele) {
		var $img = $(ele);
    	var src = $img.attr('src');
//     	var alt = $img.attr('alt');
//     	var imgPath = imgBaseDir+'{{$course->id}}/{{$session->session}}/'+src;
//     	$img.attr('src', imgPath);
    	$img.attr('src', getImageAddr('{{$course->id}}', '{{$session->id}}', src, '{{$session->session}}'));
	});
});

</script>
@stop