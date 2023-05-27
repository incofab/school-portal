<?php
$dashboardAddr = isset($dashboardAddr) ? $dashboardAddr : route('institution.dashboard', $institution->id);
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">  
    <title>Questions and Answers - Home</title> 
    <meta name="description" content="">
    <meta name="author" content="templatemo">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" integrity="sha512-1ycn6IcaQQ40/MKBW2W4Rhis/DbILU74C1vSrLJxCq57o941Ym01SwNsOMqvEBFlcgUa6xLiPY/NS5R+E6ztJQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- Bootstrap core CSS -->
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.1/css/bootstrap.min.css" integrity="sha512-T584yQ/tdRR5QwOpfvDfVQUidzfgc2339Lc8uBDtcp/wYu80d7jwBgAxbyMh0a9YM9F8N3tdErpFI8iaGx6x5g==" crossorigin="anonymous" referrerpolicy="no-referrer" />
<!--     <link href="{{assets('css/lib/bootstrap3.min.css')}}" rel="stylesheet"> -->
    <link href="{{assets('lib/templatemo/templatemo-style.css')}}" rel="stylesheet">
<!--     <link href="{{assets('css/style.css')}}" rel="stylesheet"> -->
<!--     <link href="{{assets('lib/toastr/toastr.min.css')}}" rel="stylesheet"> -->
    
    {{-- jQuery is loaded here because a most other libraries depend on it --}}
    <script src="https://code.jquery.com/jquery-3.5.1.min.js" integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
  
<style>
.w-25{ width: 25%; }
.w-50{ width: 50%; }
.w-75{ width: 75%; }
.w-100{ width: 100%; }
.pointer{ cursor: pointer; }
.cursor-default{ cursor: default; }

#loading{
    position: fixed;
    top: 0; bottom: 0; left: 0; right: 0;
    background-color: #333333aa;
    width: 100%;
    height: 100%;
    display: none;
}
#loading img{
    position: absolute;
    left: 50%; top: 50%;
    transform: translate(-50%, -50%);
}
* {
  padding: 0;
  margin: 0;
  line-height: normal;
}
.container {
  padding-right: 0;
  padding-left: 0;
  width: 100%;
}
.text-body, .text-body *{
	line-height: 1.7em;
    font-size: 16px;	
}
#answer-explanation, #answer-explanation *{color: #333 !important; line-height: 2.37rem !important;}
/* .question-text, .question-text *{ line-height: 2.67rem !important; } */
.question-container .text, .question-container .text *{ line-height: 2.67rem !important; }
#paginate{padding-left: 10px;}
#paginate li>a.current_page{
    z-index: 2;
    color: #23527c;
    font-weight: bold;
    background-color: #eee;    
    border-color: #ddd;
}
#paginate > li > a{
    border-radius: 5px;
    padding-left: 15px;
    padding-right: 15px;
}
a.templatemo-blue-button{
    display: inline-block !important;
    margin: 3px 5px;
}
</style>
</head>
<body>  
	<div class="templatemo-content-container">
		<div class="row" style="margin: 0; padding: 3px;">
    		<div class="col-xs-12 col-md-6" style="padding: 5px;">
    			<a href="<?= route('ccd.course.index', $institution->id) ?>" class="templatemo-blue-button width-20"
    				title="Shows all the courses Already recorded in the system" >
    				<i class="fa fa-home"></i>
    				<span>Home</span>					
    			 </a>
    			<a href="{{$dashboardAddr}}" class="templatemo-blue-button width-20" >
    				<i class="fa fa-dashboard"></i> <span>Back to Dashboard</span>					
    			 </a>
    		</div>
    		@if (isset($courseId))
    			<div class="col-xs-12 col-md-6" style="padding: 5px;">
    				<div class="clearfix">
        				<a href="<?= route('ccd.session.index', [$institution->id, $courseId]) ?>" 
        					class="templatemo-blue-button width-20 pull-right"
        					title="Shows all the the examination years recorded for the {{ empty($courseName) ? $courseId : $courseName }}" >
        						<span>Academic Sessions</span>					
        				 </a>
        				<a href=""
        					class="templatemo-blue-button width-20 pull-right d-none"
        					title="Shows the course summary recorded for {{ empty($courseName) ? $courseId : $courseName }}" >
        						<span>Course Summary</span>					
        				 </a>
    				</div>
        		 </div>
    		@endif
    	</div>
    	<br />
    	<div class="templatemo-content-widget white-bg">
    		  @include('common.message')
              @yield('content')
         </div>
	</div>

	<div id="loading">
		<img src="{{assets('img/images/ajax_loader_orange_128.gif')}}" alt="" />
	</div>
<!-- JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.1/js/bootstrap.bundle.min.js" integrity="sha512-mULnawDVcCnsk9a4aG1QLZZ6rcce/jSzEGqUkeOLy0b6q0+T6syHrxlsAGH7ZVoqC93Pd0lBqd6WguPWih7VHA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<!-- Templatemo Script -->
<script type="text/javascript" src="{{assets('lib/templatemo/templatemo-script.js')}}"></script>
<!-- <script type="text/javascript" src="{{assets('js/lib/handlebars-v4.0.10.js')}}"></script> -->
<!-- <script type="text/javascript" src="{{assets('lib/toastr/toastr.min.js')}}"></script> -->
<script type="text/javascript">
var addr = '<?= route('home').'/' ?>';
function showLoading() {
	$('#loading').show();
}
function dismissLoading() {
	$('#loading').hide();
}
</script>
<script type="text/javascript" src="{{assets('js/myfunctions.js')}}"></script>
</body>
</html>