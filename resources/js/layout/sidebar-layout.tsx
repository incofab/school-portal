import React from 'react';
import '../../css/dashboard.css';
import {
  Sidebar,
  Menu,
  MenuItem,
  sidebarClasses,
  menuClasses,
  SubMenu,
  MenuItemStyles,
} from 'react-pro-sidebar';
import { SidebarHeader } from '../components/SidebarHeader';
import { InertiaLink } from '@inertiajs/inertia-react';
import route from '@/util/route';
import { Nullable, UserRoleType } from '@/types/types';
import useSharedProps from '@/hooks/use-shared-props';
import useIsAdmin from '@/hooks/use-is-admin';

interface MenuType {
  label: string;
  icon?: string;
  roles?: Nullable<UserRoleType[]>;
  route?: string;
}

interface MenuListType extends MenuType {
  sub_items?: MenuType[];
}

export default function SideBarLayout() {
  const { currentUser } = useSharedProps();
  const isAdmin = useIsAdmin();

  const menus: MenuListType[] = [
    {
      label: 'Dashboard',
      route: route('home'),
    },
    {
      label: 'Users',
      roles: [UserRoleType.Admin],
      sub_items: [
        {
          label: 'All Users',
          route: route('users.index'),
          roles: [UserRoleType.Admin],
        },
        {
          label: 'Add User',
          route: route('users.create'),
          roles: [UserRoleType.Admin],
        },
      ],
    },
    {
      label: 'Courses',
      sub_items: [
        {
          label: 'All Courses',
          route: route('courses.index'),
          roles: [
            UserRoleType.Student,
            UserRoleType.Admin,
            UserRoleType.Lecturer,
          ],
        },
        {
          label: 'Add Course',
          route: route('courses.create'),
          roles: [UserRoleType.Admin],
        },
        {
          label: 'Course Registrations',
          route: route('course-registrations.index', [currentUser]),
          roles: [
            UserRoleType.Student,
            UserRoleType.Admin,
            UserRoleType.Lecturer,
          ],
        },
        {
          label: 'Lecturer Courses',
          route: route('lecturer-courses.index'),
          roles: [
            UserRoleType.Student,
            UserRoleType.Admin,
            UserRoleType.Lecturer,
          ],
        },
        {
          label: 'Course Results',
          route: route('course-results.index'),
        },
      ],
    },
    {
      label: 'Faculties',
      roles: [UserRoleType.Admin],
      sub_items: [
        {
          label: 'All Faculties',
          route: route('faculties.index'),
          roles: [UserRoleType.Admin],
        },
        {
          label: 'Add Faculty',
          route: route('faculties.create'),
          roles: [UserRoleType.Admin],
        },
      ],
    },
    {
      label: 'Departments',
      roles: [UserRoleType.Admin],
      sub_items: [
        {
          label: 'All Departments',
          route: route('departments.index'),
          roles: [UserRoleType.Admin],
        },
        {
          label: 'Add Department',
          route: route('departments.create'),
          roles: [UserRoleType.Admin],
        },
      ],
    },
    {
      label: 'Hostel',
      roles: [UserRoleType.Admin, UserRoleType.Student],
      sub_items: [
        {
          label: 'All Hostels',
          route: route('hostels.index'),
          roles: [UserRoleType.Admin, UserRoleType.Student],
        },
        {
          label: 'Add New Hostel',
          route: route('hostels.create'),
          roles: [UserRoleType.Admin],
        },
        ...(currentUser.is_welfare || isAdmin
          ? [
              {
                label: 'Assign Hostel',
                route: route('hostel-users.create'),
              },
            ]
          : []),
        {
          label: 'List Assigned Hostels',
          route: route('hostel-users.index'),
          roles: [UserRoleType.Admin, UserRoleType.Student],
        },
      ],
    },
    {
      label: 'Academic Sessions',
      roles: [UserRoleType.Admin],
      sub_items: [
        {
          label: 'All Sessions',
          route: route('academic-sessions.index'),
          roles: [UserRoleType.Admin],
        },
        {
          label: 'Add Session',
          route: route('academic-sessions.create'),
          roles: [UserRoleType.Admin],
        },
      ],
    },
    {
      label: 'Fees',
      sub_items: [
        {
          label: 'All Fees',
          route: route('fees.index'),
        },
        {
          label: 'Add New Fee',
          route: route('fees.create'),
          roles: [UserRoleType.Admin],
        },
        {
          label: 'Payment History',
          route: route('fee-payments.index', [isAdmin ? '' : currentUser]),
          roles: [UserRoleType.Admin, UserRoleType.Student],
        },
      ],
    },
    {
      label: 'Logout',
      route: route('logout'),
    },
  ];

  const menuItemStyles: MenuItemStyles = {
    root: {
      fontSize: '14px',
      fontWeight: 500,
    },
    // icon: {
    //   color: themes[theme].menu.icon,
    //   [`&.${menuClasses.disabled}`]: {
    //     color: themes[theme].menu.disabled.color,
    //   },
    // },
    SubMenuExpandIcon: {
      color: 'purple',
    },
    subMenuContent: ({ level }) => ({
      backgroundColor: level === 0 ? '#10105a' : 'transparent',
    }),
    button: {
      color: '#c2c1c1',
      [`&.${menuClasses.disabled}`]: {
        color: 'gray',
      },
      '&:hover': {
        backgroundColor: '#04136b',
        color: '#ffffff',
      },
    },
    label: ({ open }) => ({
      fontWeight: open ? 800 : undefined,
    }),
  };

  return (
    <Sidebar
      breakPoint="lg"
      rootStyles={{
        [`.${sidebarClasses.container}`]: {
          backgroundColor: '#060644',
        },
      }}
    >
      <SidebarHeader style={{ marginBottom: '24px', marginTop: '16px' }} />
      <Menu menuItemStyles={menuItemStyles}>
        {menus.map(function (menu: MenuListType, i: number) {
          if (menu.roles && !menu.roles.includes(currentUser.role)) {
            return;
          }
          if (!menu.sub_items) {
            return (
              <MenuItem
                key={i}
                component={<InertiaLink href={menu.route ?? ''} />}
              >
                {menu.label}
              </MenuItem>
            );
          }
          return (
            <SubMenu label={menu.label} key={i}>
              {menu.sub_items.map(function (subItem: MenuListType, i: number) {
                if (
                  subItem.roles &&
                  !subItem.roles?.includes(currentUser.role)
                ) {
                  return;
                }
                return (
                  <MenuItem
                    key={'j' + i}
                    component={<InertiaLink href={subItem.route ?? ''} />}
                  >
                    {subItem.label}
                  </MenuItem>
                );
              })}
            </SubMenu>
          );
        })}
      </Menu>
    </Sidebar>
  );
}
