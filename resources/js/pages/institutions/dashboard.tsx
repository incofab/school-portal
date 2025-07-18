import React from 'react';
import DashboardLayout from '@/layout/dashboard-layout';
import {
  Box,
  Icon,
  SimpleGrid,
  Text,
  useColorModeValue,
} from '@chakra-ui/react';
import useSharedProps from '@/hooks/use-shared-props';
import { InertiaLink } from '@inertiajs/inertia-react';
import { PageTitle } from '@/components/page-header';
import useInstitutionRoute from '@/hooks/use-institution-route';
import {
  AcademicCapIcon,
  BanknotesIcon,
  BuildingStorefrontIcon,
  ChartBarIcon,
  MapIcon,
  UsersIcon,
  CurrencyDollarIcon,
} from '@heroicons/react/24/solid';
import { InstitutionUserType } from '@/types/types';
import useInstitutionRole from '@/hooks/use-institution-role';
import { InstitutionGroup } from '@/types/models';
import { formatAsCurrency } from '@/util/util';
import {
  Alert,
  AlertIcon,
  AlertTitle,
  AlertDescription,
} from '@chakra-ui/react';
import CenteredBox from '@/components/centered-box';
import { LinkButton } from '@/components/buttons';
import { EnvelopeIcon } from '@heroicons/react/24/outline';

interface ItemCardProps {
  route: string;
  icon: React.ForwardRefExoticComponent<
    React.PropsWithoutRef<React.SVGProps<SVGSVGElement>> & {
      title?: string;
      titleId?: string;
    } & React.RefAttributes<SVGSVGElement>
  >;
  title: string;
  desc: string;
  roles?: InstitutionUserType[];
}

interface Props {
  institutionGroup: InstitutionGroup;
  isSetupComplete: string;
}

function DashboardItemCard(prop: ItemCardProps) {
  return (
    <Box
      border={'solid'}
      borderWidth={1}
      borderColor={useColorModeValue('gray.200', 'gray.500')}
      rounded={'lg'}
      boxShadow={'0px 2px 6px rgba(0, 0, 0, 0.1)'}
      background={useColorModeValue('white', 'gray.700')}
      as={InertiaLink}
      href={prop.route}
      display={'inline-block'}
      position={'relative'}
    >
      <PageTitle px={3} py={5} color={'brand.500'}>
        {prop.title}
      </PageTitle>
      <Text
        size={'sm'}
        py={3}
        px={3}
        backgroundColor={useColorModeValue('brand.600', 'gray.800')}
        color={'white'}
        borderTop={'1px solid rgba(150,150,150,0.1)'}
        roundedBottom={'lg'}
        fontStyle={'italic'}
      >
        {prop.desc}
      </Text>
      <Icon
        as={prop.icon}
        position={'absolute'}
        right={5}
        top={'50%'}
        opacity={0.3}
        fontSize={'7xl'}
        transform={'rotate(30deg) translateY(-50%)'}
        color={'brand.300'}
      />
    </Box>
  );
}

function InstitutionDashboard({ institutionGroup, isSetupComplete }: Props) {
  const { currentInstitutionUser } = useSharedProps();
  const student = currentInstitutionUser.student;
  const { forTeacher } = useInstitutionRole();
  const { instRoute } = useInstitutionRoute();
  const isAdmin = currentInstitutionUser.role === InstitutionUserType.Admin;
  const accountant = [
    InstitutionUserType.Admin,
    InstitutionUserType.Accountant,
  ];

  const items: ItemCardProps[] = [
    {
      title: 'Users',
      desc: 'List all users',
      route: instRoute('users.index'),
      icon: UsersIcon,
      roles: [InstitutionUserType.Admin],
    },
    {
      title: 'Subjects',
      desc: 'Show subjects',
      route: instRoute('courses.index'),
      icon: AcademicCapIcon,
    },
    {
      title: 'Results',
      desc: 'See your results',
      route: forTeacher
        ? instRoute('class-result-info.index')
        : student
        ? instRoute('students.term-results.index', [student.id])
        : '',
      icon: ChartBarIcon,
      roles: [
        InstitutionUserType.Admin,
        InstitutionUserType.Alumni,
        InstitutionUserType.Student,
        // InstitutionUserType.Guardian,
      ],
    },
    {
      title: 'Classes',
      desc: 'List classes',
      route: instRoute('classifications.index'),
      icon: BuildingStorefrontIcon,
    },
    {
      title: 'Pin',
      desc: 'Result activation pins',
      route: instRoute('pin-generators.index'),
      icon: MapIcon,
      roles: [InstitutionUserType.Admin],
    },
    {
      title: 'Payments',
      desc: 'Show fee payments',
      route: instRoute('fee-payments.index'),
      icon: BanknotesIcon,
      roles: accountant,
    },
    {
      title: formatAsCurrency(institutionGroup.credit_wallet),
      desc: 'Credit Balance',
      route: instRoute('fundings.create'),
      icon: CurrencyDollarIcon,
      roles: accountant,
    },
    {
      title: formatAsCurrency(institutionGroup.debt_wallet),
      desc: 'Debt Balance',
      route: instRoute('fundings.index'),
      icon: CurrencyDollarIcon,
      roles: accountant,
    },
    ...(student
      ? [
          {
            title: 'Receipts',
            desc: 'Payments receipts',
            route: instRoute('students.receipts.index', [student.id]),
            icon: BanknotesIcon,
            roles: [InstitutionUserType.Student],
          },
        ]
      : []),
    {
      title: 'Students', // Dependents
      desc: 'Shows your children/Wards',
      route: instRoute('guardians.list-dependents'),
      icon: UsersIcon,
      roles: [InstitutionUserType.Guardian],
    },
    {
      title: 'SMS/Email',
      desc: 'Send Emails/SMS Messages',
      route: instRoute('messages.index'),
      icon: EnvelopeIcon,
      roles: [InstitutionUserType.Admin, InstitutionUserType.Accountant],
    },
  ];

  return (
    <DashboardLayout>
      {!isSetupComplete && isAdmin ? (
        <CenteredBox mb={5}>
          <Alert status="error" variant="left-accent">
            <AlertIcon />
            <AlertTitle>You have some unfinished setup.</AlertTitle>
            <AlertDescription>
              <LinkButton
                variant={'outline'}
                href={instRoute('dashboard.setup-checklist')}
                title="Continue Setup"
              />
            </AlertDescription>
          </Alert>
        </CenteredBox>
      ) : (
        ''
      )}

      <SimpleGrid spacing={6} columns={{ base: 1, sm: 2, md: 3 }}>
        {items.map(function (item) {
          if (item.roles && !item.roles.includes(currentInstitutionUser.role)) {
            return null;
          }
          return <DashboardItemCard {...item} key={item.title} />;
        })}
      </SimpleGrid>
    </DashboardLayout>
  );
}

export default InstitutionDashboard;
