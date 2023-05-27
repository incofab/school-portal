<?php 
$deleteRoute = $deleteRoute ?? '';
$btnClasses = $btnClasses ?? 'btn btn-link btn-sm text-danger';
?>
<form action="{{$deleteRoute}}" method="post" class="d-inline-block">
	@csrf
	@method('DELETE')
	<button type="submit" class="{{$btnClasses}}" onclick="return confirm('Do you want to delete this?')">Delete</button>
</form>