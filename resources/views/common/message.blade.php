<?php

use Illuminate\Support\Facades\Session;

if (isset($donFlashMessages)) {
  return;
}
/**
 * Used to include neutral messages into another page
 * NOTE: The function controlling this page resides in the page footer
 */

// $Ierror = isset($Ierror) ? $Ierror : \Session::getFlash('error');
// $IerrorMsg = isset($IerrorMsg) ? $IerrorMsg : \Session::getFlash('errorMsg');
// $report = isset($report) ? $report : \Session::getFlash('report');
// $Isuccess = isset($Isuccess) ? $Isuccess : \Session::getFlash('success');
// $Isuccess = !empty($Isuccess) ? $Isuccess : \Session::getFlash('message');

$Ierror = $Ierror ?? Session::get('error');
$Isuccess = $Isuccess ?? Session::get('success', Session::get('message'));

$msg_ = '';
$fa_ = '';
$class_ = '';
// $Ierror = 'Lorem ipsum dolor sit amet.';
if (!empty($Ierror)) {
  $msg_ = $Ierror;
  $class_ = 'alert-danger';
  $fa_ = 'warning';
} elseif (!empty($IerrorMsg)) {
  $msg_ = $IerrorMsg;
  $class_ = 'alert-danger';
  $fa_ = 'warning';
} elseif (!empty($report)) {
  $msg_ = $report;
  $class_ = 'alert-warning';
  $fa_ = 'exclamation';
} elseif (!empty($Isuccess)) {
  $msg_ = $Isuccess;
  $class_ = 'alert-success';
  $fa_ = 'success';
}
?>

<style>
#error-msgs{
	overflow: hidden;
	margin: auto;
	width: 80%;
	font-weight: 500;
	position: absolute;
	z-index: 5;
    left: 10%;
	@if(isset($isHome))
	margin-top: 80px;
	@endif
}
#error-msgs .alert{
    border-radius: 0;
    border-style: solid;
    border-width: 1px;
    border-left-width: 5px;
}
#error-msgs .alert.alert-success{
    border-color: #3ade60;
    border-left-color: #155724;
}
#error-msgs .alert.alert-danger{
    border-color: #f94052;
    border-left-color: #721c24;
}
#error-msgs .alert.alert-warning{
    border-color: #efb50f;
    border-left-color: #856404;
}
</style>
	
@if(!empty($msg_))	
	<div id="error-msgs" class="py-1">
		<div class="my-0 alert {{$class_}}">
			<i class="float-left fa fa-{{$fa_}}" style="color: inherit; padding: 2px 5px;"></i>
			<div class="text-center" style="color: inherit;">{{$msg_}}</div>
			<div class="clearfix"></div>
		</div>
	</div>
	<script type="text/javascript">
		setTimeout(function(){
			showErrorMsgs();
		}, 1000);
		function showErrorMsgs() {
			setTimeout(function() {
				$('#error-msgs').fadeOut('slow');
			}, 10000);
		}
// 		$('#error-msgs').on('transitionend webkitTransitionEnd MSTransitionEnd oTransitionEnd', function(e) {
// 			$('#error-msgs').hide();
// 		});
	</script>
@endif
	
	
