
<style>
.app-sidebar {
    padding-top: 50px;
}
.app-sidebar__user{
    margin-bottom: 0;
    padding-top: 20px;
    padding-bottom: 20px;
    background-color: rgba(51, 51, 51, 0.4);
}
.app-sidebar__user_box{
    background-image: url("{{asset('img/material-bg.jpg')}}");
    background-size: cover;
}
.treeview-item {
    padding: 15px 5px 15px 20px;
}
</style>
<!-- Sidebar menu-->
<div class="app-sidebar__overlay" data-toggle="sidebar"></div>
<aside class="app-sidebar">
	<div class="app-sidebar__user_box">
    	<div class="app-sidebar__user">
    		<img class="app-sidebar__user-avatar" style="width: 48px; height: 48px; background-color: #afb7c4;"
    			src="{{asset('img/default.png')}}"
    			alt="User Image">
    		<div>
    			<p class="app-sidebar__user-name text-truncate">{{$currentUser->name}}</p>
    			<p class="app-sidebar__user-designation text-truncate">Administrative Staff</p>
    		</div>
    	</div>
	</div>
	<ul class="app-menu">
		<li><a class="app-menu__item active" href="{{instRoute('dashboard')}}"><i
				class="app-menu__icon fa fa-dashboard"></i><span
				class="app-menu__label">Dashboard</span></a>
		</li>
		{{-- 
			<li><a class="treeview-item" href="{{instRoute('ccd.index')}}"><i
				class="icon fa fa-users"></i> Admin Users</a>
			</li>
			<li><a class="treeview-item" href="{{instRoute('exam-contents.index')}}"><i
				class="icon fa fa-eye"></i> Exam Content/Body</a>
			</li>
			<li><a class="treeview-item" href="{{instRoute('course-sessions.index')}}"><i
					class="icon fa fa-eye"></i> Sessions</a>
			</li>
		--}}
		
	</ul>
</aside>