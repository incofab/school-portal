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
import { Nullable, InstitutionUserType } from '@/types/types';
import {
  hasInstitutionPermission,
  InstitutionPermission,
} from '@/types/permissions';
import useSharedProps from '@/hooks/use-shared-props';
import useInstitutionRoute from '@/hooks/use-institution-route';
import useIsTeacher from '@/hooks/use-is-teacher';
import { useModalValueToggle } from '@/hooks/use-modal-toggle';
import GenericSelectorModal, {
  GenericSelectorModalConfig,
} from '@/components/modals/generic-selector-modal';
import { Inertia } from '@inertiajs/inertia';
import route from '@/util/route';

interface MenuType {
  label: string;
  icon?: string;
  roles?: Nullable<InstitutionUserType[]>;
  permissions?: string[];
  route?: string;
  onClick?: () => void;
}

interface MenuListType extends MenuType {
  sub_items?: MenuType[];
}

export default function SideBarLayout() {
  const { currentUser, currentInstitutionUser, currentUserPermissions } =
    useSharedProps();
  const { toggleSidebar } = useProSidebar();
  const { instRoute } = useInstitutionRoute();
  const isTeacher = useIsTeacher();
  const reportModalToggle = useModalValueToggle<GenericSelectorModalConfig>();
  const student = currentInstitutionUser.student;
  const teachers = [InstitutionUserType.Admin, InstitutionUserType.Teacher];
  const accountant = [
    InstitutionUserType.Admin,
    InstitutionUserType.Accountant,
  ];
  const studentOrAlumni = [
    InstitutionUserType.Student,
    InstitutionUserType.Alumni,
    InstitutionUserType.Guardian,
  ];

  const menus: MenuListType[] = [
    {
      label: 'Dashboard',
      route: instRoute('dashboard'),
    },
    {
      label: 'Chats',
      route: instRoute('chats.index'),
      permissions: [InstitutionPermission.ManageChat],
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
      sub_items: [
        {
          label: 'All Staff',
          route: instRoute('users.index', { staffOnly: true }),
        },
        {
          label: 'Add Staff',
          route: instRoute('users.create'),
          roles: [InstitutionUserType.Admin],
        },
        {
          label: 'Roles and permissions',
          route: instRoute('roles.index'),
          roles: [InstitutionUserType.Admin],
          permissions: [InstitutionPermission.ManageRoles],
        },
        {
          label: 'Staff ID Cards',
          route: instRoute('users.idcards'),
        },
        {
          label: 'My Bank Accounts',
          route: instRoute('inst-user-bank-accounts.index'),
          permissions: [InstitutionPermission.ManageFinance],
        },
      ],
    },
    {
      label: 'Students',
      sub_items: [
        {
          label: 'All Students',
          route: instRoute('students.index'),
        },
        {
          label: 'Guardians',
          route: instRoute('guardians.index'),
          roles: [InstitutionUserType.Admin, InstitutionUserType.Teacher],
        },
        {
          label: 'Add Student',
          route: instRoute('students.create'),
          roles: [InstitutionUserType.Admin, InstitutionUserType.Teacher],
        },
        {
          label: 'Student ID Cards',
          route: instRoute('students.idcards'),
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
      sub_items: [
        {
          label: 'All Subject',
          route: instRoute('courses.index'),
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
          roles: [InstitutionUserType.Admin, InstitutionUserType.Teacher],
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
        },
        {
          label: 'Add Class',
          route: instRoute('classifications.create'),
          roles: [InstitutionUserType.Admin],
        },
        {
          label: 'All Class Groups',
          route: instRoute('classification-groups.index'),
        },
        {
          label: 'Student Class Changes',
          route: instRoute('student-class-movements.index'),
          roles: [InstitutionUserType.Admin, InstitutionUserType.Teacher],
        },
        {
          label: 'Class Divisions',
          route: instRoute('class-divisions.index'),
        },
        {
          label: 'Class Result',
          route: instRoute('class-result-info.index'),
          roles: [InstitutionUserType.Admin, InstitutionUserType.Teacher],
        },
        {
          label: 'Session Result',
          route: instRoute('session-results.index'),
          roles: [InstitutionUserType.Admin, InstitutionUserType.Teacher],
        },
        {
          label: 'Live Classes',
          route: instRoute('live-classes.index'),
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
      sub_items: [
        {
          label: 'Mark Attendance',
          route: instRoute('attendances.create'),
          permissions: [InstitutionPermission.ManageAttendance],
        },
        {
          label: 'All Attendances',
          route: instRoute('attendances.index'),
        },
        {
          label: 'Class Register',
          route: instRoute('attendances.class-register.view'),
          permissions: [InstitutionPermission.ManageAttendance],
        },
        {
          label: 'Attendance Report',
          route: instRoute('attendance-reports.index'),
        },
      ],
    },
    {
      label: 'Reports',
      roles: [InstitutionUserType.Admin, InstitutionUserType.Teacher],
      sub_items: [
        {
          label: 'Grade Report',
          route: instRoute('reports.grade-report'),
        },
        {
          label: 'Subject Report',
          route: instRoute('reports.subject-report'),
        },
        {
          label: 'Single Subject Report',
          route: instRoute('reports.single-subject-report'),
        },
        {
          label: 'Full Subject Result',
          route: instRoute('reports.class-subject-result-report'),
        },
        {
          label: 'Full Class Report',
          route: instRoute('reports.full-class-report'),
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
      label: 'Recruitment',
      roles: [InstitutionUserType.Admin],
      sub_items: [
        {
          label: 'Vacancy Posts',
          route: instRoute('vacancy-posts.index'),
          roles: [InstitutionUserType.Admin],
        },
      ],
    },
    {
      label: 'Curriculum',
      roles: [
        InstitutionUserType.Admin,
        InstitutionUserType.Teacher,
        InstitutionUserType.Student,
      ],
      sub_items: [
        {
          label: 'Topics',
          route: instRoute('inst-topics.index'),
          roles: [InstitutionUserType.Admin, InstitutionUserType.Teacher],
        },
        {
          label: 'Scheme of Work',
          route: instRoute('scheme-of-works.index'),
          roles: [InstitutionUserType.Admin, InstitutionUserType.Teacher],
        },
        {
          label: 'Lesson Plans',
          route: instRoute('lesson-plans.index'),
          roles: [InstitutionUserType.Admin, InstitutionUserType.Teacher],
        },
        {
          label: 'Lesson Notes',
          route: instRoute('lesson-notes.index'),
          roles: [InstitutionUserType.Admin, InstitutionUserType.Teacher],
        },
      ],
    },
    {
      label: 'Assignments',
      sub_items: [
        {
          label: 'All Assignments',
          route: instRoute('assignments.index'),
        },
        {
          label: 'Add Assignment',
          route: instRoute('assignments.create'),
          permissions: [InstitutionPermission.NaturalAccess],
        },
        {
          label: 'Submitted Assignments',
          route: instRoute('assignment-submissions.index'),
          roles: [InstitutionUserType.Student],
        },
      ],
    },
    {
      label: 'E-Library',
      sub_items: [
        {
          label: 'Library Materials',
          route: instRoute('libraries.index'),
        },
        {
          label: 'Add Material',
          route: instRoute('libraries.create'),
          permissions: [InstitutionPermission.ManageLibrary],
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
          label: 'Activity Logs',
          route: instRoute('activity-logs.index'),
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
          label: 'Sent Notifications',
          route: instRoute('notifications.sent.index'),
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
          permissions: [InstitutionPermission.ManageFees],
        },
        {
          label: 'Payments',
          route: instRoute('fee-payments.index'),
          permissions: [InstitutionPermission.ManageFees],
        },
        {
          label: 'Payment Attempts',
          route: instRoute('payment-attempts.index'),
          permissions: [InstitutionPermission.ManageFees],
        },
        {
          label: 'Manual Payments',
          route: instRoute('manual-payments.index'),
          permissions: [InstitutionPermission.ManageFees],
        },
        {
          label: 'Receipts',
          route: instRoute('receipts.index'),
          permissions: [InstitutionPermission.ManageFees],
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
              {
                label: 'Manual Payments',
                route: instRoute('students.manual-payments.history', [
                  student.id,
                ]),
                roles: studentOrAlumni,
              },
              {
                label: 'Payment Attempts',
                route: instRoute('payment-attempts.index'),
                roles: studentOrAlumni,
              },
            ]
          : []),
      ],
    },
    {
      label: 'CBT Events',
      route: instRoute('events.index'),
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
      permissions: [InstitutionPermission.ManagePayroll],
      sub_items: [
        {
          label: 'Payroll',
          route: instRoute('payroll-summaries.index'),
          permissions: [InstitutionPermission.ManagePayroll],
        },
        {
          label: 'Salary Components',
          route: instRoute('salary-types.index'),
          permissions: [InstitutionPermission.ManagePayroll],
        },
        {
          label: 'Salaries',
          route: instRoute('salaries.index'),
          permissions: [InstitutionPermission.ManagePayroll],
        },
        // {
        //   label: 'Bonuses/Deductions',
        //   route: instRoute('payroll-adjustments.index'),
        //   roles: [InstitutionUserType.Admin],
        // },
      ],
    },
    {
      label: 'FAQ / Knowledge Base',
      route: route('knowledge-base'),
      roles: null,
    },
    // {
    //   label: 'Profile',
    //   route: instRoute('users.profile', [currentUser]),
    // },
    // {
    //   label: 'Logout',
    //   route: route('logout'),
    // },
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
          if (menu.roles && !menu.roles.includes(currentInstitutionUser.type)) {
            return;
          }
          if (
            menu.permissions &&
            !menu.permissions.some((permission) =>
              hasInstitutionPermission(currentUserPermissions, permission)
            )
          ) {
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
                  !subItem.roles?.includes(currentInstitutionUser.type)
                ) {
                  return;
                }
                if (
                  subItem.permissions &&
                  !subItem.permissions.some((permission) =>
                    hasInstitutionPermission(currentUserPermissions, permission)
                  )
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
