<?php

if(!isset($count)) return;

$__addr = Arr::get(parse_url($_SERVER['REQUEST_URI']), 'path') . '?';

foreach ($_GET as $key => $value) 
{
    if($key == 'page') continue;
    
    $__addr .= "$key=$value&";
}

$isPageEnd = ($count/$numPerPage) <= $page;

$toPaginate = $count > $numPerPage;
?>

@if($toPaginate)
<div class="px-3 my-2 clearfix">
	@if($page > 1)
	<a href="{{$__addr . 'page=' . ($page - 1) }}" class="pull-left paginate paginate-previous">&laquo; Previous</a>
	@endif
	@if(!$isPageEnd)
	<a href="{{$__addr . 'page=' . ($page + 1) }}" class="pull-right paginate paginate-next">Next &raquo;</a>
	@endif
</div>
@endif



