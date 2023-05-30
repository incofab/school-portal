import React from 'react';
// import '../../css/dashboard.css';
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
import { Nullable, InstitutionUserType } from '@/types/types';
import useSharedProps from '@/hooks/use-shared-props';
import useIsAdmin from '@/hooks/use-is-admin';
import useInstitutionRoute from '@/hooks/use-institution-route';

interface MenuType {
  label: string;
  icon?: string;
  roles?: Nullable<InstitutionUserType[]>;
  route?: string;
}

interface MenuListType extends MenuType {
  sub_items?: MenuType[];
}

export default function SideBarLayout() {
  const { currentUser, currentInstitution, currentInstitutionUser } =
    useSharedProps();
  const isAdmin = useIsAdmin();
  const { instRoute } = useInstitutionRoute();

  const menus: MenuListType[] = [
    {
      label: 'Dashboard',
      route: instRoute('dashboard'),
    },
    {
      label: 'Staff',
      roles: [InstitutionUserType.Admin],
      sub_items: [
        {
          label: 'All Staff',
          route: instRoute('users.index', [
            {
              roles_not_in: [
                InstitutionUserType.Student,
                InstitutionUserType.Alumni,
              ],
            },
          ]),
          roles: [InstitutionUserType.Admin, InstitutionUserType.Teacher],
        },
        {
          label: 'Add Staff',
          route: instRoute('users.create'),
          roles: [InstitutionUserType.Admin],
        },
      ],
    },
    {
      label: 'Students',
      roles: [InstitutionUserType.Admin],
      sub_items: [
        {
          label: 'All Students',
          route: instRoute('students.index'),
          roles: [InstitutionUserType.Admin],
        },
        {
          label: 'Add Student',
          route: instRoute('students.create'),
          roles: [InstitutionUserType.Admin],
        },
      ],
    },
    {
      label: 'Subject',
      sub_items: [
        {
          label: 'All Subject',
          route: instRoute('courses.index'),
          roles: [
            InstitutionUserType.Student,
            InstitutionUserType.Admin,
            InstitutionUserType.Teacher,
          ],
        },
        {
          label: 'Add Subject',
          route: instRoute('courses.create'),
          roles: [InstitutionUserType.Admin],
        },
        {
          label: 'Subject Teachers',
          route: instRoute('course-teachers.index'),
          roles: [
            InstitutionUserType.Student,
            InstitutionUserType.Admin,
            InstitutionUserType.Teacher,
          ],
        },
        {
          label: 'Subject Results',
          route: instRoute('course-results.index'),
        },
      ],
    },
    {
      label: 'Classes',
      sub_items: [
        {
          label: 'All Classes',
          route: instRoute('classifications.index'),
        },
        {
          label: 'Add Class',
          route: instRoute('classifications.create'),
          roles: [InstitutionUserType.Admin],
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
          if (menu.roles && !menu.roles.includes(currentInstitutionUser.role)) {
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
                  !subItem.roles?.includes(currentInstitutionUser.role)
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
