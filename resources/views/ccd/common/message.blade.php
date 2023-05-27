<?php

/**
 * Used to include neutral messages into another page 
 */

$error = isset($error) ? $error : \Session::getFlash('error');
$report = isset($report) ? $report : \Session::getFlash('report');
$success = isset($success) ? $success : \Session::getFlash('success');

?>
	
	@if(!empty($error))
			<p class="error"><?= $error ?></p>
	@endif
	@if(!empty($report))
			<p class="report"><?= $report ?></p>
	@endif
	@if(!empty($success))
			<p class="success"><?= $success ?></p>
	@endif
	
