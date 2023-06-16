import React from 'react';
import DashboardLayout from '@/layout/dashboard-layout';
import { Box, Icon, SimpleGrid, Text } from '@chakra-ui/react';
import useSharedProps from '@/hooks/use-shared-props';
import { InertiaLink } from '@inertiajs/inertia-react';
import { PageTitle } from '@/components/page-header';
import useInstitutionRoute from '@/hooks/use-institution-route';
import {
  AcademicCapIcon,
  BuildingStorefrontIcon,
  ChartBarIcon,
  MapIcon,
  UsersIcon,
} from '@heroicons/react/24/solid';
import { InstitutionUserType } from '@/types/types';
import useIsStaff from '@/hooks/use-is-staff';

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

function InstitutionDashboard() {
  const { currentInstitutionUser } = useSharedProps();
  const isStaff = useIsStaff();
  const { instRoute } = useInstitutionRoute();
  const staffOnly = [InstitutionUserType.Admin, InstitutionUserType.Teacher];
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
      route: () => {
        if (isStaff) {
          return instRoute('class-result-info.index');
        }
        return instRoute('students.term-results.index');
      },
      icon: ChartBarIcon,
    },
    {
      title: 'Results',
      desc: 'List classes',
      route: instRoute('classifications.index'),
      icon: BuildingStorefrontIcon,
    },
    {
      title: 'Pin',
      desc: 'Result activation pins',
      route: instRoute('pin-prints.index'),
      icon: MapIcon,
    },
  ];

  return (
    <DashboardLayout>
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
