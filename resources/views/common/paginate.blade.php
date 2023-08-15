<?php
use Illuminate\Support\Arr;

if (!isset($paginatedData)) {
  return;
}

$prevPageUrl = null;
$nextPageUrl = null;
$urlParams = [];

$query = Arr::get(parse_url($_SERVER['REQUEST_URI']), 'query', '');
parse_str($query, $urlParams);
$currentUrl = url()->current(); //

if (!$paginatedData->onFirstPage()) {
  $urlParams['page'] = $paginatedData->currentPage() - 1;
  $prevPageUrl = $currentUrl . '?' . http_build_query($urlParams);
}

if ($paginatedData->hasMorePages()) {
  $urlParams['page'] = $paginatedData->currentPage() + 1;
  $nextPageUrl = $currentUrl . '?' . http_build_query($urlParams);
}
?>

<div class="px-3 my-2 clearfix">
	@if($prevPageUrl)
	<a href="{{$prevPageUrl}}" 
		class="float-start float-left paginate paginate-previous">&laquo; Previous</a>
	@endif
	@if($nextPageUrl)
	<a href="{{$nextPageUrl}}" 
		class="float-end float-right paginate paginate-next">Next &raquo;</a>
	@endif
</div>
