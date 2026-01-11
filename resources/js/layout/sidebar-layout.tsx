import React from 'react';
import {
  Sidebar,
  Menu,
  MenuItem,
  sidebarClasses,
  menuClasses,
  SubMenu,
  MenuItemStyles,
  useProSidebar,
} from 'react-pro-sidebar';
import { SidebarHeader } from '../components/sidebar-header';
import { InertiaLink } from '@inertiajs/inertia-react';
import route from '@/util/route';
import { Nullable, InstitutionUserType } from '@/types/types';
import useSharedProps from '@/hooks/use-shared-props';
import useInstitutionRoute from '@/hooks/use-institution-route';
import useIsTeacher from '@/hooks/use-is-teacher';
import { useModalValueToggle } from '@/hooks/use-modal-toggle';
import GenericSelectorModal, {
  GenericSelectorModalConfig,
} from '@/components/modals/generic-selector-modal';
import { Inertia } from '@inertiajs/inertia';

interface MenuType {
  label: string;
  icon?: string;
  roles?: Nullable<InstitutionUserType[]>;
  route?: string;
  onClick?: () => void;
}

interface MenuListType extends MenuType {
  sub_items?: MenuType[];
}

export default function SideBarLayout() {
  const { currentUser, currentInstitutionUser } = useSharedProps();
  const { toggleSidebar, collapseSidebar, broken, collapsed } = useProSidebar();
  const { instRoute } = useInstitutionRoute();
  const isTeacher = useIsTeacher();
  const reportModalToggle = useModalValueToggle<GenericSelectorModalConfig>();
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
        {
          label: 'My Bank Accounts',
          route: instRoute('inst-user-bank-accounts.index'),
          roles: staff,
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
          route: instRoute(
            'course-teachers.index',
            isTeacher ? [currentUser.id] : undefined
          ),
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
          label: 'Class Divisions',
          route: instRoute('class-divisions.index'),
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
        {
          label: 'Live Classes',
          route: instRoute('live-classes.index'),
          roles: [...teachers, InstitutionUserType.Student],
        },
      ],
    },
    {
      label: 'Timetable',
      route: instRoute('timetables.index'),
      roles: [...teachers, InstitutionUserType.Student],
    },
    {
      label: 'Attendance',
      roles: [InstitutionUserType.Admin, InstitutionUserType.Teacher],
      sub_items: [
        {
          label: 'Mark Attendance',
          route: instRoute('attendances.create'),
          roles: [InstitutionUserType.Admin, InstitutionUserType.Teacher],
        },
        {
          label: 'All Attendances',
          route: instRoute('attendances.index'),
          roles: [InstitutionUserType.Admin, InstitutionUserType.Teacher],
        },
        {
          label: 'Attendance Report',
          route: instRoute('attendance-reports.index'),
          roles: [InstitutionUserType.Admin, InstitutionUserType.Teacher],
        },
      ],
    },
    {
      label: 'Reports',
      roles: [InstitutionUserType.Admin, InstitutionUserType.Teacher],
      sub_items: [
        {
          label: 'Subject Report',
          route: instRoute('reports.subject-report'),
          roles: [InstitutionUserType.Admin, InstitutionUserType.Teacher],
        },
        {
          label: 'Student Result',
          onClick: () =>
            reportModalToggle.open({
              title: 'Student Result',
              submitLabel: 'View Result',
              fields: [
                { key: 'classification', label: 'Class', isRequired: true },
                { key: 'student', label: 'Student', isRequired: true },
                {
                  key: 'academicSession',
                  label: 'Academic Session',
                  isRequired: true,
                },
                { key: 'term', label: 'Term', isRequired: true },
                { key: 'forMidTerm', label: 'For Mid-Term Result' },
              ],
              onSubmit: (values) => {
                Inertia.visit(
                  instRoute('students.result-sheet', [
                    values.student,
                    values.classification,
                    values.academicSession,
                    values.term,
                    values.forMidTerm ? 1 : 0,
                  ])
                );
              },
            }),
          roles: [InstitutionUserType.Admin, InstitutionUserType.Teacher],
        },
        {
          label: 'Student Class Sheet',
          onClick: () =>
            reportModalToggle.open({
              title: 'Student Class Sheet',
              submitLabel: 'View Sheet',
              fields: [
                { key: 'classification', label: 'Class', isRequired: true },
                {
                  key: 'academicSession',
                  label: 'Academic Session',
                  isRequired: true,
                },
                { key: 'term', label: 'Term', isRequired: true },
                { key: 'forMidTerm', label: 'For Mid-Term Result' },
              ],
              onSubmit: (values) => {
                Inertia.visit(
                  instRoute('class-result-info.fetch-result-sheets', {
                    classification: values.classification,
                    academicSession: values.academicSession,
                    term: values.term,
                    forMidTerm: values.forMidTerm ? 1 : 0,
                  })
                );
              },
            }),
          roles: [InstitutionUserType.Admin, InstitutionUserType.Teacher],
        },
        {
          label: 'Transcript',
          onClick: () =>
            reportModalToggle.open({
              title: 'Student Transcript',
              submitLabel: 'View Transcript',
              fields: [{ key: 'student', label: 'Student', isRequired: true }],
              onSubmit: (values) => {
                Inertia.visit(
                  instRoute('students.transcript', [values.student])
                );
              },
            }),
          roles: [InstitutionUserType.Admin, InstitutionUserType.Teacher],
        },
        {
          label: 'Cummulative Results',
          onClick: () =>
            reportModalToggle.open({
              title: 'Cummulative Results',
              submitLabel: 'View Results',
              fields: [
                { key: 'classification', label: 'Class', isRequired: true },
                {
                  key: 'academicSession',
                  label: 'Academic Session',
                  isRequired: true,
                },
                { key: 'term', label: 'Term' },
              ],
              onSubmit: (values) => {
                const params: { [key: string]: any } = {
                  classification: values.classification,
                  academicSession: values.academicSession,
                };
                if (values.term) {
                  params.term = values.term;
                }
                Inertia.visit(instRoute('cummulative-result.index', params));
              },
            }),
          roles: [InstitutionUserType.Admin, InstitutionUserType.Teacher],
        },
        {
          label: 'Session Results',
          onClick: () =>
            reportModalToggle.open({
              title: 'Session Results',
              submitLabel: 'View Results',
              fields: [
                { key: 'classification', label: 'Class', isRequired: true },
                { key: 'academicSession', label: 'Academic Session' },
              ],
              onSubmit: (values) => {
                const params = values.academicSession
                  ? [values.classification, values.academicSession]
                  : [values.classification];
                Inertia.visit(
                  instRoute('classifications.session-results.index', params)
                );
              },
            }),
          roles: [InstitutionUserType.Admin, InstitutionUserType.Teacher],
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
      label: 'Topics',
      route: instRoute('inst-topics.index'),
      roles: [InstitutionUserType.Admin, InstitutionUserType.Teacher],
    },
    {
      label: 'Lesson Notes',
      route: instRoute('lesson-notes.index'),
      roles: [InstitutionUserType.Student],
    },
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
          label: 'School Bank Accounts',
          route: instRoute('inst-bank-accounts.index'),
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
          label: 'Expenses',
          route: instRoute('expenses.index'),
          roles: [InstitutionUserType.Admin],
        },
        {
          label: 'Expense Categorires',
          route: instRoute('expense-categories.index'),
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
      label: 'Payroll',
      roles: [InstitutionUserType.Admin],
      sub_items: [
        {
          label: 'Payroll',
          route: instRoute('payroll-summaries.index'),
          roles: [InstitutionUserType.Admin],
        },
        {
          label: 'Salary Components',
          route: instRoute('salary-types.index'),
          roles: [InstitutionUserType.Admin],
        },
        {
          label: 'Salaries',
          route: instRoute('salaries.index'),
          roles: [InstitutionUserType.Admin],
        },
        // {
        //   label: 'Bonuses/Deductions',
        //   route: instRoute('payroll-adjustments.index'),
        //   roles: [InstitutionUserType.Admin],
        // },
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
                component={
                  menu.route ? (
                    <InertiaLink href={menu.route ?? ''} />
                  ) : undefined
                }
                onClick={() => {
                  menu.onClick?.();
                  toggleSidebar(false);
                }}
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
                    component={
                      subItem.route ? (
                        <InertiaLink href={subItem.route ?? ''} />
                      ) : undefined
                    }
                    onClick={() => {
                      subItem.onClick?.();
                      toggleSidebar(false);
                    }}
                  >
                    {subItem.label}
                  </MenuItem>
                );
              })}
            </SubMenu>
          );
        })}
      </Menu>
      {reportModalToggle.state && (
        <GenericSelectorModal
          {...reportModalToggle.props}
          title={reportModalToggle.state.title}
          submitLabel={reportModalToggle.state.submitLabel}
          fields={reportModalToggle.state.fields}
          initialValues={reportModalToggle.state.initialValues}
          onSubmit={reportModalToggle.state.onSubmit}
        />
      )}
    </Sidebar>
  );
}
