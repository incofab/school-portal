@php
$crumbs = $crumbs ?? [];
@endphp
<div class="app-title">
	<div  style="width: 100%">
		<ol class="breadcrumb">
			<li><a href="{{instRoute('dashboard', currentInstitution())}}"><i class="fa fa-dashboard"></i> Dashboard</a></li>
			<?php
/** @var App/DTO/BreadCrumb $crumb */
?>
			@foreach ($crumbs as $crumb)
				<li class="{{$crumb->activeClass()}} ml-2">
					<span>/</span>
					@if (empty($crumb->route))
						<i class="fa {{$crumb->icon}}"></i> {{$crumb->title}}
					@else
						<a href="{{$crumb->route}}">
							<i class="fa {{$crumb->icon}}"></i> {{$crumb->title}}
						</a>
					@endif
				</li>
			@endforeach
		</ol>
		<h4 class="">{{$headerTitle}}</h4>
	</div>
</div>