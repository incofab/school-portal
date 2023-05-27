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
            		<i class="fa fa-lg fa-fw fa-thumbs-up"></i>
				</div>
				<div class="text-center">
					Welcome, {{Auth::user()->name}}
				</div>
			</h3>
        	<br /><br />
			<div class="text-center px-3 h5 font-weight-normal">
				Thanks for signing up.
				<br />
				<br />
				You need to to be assigned an institution to be able to access institutional previleges.  
			</div>
			<br />
		</form>
	</div>
</section>
    
@endsection