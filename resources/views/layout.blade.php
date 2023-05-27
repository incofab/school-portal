<?php
$title = isset($title) ? $title : SITE_TITLE;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" href="{{assets('favicon.ico')}}" >
    <title>{{$title}}</title>
    <!-- Bootstrap core CSS -->
    <link href="{{assets('lib/bootstrap4/css/bootstrap.min.css')}}" rel="stylesheet">
    <!-- Custom Fonts -->
    <link href="{{ assets('lib/font-awesome-4.6.3/css/font-awesome.min.css') }}" rel="stylesheet">
    <!-- jQuery -->
    <script type="text/javascript" src="{{ assets('lib/jquery.min.js')}}"></script>
    @yield('meta')
</head>
<style>
.pointer{cursor: pointer;}
</style>
<body class="app sidebar-mini rtl">

	@yield('body')
	
	<script src="{{assets('lib/popper.min.js')}}"></script>
	<script type="text/javascript" src="{{assets('lib/bootstrap4/js/bootstrap.min.js')}}"></script>
	<!-- The javascript plugin to display page loading on top-->
	<script src="{{assets('lib/pace/pace.min.js')}}"></script>
	@yield('scripts')
</body>
</html>
