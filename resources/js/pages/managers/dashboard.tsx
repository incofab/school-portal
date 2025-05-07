import React from 'react';
import {
  Box,
  Icon,
  SimpleGrid,
  Text,
  useColorModeValue,
} from '@chakra-ui/react';
import ManagerDashboardLayout from '@/layout/managers/manager-dashboard-layout';
import useIsPartner from '@/hooks/use-is-partner';
import useSharedProps from '@/hooks/use-shared-props';
import route from '@/util/route';
import Slab, { SlabBody } from '@/components/slab';
import { ManagerRole } from '@/types/types';
import { InertiaLink } from '@inertiajs/inertia-react';
import { PageTitle } from '@/components/page-header';
import { formatAsCurrency } from '@/util/util';
import { CurrencyDollarIcon } from '@heroicons/react/24/solid';

interface Props {
  commissionBalance: number;
}

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
  roles?: ManagerRole[];
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

function ManagerDashboard({ commissionBalance }: Props) {
  const isPartner = useIsPartner();
  const { currentUser } = useSharedProps();
  const onboardingUrl = route('registration-requests.create', [currentUser]);

  const items: ItemCardProps[] = [
    {
      title: formatAsCurrency(commissionBalance),
      desc: 'Commission Balance',
      route: route('managers.withdrawals.index'),
      icon: CurrencyDollarIcon,
      roles: [ManagerRole.Partner],
    },
  ];

  return (
    <ManagerDashboardLayout>
      {isPartner && (
        <>
          <Slab my={2}>
            <SlabBody>
              <Text as={'span'}>Onboarding Link: </Text>{' '}
              <Text
                as={'a'}
                href={onboardingUrl}
                target="_blank"
                color={'brand.500'}
              >
                {onboardingUrl}
              </Text>
            </SlabBody>
          </Slab>

          <SimpleGrid spacing={6} columns={{ base: 1, sm: 2, md: 3 }} mt={5}>
            {items.map(function (item) {
              return <DashboardItemCard {...item} key={item.title} />;
            })}
          </SimpleGrid>
        </>
      )}
    </ManagerDashboardLayout>
  );
}

export default ManagerDashboard;
