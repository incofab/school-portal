<?php
$title = 'View Single Result | ' . SITE_TITLE; ?>

@extends('vali_layout') 

@section('body')
<style>
body{background: #eeeeee; }
#result{background: #fff; }
hr.line{    
    margin-top: 0.5rem;
    margin-bottom: 0.5rem;
}
</style>
<div id="result" class="container-fluid card mx-auto w-75 mt-2">
	<h4 class="title text-center mt-2 py-3 bg-light">{{$institution->name}}</h4>
	<hr class="line" />
	<h5 class="title mt-1" align="right"><i>{{$event->title}}</i></h5>
	<hr class="line" />
	<h6 class="title mt-3 bg-info p-1"><i>Personal Details</i></h6>
	<hr class="line" />
	<div class="user-details mb-2">
		<dl class="row">
			<dt class="col-3 col-sm-2 mb-3">Fullname</dt>
			<dd class="col-9 col-sm-10 mb-3">{{$student['firstname']}} {{$student['lastname']}}</dd>
			<dt class="col-3 col-sm-2 mb-3">Student ID</dt>
			<dd class="col-9 col-sm-10 mb-3">{{$student['student_id']}}</dd>
			<dt class="col-3 col-sm-2 mb-3">Exam No</dt>
			<dd class="col-9 col-sm-10 mb-3">{{$exam['exam_no']}}</dd>
			<dt class="col-3 col-sm-2 mb-3">Subjects</dt>
			<dd class="col-9 col-sm-10 mb-3">{{implode(', ', $subjectsCourseCode)}}</dd>
			<dt class="col-3 col-sm-2 mb-3">Total Score</dt>
			<dd class="col-9 col-sm-10 mb-3">
				<?= $total_score_percent
// \App\Core\Settings::getPercentage($totalScore, $totalNumOfQuestions, 0)
?>
			</dd>
		</dl>
	</div>
	<hr class="line" />
	<h6 class="title m-0 bg-info p-1"><i>Result Details</i></h6>
	<hr class="line" />
	<div class="result-details">
		<ul class="list-group">
			@foreach($result_detail as $detail)
			<li
				class="list-group-item d-flex justify-content-between align-items-center">
				{{$detail['']}} 
				<span class="badge badge-primary badge-pill">
					{{$detail['score_percent']}}
				</span>
			</li>
			@endforeach
		</ul>
		<br />
	</div>
<br />
</div>
<br />
<br />
<!-- The javascript plugin to display page loading on top-->
<script type="text/javascript">

</script>

@endsection
