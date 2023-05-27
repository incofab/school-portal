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
    background-image: url("{{assets('img/images/material-bg.jpg')}}");
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
    			src="{{assets('img/default.png')}}"
    			alt="User Image">
    		<div>
    			<p class="app-sidebar__user-name text-truncate">{{Auth::user()->username}}</p>
    			<p class="app-sidebar__user-designation text-truncate">Administrative Staff</p>
    		</div>
    	</div>
	</div>
	<ul class="app-menu">
		<li><a class="app-menu__item active" href="{{route('admin.dashboard')}}"><i
				class="app-menu__icon fa fa-dashboard"></i><span
				class="app-menu__label">Dashboard</span></a>
		</li>
		<li><a class="app-menu__item active" href="{{route('admin.user.index')}}"><i
				class="app-menu__icon fa fa-users"></i><span
				class="app-menu__label">Users</span></a>
		</li>
		<li class="treeview"><a class="app-menu__item" href="#"
			data-toggle="treeview"><i class="app-menu__icon fa fa-edit"></i><span
				class="app-menu__label">Institutions</span><i
				class="treeview-indicator fa fa-angle-right"></i></a>
			<ul class="treeview-menu">
				<li><a class="treeview-item" href="{{route('admin.institution.index')}}"><i
						class="icon fa fa-hall"></i> View Institution</a>
				</li>
				<li><a class="treeview-item" href="{{route('admin.institution.create')}}"><i
						class="icon fa fa-plus"></i> Add Institution</a>
				</li>
			</ul>
		</li>
		
		
		<?php /*
		<li><a class="treeview-item" href="{{getAddr('admin_exam_contents')}}"><i
				class="icon fa fa-eye"></i> Exam Content/Body</a>
		</li>
		<li><a class="app-menu__item" href="{{getAddr('admin_install_courses')}}"><i
				class="app-menu__icon fa fa-pie-chart"></i><span
				class="app-menu__label">Install/Uninstall Courses</span></a>
		</li>
		<li><a class="app-menu__item" href="{{getAddr('ccd_home')}}"><i
				class="app-menu__icon fa fa-pie-chart"></i><span
				class="app-menu__label">Content Developer</span></a>
		</li>
		*/?>
	</ul>
</aside>