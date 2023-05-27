<?php

use Illuminate\Support\Facades\URL;

?>

@extends('ccd.layout')

@section('content')

 <div class="templatemo-content-widget white-bg ">
	 <header class="text-center">
		<h2>Preview {{ $course->course_code }} Question of {{ $session->session }}</h2><hr />
	 </header>
	@include('ccd.common.preview_single_question')
</div>

<script type="text/javascript">
var imgBaseDir = '<?= route('home').IMG_OUTPUT_PUBLIC_PATH; ?>';
$(function(){
	$('.question-container').find('img').each(function(i, ele) {
		var $img = $(ele);
    	var src = $img.attr('src');
    	var imgPath = imgBaseDir+'{{$course->id}}/{{$session->session}}/'+src;
    	
//     	$img.attr('src', imgPath);
    	$img.attr('src', getImageAddr('{{$course->id}}', '{{$session->id}}', src, '{{$session->session}}'));
	});
});

</script>
			
@stop