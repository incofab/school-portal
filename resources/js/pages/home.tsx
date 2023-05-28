import { PageTitle } from '@/components/page-header';
import useIsAlumni from '@/hooks/use-is-alumni';
// import useIsLecturer from '@/hooks/use-is-lecturer';
import useIsStudent from '@/hooks/use-is-student';
import useSharedProps from '@/hooks/use-shared-props';
import { UserRoleType } from '@/types/types';
import route from '@/util/route';
import { Box, Icon, SimpleGrid, Text } from '@chakra-ui/react';
import { AcademicCapIcon } from '@heroicons/react/24/outline';
import {
  BuildingStorefrontIcon,
  ChartBarIcon,
  CurrencyDollarIcon,
  UsersIcon,
} from '@heroicons/react/24/solid';
import { InertiaLink } from '@inertiajs/inertia-react';
import React from 'react';
import DashboardLayout from '../layout/dashboard-layout';

interface ItemCardProps {
  route: string;
  icon: React.ForwardRefExoticComponent<React.SVGProps<SVGSVGElement>>;
  title: string;
  desc: string;
  roles?: UserRoleType[];
}

function DashboardItemCard(prop: ItemCardProps) {
  return (
    <Box
      border={'solid'}
      borderWidth={1}
      borderColor={'gray.200'}
      rounded={'lg'}
      boxShadow={'0px 2px 6px rgba(0, 0, 0, 0.1)'}
      background={'white'}
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
        backgroundColor={'brand.600'}
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

function home() {
  const { currentUser } = useSharedProps();
  const isStudent = useIsStudent();
  const isLecturer = false;
  const isAlumni = useIsAlumni();
  const items: ItemCardProps[] = [
    {
      title: 'Users',
      desc: 'List all users',
      route: route('users.index'),
      icon: UsersIcon,
      roles: [UserRoleType.Admin],
    },
    {
      title: 'Courses',
      desc: 'Show course',
      route: (() => {
        if (isStudent || isAlumni) {
          return route('course-registrations.index', [currentUser]);
        }
        if (isLecturer) {
          return route('lecturer-courses.index', [currentUser]);
        }
        return route('courses.index');
      })(),
      icon: AcademicCapIcon,
    },
    {
      title: 'Results',
      desc: 'See your results',
      route: route('course-results.index'),
      icon: ChartBarIcon,
    },
    {
      title: 'Hostels',
      desc: 'List hostels',
      route: route('hostels.index'),
      icon: BuildingStorefrontIcon,
    },
    {
      title: 'Fees',
      desc: 'My fees',
      route: (() => {
        if (isStudent || isAlumni) {
          return route('fee-payments.index', [currentUser]);
        }
        return route('fee-payments.index');
      })(),
      icon: CurrencyDollarIcon,
      roles: [UserRoleType.Admin, UserRoleType.Alumni, UserRoleType.Student],
    },
  ];
  return (
    <DashboardLayout>
      <SimpleGrid spacing={6} columns={{ base: 1, sm: 2, md: 3 }}>
        {items.map(function (item) {
          if (item.roles && !item.roles.includes(currentUser.role)) {
            return null;
          }
          return <DashboardItemCard {...item} key={item.title} />;
        })}
      </SimpleGrid>
    </DashboardLayout>
  );
}

export default home;
