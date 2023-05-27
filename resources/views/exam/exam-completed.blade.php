@extends('vali_layout')

@section('body')

<section class="material-half-bg">
	<div class="cover"></div>
</section>
<section class="login-content">
	<div class="logo">
		<h1><?php echo SITE_TITLE ?></h1>
	</div>
	<div class="login-box">
		<form class="login-form" action="" method="get" autocomplete="off">
			<h3 class="login-head text-center">
				<div>
            		<i class="fa fa-lg fa-fw fa-graduation-cap"></i>
				</div>
				<div class="text-center">
					Congratulations!!!
				</div>
			</h3>
        	<br />
			<div class="text-center px-3 h5 font-weight-normal">
				You have successfully completed this test.
				<br />
				<br />
				You will be informed of your results later.
				<br />
				<a href="{{route('home.exam.start')}}" class="btn btn-primary mt-3">Start Another Exam</a>
			</div>
			<br />
		</form>
	</div>
</section>
    
@endsection