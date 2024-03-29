<?php
$title = 'Admin - Login | ' . config('app.name');
$for = 'Admin';
?>
 @extends('ccd.vali_layout') 
 
 @section('body')

<section class="material-half-bg">
	<div class="cover"></div>
</section>
<section class="login-content">
	<div class="logo">
		<h1>{{config('app.name')}}</h1>
	</div>
	<div class="login-box">
		<form class="login-form" action="" method="post">
			<h3 class="login-head">
				<i class="fa fa-lg fa-fw fa-user"></i>ADMIN SIGN IN
			</h3>
			<div class="form-group">
				<label class="control-label">USERNAME</label> <input
					class="form-control" type="text" placeholder="Username" autofocus
					name="{{USERNAME}}">
			</div>
			<div class="form-group">
				<label class="control-label">PASSWORD</label> <input
					class="form-control" type="password" placeholder="Password"
					name="{{PASSWORD}}">
			</div>
			<div class="form-group">
				<div class="utility">
					<div class="animated-checkbox">
						<label> <input type="checkbox"><span class="label-text">Stay
								Signed in</span>
						</label>
					</div>
					<p class="semibold-text mb-2">
						<a href="#" data-toggle="flip">Forgot Password ?</a>
					</p>
				</div>
			</div>
			<div class="form-group btn-container">
				<button class="btn btn-primary btn-block">
					<i class="fa fa-sign-in fa-lg fa-fw"></i>SIGN IN
				</button>
			</div>
		</form>
		<form class="forget-form" action="index.html" method="post">
			<h3 class="login-head">
				<i class="fa fa-lg fa-fw fa-lock"></i>Forgot Password ?
			</h3>
			<div class="form-group">
				<label class="control-label">EMAIL</label> <input
					class="form-control" type="text" placeholder="Email">
			</div>
			<div class="form-group btn-container">
				<button class="btn btn-primary btn-block">
					<i class="fa fa-unlock fa-lg fa-fw"></i>RESET
				</button>
			</div>
			<div class="form-group mt-3">
				<p class="semibold-text mb-0">
					<a href="#" data-toggle="flip"><i class="fa fa-angle-left fa-fw"></i>
						Back to Login</a>
				</p>
			</div>
		</form>
	</div>
</section>
<!-- The javascript plugin to display page loading on top-->
<script src="js/plugins/pace.min.js"></script>
<script type="text/javascript">
      // Login Page Flipbox control
      $('.login-content [data-toggle="flip"]').click(function() {
      	$('.login-box').toggleClass('flipped');
      	return false;
      });
</script>

@endsection
