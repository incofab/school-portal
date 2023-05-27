<?php

use Illuminate\Support\Arr;

// $instruction = \App\Helpers\FormatExam::getInstruction($allInstructions, $questionObj['question_no']);

// $passage = \App\Helpers\FormatExam::getPassage($allPassages, $questionObj['question_no']);

// $topic = $questionObj['topic'];

$attemptResult = 'not-attempted';
$answer = $questionObj->answer;
$questionAttempt = '';

if(!empty($attempts[$questionObj->id]['attempt']))
{
    $questionAttempt = $attempts[$questionObj->id]['attempt'];
    
    if ($questionAttempt == $answer) $attemptResult = 'correct';
    else $attemptResult = 'wrong';

//     dlog('$attemptResult = '.$attemptResult.', $questionAttempt = '.$questionAttempt.', $questionObj->answer '.  $questionObj->answer.
//         ', QuestionNo = '.$questionObj->question_no.', CourseName = '.$courseName);
}

?>

 <div class="p-2 p-md-3 white-bg question-container mt-2 card shadow rounded {{$attemptResult}}" >
{{-- 
 	@if($instruction)
 	<div>
		<label for="" ><b>Instruction: </b></label> 
		<b>Questions {{$instruction['from_']}} - {{$instruction['to_']}}</b>
		<div class="text">{!! $instruction['instruction'] !!}</div>
	</div>
	<br />
	@endif
	
 	@if($passage)
 	<div>
		<label for="" ><b>Passage: </b></label>
		<b>Questions {{$passage['from_']}} - {{$passage['to_']}}</b>
		<div class="text">{!! $passage['passage'] !!}</div>
	</div>
	<br />
	@endif
--}}
	<div>
		<div class="clearfix">
			<div class="pull-left">No: {{ $questionObj['question_no'] }} </div>
			@if(!empty($topic['title']))
			<div class="pull-right"><strong>Topic: {!!$topic['title']!!}</strong></div>
			@endif
		</div>
		<div class="question-text text mt-2">{!! $questionObj['question'] !!}</div>
	</div>
 	<br />
	<div class="row">
		<div class="col-md-6 clearfix">
			<label class="float-left mr-2 {{($questionAttempt == 'A')?'user-choice':''}}" for="" >(A) </label>
			<div class="text float-left">{!! $questionObj['option_a'] !!}</div>
		</div>
		<div class="col-md-6 clearfix">
			<label class="float-left mr-2 {{($questionAttempt == 'B')?'user-choice':''}}"  for="" >(B) </label>
			<div class="text float-left">{!! $questionObj['option_b'] !!}</div>
		</div>
	</div> 
	<div class="row">
		<div class="col-md-6 clearfix">
			<label class="float-left mr-2 {{($questionAttempt == 'C')?'user-choice':''}}"  for="" >(C) </label>
			<div class="text float-left">{!! $questionObj['option_c'] !!}</div>
		</div>
		<div class="col-md-6 clearfix">
			<label class="float-left mr-2 {{($questionAttempt == 'D')?'user-choice':''}}"  for="" >(D) </label>
			<div class="text float-left">{!! $questionObj['option_d'] !!}</div>
		</div>
	</div> 
	@if(!empty($questionObj['option_e']))
	<div class="row">
		<div class="col-md-6 clearfix">
			<label class="float-left mr-2 {{($questionAttempt == 'E')?'user-choice':''}}" for="" >(E) </label>
			<div class="text float-left">{!! $questionObj['option_e'] !!}</div>
		</div>
	</div>
	@endif
	<br />
	<div>
		<div style="margin-bottom: 10px;">
			<label for="">Answer: </label> <span>{{ $questionObj['answer'] }}</span> 
		</div>
		@if(!empty($questionObj['answer_meta']))
		<div id="answer-explanation">
			<label for=""><u>Explanation:</u></label> <span>{!! $questionObj['answer_meta'] !!}</span>
		</div>
		@endif
	</div> 
	<?php /*
	{{--
	<div style="margin-top: 20px;">
		<a href="<?= getAddr('ccd_edit_session_question', [$courseId, $year_id, $questionObj['id']])."?next=$next" ?>"
			class="templatemo-blue-button width-20" ><span>Edit</span></a>
		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		<a href="<?= getAddr('ccd_delete_session_question', [$courseId, $year_id, $questionObj['id']]) ?>"
			class="templatemo-blue-button width-20" onclick="return confirm('Are you sure?')"
			 ><span>Delete</span></a>	
	</div>
	--}}
	*/ ?>
</div>

<script type="text/javascript">

</script>