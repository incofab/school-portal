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
import { SidebarHeader } from '../components/sidebar-header';
import { InertiaLink } from '@inertiajs/inertia-react';
import route from '@/util/route';
import { Nullable, InstitutionUserType } from '@/types/types';
import useSharedProps from '@/hooks/use-shared-props';
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
  const { currentUser, currentInstitutionUser } = useSharedProps();
  const { instRoute } = useInstitutionRoute();
  const student = currentInstitutionUser.student;
  const staff = [
    InstitutionUserType.Admin,
    InstitutionUserType.Teacher,
    InstitutionUserType.Accountant,
  ];
  const teachers = [InstitutionUserType.Admin, InstitutionUserType.Teacher];
  const accountant = [
    InstitutionUserType.Admin,
    InstitutionUserType.Accountant,
  ];
  const studentOrAlumni = [
    InstitutionUserType.Student,
    InstitutionUserType.Alumni,
  ];

  const menus: MenuListType[] = [
    {
      label: 'Dashboard',
      route: instRoute('dashboard'),
    },
    ...(student
      ? [
          {
            label: 'My Results',
            route: instRoute('students.term-results.index', [student]),
            roles: [InstitutionUserType.Student],
          },
        ]
      : []),
    {
      label: 'Staff',
      roles: [InstitutionUserType.Admin],
      sub_items: [
        {
          label: 'All Staff',
          route: instRoute('users.index', { staffOnly: true }),
          roles: teachers,
        },
        {
          label: 'Add Staff',
          route: instRoute('users.create'),
          roles: [InstitutionUserType.Admin],
        },
        {
          label: 'Staff ID Cards',
          route: instRoute('users.idcards'),
          roles: [InstitutionUserType.Admin],
        },
      ],
    },
    {
      label: 'Students',
      roles: staff,
      sub_items: [
        {
          label: 'All Students',
          route: instRoute('students.index'),
          roles: staff,
        },
        {
          label: 'Guardians',
          route: instRoute('guardians.index'),
          roles: staff,
        },
        {
          label: 'Add Student',
          route: instRoute('students.create'),
          roles: [InstitutionUserType.Admin],
        },
        // {
        //   label: 'Student Applications',
        //   route: instRoute('admission-applications.index'),
        //   roles: [InstitutionUserType.Admin],
        // },
      ],
    },
    {
      label: 'Attendance',
      roles: [InstitutionUserType.Admin],
      sub_items: [
        {
          label: 'Mark Attendance',
          route: instRoute('attendances.create'),
          roles: [InstitutionUserType.Admin],
        },
        {
          label: 'All Attendances',
          route: instRoute('attendances.index'),
          roles: [InstitutionUserType.Admin],
        },
      ],
    },
    {
      label: 'Admissions',
      roles: [InstitutionUserType.Admin],
      sub_items: [
        {
          label: 'Admission Forms',
          route: instRoute('admission-forms.index'),
          roles: [InstitutionUserType.Admin],
        },
        {
          label: 'Admission Applications',
          route: instRoute('admission-applications.index'),
          roles: [InstitutionUserType.Admin],
        },
      ],
    },
    {
      label: 'Timetable',
      route: instRoute('timetables.index'),
      roles: [InstitutionUserType.Student, InstitutionUserType.Teacher],
    },
    {
      label: 'Subject',
      roles: [
        InstitutionUserType.Student,
        InstitutionUserType.Admin,
        InstitutionUserType.Teacher,
      ],
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
          roles: teachers,
        },
        {
          label: 'Recorded Results',
          route: instRoute('course-result-info.index'),
          roles: teachers,
        },
      ],
    },

    {
      label: 'Topics',
      route: instRoute('inst-topics.index'),
      roles: [InstitutionUserType.Admin, InstitutionUserType.Teacher],
    },
    {
      label: 'Lesson Notes',
      route: instRoute('lesson-notes.index'),
      roles: [InstitutionUserType.Student],
    },

    /*
    {
      label: 'Topics',
      roles: [InstitutionUserType.Admin],
      sub_items: [
        {
          label: 'All Topics',
          route: instRoute('inst-topics.index'),
          roles: [InstitutionUserType.Admin],
        },
        {
          label: 'Add Topic',
          route: instRoute('inst-topics.create-or-edit'),
          roles: [InstitutionUserType.Admin],
        },
      ],
    },

    {
      label: 'Scheme of Work',
      roles: [InstitutionUserType.Admin, InstitutionUserType.Teacher],
      sub_items: [
        {
          label: 'All Scheme of Works',
          route: instRoute('scheme-of-works.index'),
          roles: [InstitutionUserType.Admin, InstitutionUserType.Teacher],
        },
        {
          label: 'Add Scheme of Work',
          route: instRoute('scheme-of-works.create'),
          roles: [InstitutionUserType.Admin],
        },
      ],
    },

    {
      label: 'Lesson Plans',
      route: instRoute('lesson-plans.index'),
      roles: [InstitutionUserType.Admin, InstitutionUserType.Teacher],
    },

    {
      label: 'Lesson Notes',
      route: instRoute('lesson-notes.index'),
      roles: [
        InstitutionUserType.Student,
        InstitutionUserType.Admin,
        InstitutionUserType.Teacher,
      ],
    },

    {
      label: 'Notes',
      roles: [
        InstitutionUserType.Student,
        InstitutionUserType.Admin,
        InstitutionUserType.Teacher,
      ],
      sub_items: [
        {
          label: 'All Note Topics',
          route: instRoute('note-topics.index'),
          roles: [
            InstitutionUserType.Student,
            InstitutionUserType.Admin,
            InstitutionUserType.Teacher,
          ],
        },
        {
          label: 'Add Note Topic',
          route: instRoute('note-topics.create'),
          roles: [InstitutionUserType.Admin, InstitutionUserType.Teacher],
        },
      ],
    },
    */

    {
      label: 'Assignments',
      roles: [
        InstitutionUserType.Student,
        InstitutionUserType.Admin,
        InstitutionUserType.Teacher,
      ],
      sub_items: [
        {
          label: 'All Assignments',
          route: instRoute('assignments.index'),
          roles: [
            InstitutionUserType.Student,
            InstitutionUserType.Admin,
            InstitutionUserType.Teacher,
          ],
        },
        {
          label: 'Add Assignment',
          route: instRoute('assignments.create'),
          roles: [InstitutionUserType.Admin, InstitutionUserType.Teacher],
        },
        {
          label: 'Submitted Assignments',
          route: instRoute('assignment-submissions.index'),
          roles: [InstitutionUserType.Student],
        },
      ],
    },

    {
      label: 'Classes',
      roles: [
        ...teachers,
        InstitutionUserType.Student,
        InstitutionUserType.Alumni,
      ],
      sub_items: [
        {
          label: 'All Classes',
          route: instRoute('classifications.index'),
          roles: teachers,
        },
        {
          label: 'Add Class',
          route: instRoute('classifications.create'),
          roles: [InstitutionUserType.Admin],
        },
        {
          label: 'All Class Groups',
          route: instRoute('classification-groups.index'),
          roles: teachers,
        },
        {
          label: 'Student Class Changes',
          route: instRoute('student-class-movements.index'),
          roles: teachers,
        },
        {
          label: 'Class Result',
          route: instRoute('class-result-info.index'),
          roles: teachers,
        },
        {
          label: 'Session Result',
          route: instRoute('session-results.index'),
          roles: [
            ...teachers,
            InstitutionUserType.Student,
            InstitutionUserType.Alumni,
          ],
        },
      ],
    },
    {
      label: 'Admin',
      roles: [InstitutionUserType.Admin],
      sub_items: [
        {
          label: 'School Profile',
          route: instRoute('profile'),
          roles: [InstitutionUserType.Admin],
        },
        {
          label: 'Current Term Detail',
          route: instRoute('term-details.index'),
          roles: teachers,
        },
        {
          label: 'Pins',
          route: instRoute('pin-generators.index'),
          roles: [InstitutionUserType.Admin],
        },
        {
          label: 'Assessments',
          route: instRoute('assessments.index'),
          roles: [InstitutionUserType.Admin],
        },
        {
          label: 'Cummulative Results',
          route: instRoute('cummulative-result.index'),
          roles: [InstitutionUserType.Admin],
        },
        {
          label: 'Result Comments',
          route: instRoute('result-comment-templates.index'),
          roles: [InstitutionUserType.Admin],
        },
        {
          label: 'Result Publication',
          route: instRoute('result-publications.index'),
          roles: [InstitutionUserType.Admin],
        },
        {
          label: 'Student/Staff Divisions',
          route: instRoute('associations.index'),
          roles: [InstitutionUserType.Admin],
        },
        {
          label: 'SMS/Email Messages',
          route: instRoute('messages.index'),
          roles: [InstitutionUserType.Admin],
        },
      ],
    },
    {
      label: 'Funds',
      roles: [InstitutionUserType.Admin],
      sub_items: [
        {
          label: 'Add Fund',
          route: instRoute('fundings.create'),
          roles: [InstitutionUserType.Admin],
        },
        {
          label: 'All Fundings',
          route: instRoute('fundings.index'),
          roles: [InstitutionUserType.Admin],
        },
        {
          label: 'All Transactions',
          route: instRoute('transactions.index'),
          roles: [InstitutionUserType.Admin],
        },
        {
          label: 'Bank Accounts',
          route: instRoute('inst-bank-accounts.index'),
          roles: [InstitutionUserType.Admin],
        },
        {
          label: 'Withdrawals',
          route: instRoute('inst-withdrawals.index'),
          roles: [InstitutionUserType.Admin],
        },
      ],
    },
    {
      label: 'Payments',
      roles: [...accountant, ...studentOrAlumni],
      sub_items: [
        {
          label: 'Fees',
          route: instRoute('fees.index'),
          roles: accountant,
        },
        {
          label: 'Payments',
          route: instRoute('fee-payments.index'),
          roles: accountant,
        },
        {
          label: 'Receipts',
          route: instRoute('receipts.index'),
          roles: accountant,
        },
        ...(student
          ? [
              {
                label: 'Receipts',
                route: instRoute('students.receipts.index', [student.id]),
                roles: studentOrAlumni,
              },
              {
                label: 'Pay Fees',
                route: instRoute('students.fee-payments.create', [student.id]),
                roles: studentOrAlumni,
              },
            ]
          : []),
      ],
    },
    {
      label: 'Events',
      route: instRoute('events.index'),
      roles: [
        InstitutionUserType.Admin,
        InstitutionUserType.Student,
        InstitutionUserType.Teacher,
      ],
    },
    {
      label: 'Settings',
      route: instRoute('settings.create'),
      roles: [InstitutionUserType.Admin],
    },
    {
      label: 'Evaluations',
      roles: [InstitutionUserType.Admin],
      sub_items: [
        {
          label: 'Evaluation Types',
          route: instRoute('learning-evaluation-domains.index'),
          roles: [InstitutionUserType.Admin],
        },
        {
          label: 'Evaluations',
          route: instRoute('learning-evaluations.index'),
          roles: [InstitutionUserType.Admin],
        },
      ],
    },
    {
      label: 'Profile',
      route: instRoute('users.profile', [currentUser]),
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
      <SidebarHeader />
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
