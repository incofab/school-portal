import React from 'react';
import {
  Sidebar,
  Menu,
  MenuItem,
  sidebarClasses,
  menuClasses,
  SubMenu,
  MenuItemStyles,
} from 'react-pro-sidebar';
import { InertiaLink } from '@inertiajs/inertia-react';
import route from '@/util/route';
import { Nullable, ManagerRole } from '@/types/types';
import useSharedProps from '@/hooks/use-shared-props';
import useIsAdminManager from '@/hooks/use-is-admin-manager';
import ManagerSidebarHeader from './manager-sidebar-header';

interface MenuType {
  label: string;
  icon?: string;
  roles?: Nullable<ManagerRole[]>;
  route?: string;
}

interface MenuListType extends MenuType {
  sub_items?: MenuType[];
}

export default function ManagerSideBarLayout() {
  const { currentUser } = useSharedProps();
  const isAdminManager = useIsAdminManager();
  const managerRole = currentUser.roles![0]?.name;

  const menus: MenuListType[] = [
    {
      label: 'Dashboard',
      route: route('managers.dashboard'),
    },
    {
      label: 'Managers',
      route: route('managers.index'),
      roles: [ManagerRole.Admin],
    },
    {
      label: 'Fundings',
      route: route('managers.funding.index'),
      roles: [ManagerRole.Admin],
    },
    {
      label: 'Billings',
      route: route('managers.billings.index'),
      roles: [ManagerRole.Admin],
    },
    {
      label: 'Institution Groups',
      roles: [ManagerRole.Partner, ManagerRole.Admin],
      route: route('managers.institution-groups.index'),
    },
    {
      label: 'Institutions',
      roles: [ManagerRole.Partner, ManagerRole.Admin],
      route: route('managers.institutions.index'),
    },
    {
      label: 'Registration Requests',
      roles: [ManagerRole.Partner, ManagerRole.Admin],
      route: route('managers.registration-requests.index'),
    },
    // {
    //   label: 'Pins',
    //   roles: [ManagerRole.Admin],
    //   sub_items: [
    //     {
    //       label: 'List Generated Pins',
    //       route: route('managers.pin-generators.index'),
    //       roles: [ManagerRole.Admin],
    //     },
    //     {
    //       label: 'Generate Pins',
    //       route: route('managers.generate-pin.create'),
    //       roles: [ManagerRole.Admin],
    //     },
    //   ],
    // },
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
      backgroundColor: level === 0 ? '#123a2b' : 'transparent',
    }),
    button: {
      color: '#c2c1c1',
      [`&.${menuClasses.disabled}`]: {
        color: 'gray',
      },
      '&:hover': {
        backgroundColor: '#2a8864',
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
          backgroundColor: '#06130e',
        },
      }}
    >
      <ManagerSidebarHeader />
      <Menu menuItemStyles={menuItemStyles}>
        {menus.map(function (menu: MenuListType, i: number) {
          if (menu.roles && !menu.roles.includes(managerRole)) {
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
                if (subItem.roles && !subItem.roles?.includes(managerRole)) {
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
