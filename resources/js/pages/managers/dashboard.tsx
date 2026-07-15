import React from 'react';
import {
  Alert,
  AlertDescription,
  AlertIcon,
  AlertTitle,
  Box,
  Button,
  Icon,
  HStack,
  Input,
  SimpleGrid,
  Text,
  VStack,
  useColorModeValue,
} from '@chakra-ui/react';
import ManagerDashboardLayout from '@/layout/managers/manager-dashboard-layout';
import useIsAdminManager from '@/hooks/use-is-admin-manager';
import useIsPartner from '@/hooks/use-is-partner';
import useSharedProps from '@/hooks/use-shared-props';
import route from '@/util/route';
import Slab, { SlabBody } from '@/components/slab';
import { ManagerRole } from '@/types/types';
import { InertiaLink } from '@inertiajs/inertia-react';
import { PageTitle } from '@/components/page-header';
import { formatAsCurrency } from '@/util/util';
import { CurrencyDollarIcon } from '@heroicons/react/24/solid';
import { LinkButton } from '@/components/buttons';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import { Inertia } from '@inertiajs/inertia';

interface Props {
  commissionBalance: number;
  attentionSummary?: {
    pendingWithdrawalsCount: number;
  } | null;
  partnerProfile?: {
    id: number;
    name?: string | null;
    canUpdate: boolean;
  } | null;
  partnerAnalytics?: {
    institutionGroupsCount: number;
    institutionsCount: number;
    registrationRequestsCount: number;
    partnerUsersCount: number;
    bankAccountsCount: number;
    pendingWithdrawalsCount: number;
    totalWithdrawalsCount: number;
  } | null;
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

interface PartnerMetricCardProps {
  label: string;
  value: string | number;
  tone: string;
}

function PartnerMetricCard({ label, value, tone }: PartnerMetricCardProps) {
  return (
    <Box
      borderWidth={1}
      borderColor={useColorModeValue(`${tone}.200`, `${tone}.600`)}
      background={useColorModeValue(`${tone}.50`, 'gray.800')}
      rounded="md"
      px={4}
      py={4}
      minH="96px"
    >
      <Text fontSize="sm" color={useColorModeValue('gray.600', 'gray.300')}>
        {label}
      </Text>
      <Text fontSize="2xl" fontWeight="bold" mt={2}>
        {value}
      </Text>
    </Box>
  );
}

function ManagerDashboard({
  commissionBalance,
  attentionSummary,
  partnerProfile,
  partnerAnalytics,
}: Props) {
  const isAdminManager = useIsAdminManager();
  const isPartner = useIsPartner();
  const { currentUser } = useSharedProps();
  const { handleResponseToast } = useMyToast();
  const onboardingUrl = route('registration-requests.create', [currentUser]);
  const partnerName = partnerProfile?.name || currentUser.full_name || '';
  const partnerProfileForm = useWebForm({
    name: partnerName,
  });

  async function updatePartnerProfile() {
    const res = await partnerProfileForm.submit((data, web) =>
      web.post(route('managers.partner-profile.update'), data)
    );
    if (!handleResponseToast(res)) {
      return;
    }
    Inertia.reload({ only: ['partnerProfile', 'shared__currentUser'] });
  }

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
      {isAdminManager &&
        (attentionSummary?.pendingWithdrawalsCount ?? 0) > 0 && (
          <Slab my={2}>
            <SlabBody>
              <Alert
                status="warning"
                variant="subtle"
                rounded="md"
                alignItems="flex-start"
              >
                <AlertIcon mt={1} />
                <HStack
                  justifyContent="space-between"
                  alignItems="center"
                  spacing={4}
                  width="full"
                  flexWrap="wrap"
                >
                  <Text>
                    <AlertTitle mr={2}>Attention Notice</AlertTitle>
                    <AlertDescription display="inline">
                      {attentionSummary?.pendingWithdrawalsCount} withdrawal
                      {attentionSummary?.pendingWithdrawalsCount === 1
                        ? ''
                        : 's'}{' '}
                      pending review.
                    </AlertDescription>
                  </Text>
                  <LinkButton
                    href={route('managers.withdrawals.index')}
                    title="Review Withdrawals"
                    variant="outline"
                  />
                </HStack>
              </Alert>
            </SlabBody>
          </Slab>
        )}

      {isPartner && (
        <>
          <Box
            my={2}
            px={{ base: 4, md: 6 }}
            py={5}
            rounded="lg"
            borderWidth={1}
            borderColor={useColorModeValue('teal.200', 'teal.700')}
            background={useColorModeValue('teal.50', 'gray.800')}
          >
            <VStack spacing={4} align="stretch">
              <HStack
                justifyContent="space-between"
                alignItems="flex-end"
                flexWrap="wrap"
                spacing={4}
              >
                <Box>
                  <Text fontSize="sm" color="teal.700" fontWeight="semibold">
                    Partner Account
                  </Text>
                  <Text fontSize="2xl" fontWeight="bold">
                    {partnerName}
                  </Text>
                </Box>
                <Text
                  as={'a'}
                  href={onboardingUrl}
                  target="_blank"
                  color={useColorModeValue('teal.700', 'teal.200')}
                  fontWeight="semibold"
                >
                  Onboarding Link
                </Text>
              </HStack>

              {partnerProfile?.canUpdate && (
                <HStack spacing={3} alignItems="flex-start">
                  <Input
                    value={partnerProfileForm.data.name}
                    onChange={(e) =>
                      partnerProfileForm.setValue('name', e.currentTarget.value)
                    }
                    background={useColorModeValue('white', 'gray.700')}
                    maxW="520px"
                  />
                  <Button
                    colorScheme="teal"
                    onClick={updatePartnerProfile}
                    isLoading={partnerProfileForm.processing}
                  >
                    Update Name
                  </Button>
                </HStack>
              )}
            </VStack>
          </Box>

          {partnerAnalytics && (
            <SimpleGrid spacing={4} columns={{ base: 1, sm: 2, lg: 4 }} mt={5}>
              <PartnerMetricCard
                label="School Groups"
                value={partnerAnalytics.institutionGroupsCount}
                tone="teal"
              />
              <PartnerMetricCard
                label="Schools"
                value={partnerAnalytics.institutionsCount}
                tone="cyan"
              />
              <PartnerMetricCard
                label="Registration Requests"
                value={partnerAnalytics.registrationRequestsCount}
                tone="orange"
              />
              <PartnerMetricCard
                label="Partner Users"
                value={partnerAnalytics.partnerUsersCount}
                tone="purple"
              />
              <PartnerMetricCard
                label="Bank Accounts"
                value={partnerAnalytics.bankAccountsCount}
                tone="blue"
              />
              <PartnerMetricCard
                label="Pending Withdrawals"
                value={partnerAnalytics.pendingWithdrawalsCount}
                tone="yellow"
              />
              <PartnerMetricCard
                label="Total Withdrawals"
                value={partnerAnalytics.totalWithdrawalsCount}
                tone="gray"
              />
              <PartnerMetricCard
                label="Commission Balance"
                value={formatAsCurrency(commissionBalance)}
                tone="green"
              />
            </SimpleGrid>
          )}

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
