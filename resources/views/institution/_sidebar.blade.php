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
    			<p class="app-sidebar__user-designation text-truncate">{{$institution->name}}</p>
    			<p class="app-sidebar__user-designation text-truncate">{{$institution->code}}</p>
    		</div>
    	</div>
	</div>
	<ul class="app-menu">
		<li><a class="app-menu__item active" href="{{route('institution.dashboard', $institution->id)}}"><i
				class="app-menu__icon fa fa-tachometer-alt"></i><span
				class="app-menu__label">Dashboard</span></a>
		</li>
		<li class="treeview"><a class="app-menu__item" href="#"
			data-toggle="treeview"><i class="app-menu__icon fa fa-calendar-day"></i><span
				class="app-menu__label">Events</span><i
				class="treeview-indicator fa fa-angle-right"></i></a>
			<ul class="treeview-menu">
				<li><a class="treeview-item" href="{{route('institution.event.index', $institution->id)}}"><i
						class="icon fa fa-graduation-cap"></i> View Events</a>
				</li>
				<li><a class="treeview-item" href="{{route('institution.event.create', $institution->id)}}"
					><i class="icon fa fa-plus"></i> Create Event</a>
				</li>
			</ul>
		</li>
		<li class="treeview"><a class="app-menu__item" href="#"
			data-toggle="treeview"><i class="app-menu__icon fa fa-users"></i><span
				class="app-menu__label">Students</span><i
				class="treeview-indicator fa fa-angle-right"></i></a>
			<ul class="treeview-menu">
				<li><a class="treeview-item" href="{{route('institution.student.index', $institution->id)}}"><i
						class="icon fa fa-graduation-cap"></i> View Students</a>
				</li>
				<li><a class="treeview-item" href="{{route('institution.student.create', $institution->id)}}"
					><i class="icon fa fa-plus"></i> Add Student</a>
				</li>
				<li><a class="treeview-item" href="{{route('institution.student.multi-create', $institution->id)}}"
					><i class="icon fa fa-plus-square"></i> Multi Add Student</a>
				</li>
				<li><a class="treeview-item" href="{{route('institution.student.upload.create', $institution->id)}}"
					><i class="icon fa fa-plus"></i> Upload Students</a>
				</li>
			</ul>
		</li>
		<li class="treeview"><a class="app-menu__item" href="#"
			data-toggle="treeview"><i class="app-menu__icon fa fa-hourglass-half"></i><span
				class="app-menu__label">Classes</span><i
				class="treeview-indicator fa fa-angle-right"></i></a>
			<ul class="treeview-menu">
				<li><a class="treeview-item" href="{{route('institution.grade.index', $institution->id)}}"><i
						class="icon fa fa-graduation-cap"></i> View Classes</a>
				</li>
				<li><a class="treeview-item" href="{{route('institution.grade.create', $institution->id)}}"
					><i class="icon fa fa-plus"></i> Add Class</a>
				</li>
			</ul>
		</li>
		<li class="treeview"><a class="app-menu__item" href="#"
			data-toggle="treeview"><i class="app-menu__icon fa fa-edit"></i><span
				class="app-menu__label">Exams</span><i
				class="treeview-indicator fa fa-angle-right"></i></a>
			<ul class="treeview-menu">
				<li><a class="treeview-item" href="{{route('institution.exam.index', $institution->id)}}"><i
						class="icon fa fa-circle-o"></i> View Exams</a>
				</li>
				<li><a class="treeview-item" href="{{route('institution.exam.create', $institution->id)}}"><i
						class="icon fa fa-circle-o"></i> Register Exam</a>
				</li>
			</ul>
		</li>
		<li><a class="app-menu__item active" href="{{route('ccd.course.index', $institution->id)}}"><i
				class="app-menu__icon fa fa-keyboard"></i><span
				class="app-menu__label">Content Developer</span></a>
		</li>
	</ul>
</aside>