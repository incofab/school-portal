<?php
$title = "Admin - Add Administrative user | " . SITE_TITLE;

?>

@extends('admin.layout')

@section('dashboard_content')

<div id="register">
	<div class="page-display">
		<h2 class="text-center color_primary">New Admin User</h2>
		<form method="POST" action="" name="register" >
			@if($valErrors)
			<div class="alert alert-danger">
				@foreach($valErrors as $vError)
					<p><i class="fa fa-star" style="color: #cc4141;"></i> {{implode('<br />', $vError)}}</p>
				@endforeach
			</div>
			@endif
			<div class="form-group">
				<label for="">Username</label>
				<input type="text" id="" name="<?= USERNAME ?>" value="<?= getValue($post, USERNAME)  ?>" 
					placeholder="Username" class="form-control">
			</div>
			<div class="form-group">
				<label for="">Email</label>
				<input type="text" id="" name="<?= EMAIL ?>" value="<?= getValue($post, EMAIL)  ?>" 
					placeholder="Email" class="form-control">
			</div>
			@if(empty($edit))
			<div class="form-group">
				<label for="">Access Level</label>
				<select name="<?= LEVEL ?>" id="" class="form-control" required="required">
					<option value="">select</option>
					<option value="5" <?= markSelected('5', array_get($post, LEVEL))?> >5</option>
					<option value="6" <?= markSelected('6', array_get($post, LEVEL))?> >6</option>
					<option value="7" <?= markSelected('7', array_get($post, LEVEL))?> >7</option>
					<option value="8" <?= markSelected('8', array_get($post, LEVEL))?> >8</option>
					<option value="9" <?= markSelected('9', array_get($post, LEVEL))?> >9</option>
					<option value="10" <?= markSelected('10', array_get($post, LEVEL))?> >10</option>
				</select>
			</div>
			@endif
	
			<div class="form-group">
    			<input type="hidden" name="<?= CSRF_TOKEN ?>" value="<?= \Session::getCsrfValue() ?>" />
    			<input type="submit"  name="add" style="width: 60%; margin: auto;" 
    					class="btn btn-primary btn-block" value="{{empty($edit) ? 'Add' : 'Update'}}">
    			<div class="clearfix"></div>
			</div>
		</form>
	</div>
</div>

@endsection