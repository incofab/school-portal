<?php

use Illuminate\Support\Facades\Session;
$donFlashMessages = true;
$Ierror = $Ierror ?? Session::get('error');
$Isuccess = $Isuccess ?? Session::get('success', Session::get('message'));

/** @var Illuminate\Support\ViewErrorBag $errors */
?>
<script type="text/javascript">
var donFlashMessages = true;
</script>
<?php if ($errors->any()): ?>
<div class="alert alert-danger rounded-0">
    {!! implode('', $errors->all('<p class="m-0 py-1"><span class="red">*</span> :message</p>')) !!}
</div>
<?php return;endif; ?>
@if($Ierror)
    <div class="alert alert-danger rounded-0">
        <p class="m-0 py-1"><i class="fa fa-star red"></i> {{$Ierror}}</p>
    </div>
@endif

@if($Isuccess)
    <div class="alert alert-success rounded-0">
        <p class="m-0 py-1"><i class="fa fa-check"></i> {{$Isuccess}}</p>
    </div>
@endif