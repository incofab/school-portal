import React from 'react';
import DashboardLayout from '@/layout/dashboard-layout';
import {
  Box,
  Flex,
  HStack,
  Icon,
  IconButton,
  SimpleGrid,
  Stack,
  Text,
  useColorModeValue,
  VStack,
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
import { BvnNinReminderMessage, InstitutionUserType } from '@/types/types';
import useInstitutionRole from '@/hooks/use-institution-role';
import { InstitutionGroup, ReservedAccount, User } from '@/types/models';
import { copyToClipboard, formatAsCurrency, numberFormat } from '@/util/util';
import {
  Alert,
  AlertIcon,
  AlertTitle,
  AlertDescription,
} from '@chakra-ui/react';
import CenteredBox from '@/components/centered-box';
import { LinkButton } from '@/components/buttons';
import {
  ArrowRightIcon,
  BellAlertIcon,
  BuildingLibraryIcon,
  ClipboardIcon,
  EnvelopeIcon,
  WalletIcon,
} from '@heroicons/react/24/outline';
import { Div } from '@/components/semantic';
import UpdateBvnNinForm from '@/components/users/update-bvn-nin-form';
import { LabelText } from '@/components/result-helper-components';
import useModalToggle from '@/hooks/use-modal-toggle';
import ListReservedAccountsModal from '@/components/modals/users/list-reserved-accounts-modal';
import DashboardCharts from '@/components/dashboard-charts';
import { DashboardData } from '@/types/dashboard';
import Slab from '@/components/slab';

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
  count?: string;
}

interface Props {
  institutionGroup: InstitutionGroup;
  isSetupComplete: string;
  reservedAccounts: ReservedAccount[];
  dashboardData: DashboardData;
  attentionSummary?: {
    pendingManualPaymentsCount: number;
    unreadChatCount: number;
    hasBankAccounts: boolean;
    canManageBankAccounts: boolean;
  } | null;
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
      <PageTitle color={'brand.500'} px={3} py={2}>
        <HStack align={'center'} justifyContent={'space-between'} w={'full'}>
          <Div py={5}>{prop.title}</Div>
          {prop.count && (
            <Div
              backgroundColor={'brand.700'}
              color={'brand.50'}
              fontWeight={'bold'}
              height={'40px'}
              lineHeight={'40px'}
              minWidth={'40px'}
              borderRadius={'20px'}
              textAlign={'center'}
              px={3}
              fontSize={'md'}
            >
              {prop.count}
            </Div>
          )}
        </HStack>
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

export default function InstitutionDashboard({
  institutionGroup,
  isSetupComplete,
  reservedAccounts,
  dashboardData,
  attentionSummary,
}: Props) {
  // console.log('Dashboard data', dashboardData);
  const { currentInstitutionUser, currentUser } = useSharedProps();
  const student = currentInstitutionUser.student;
  const { forTeacher, forAccountant } = useInstitutionRole();
  const { instRoute } = useInstitutionRoute();
  const isAdmin = currentInstitutionUser.role === InstitutionUserType.Admin;
  const isGuardian =
    currentInstitutionUser.role === InstitutionUserType.Guardian;
  const accountant = [
    InstitutionUserType.Admin,
    InstitutionUserType.Accountant,
  ];
  const attentionItems = attentionSummary
    ? [
        ...(attentionSummary.pendingManualPaymentsCount > 0
          ? [
              {
                key: 'manual-payments',
                title: 'Pending manual payments',
                description: `${numberFormat(
                  attentionSummary.pendingManualPaymentsCount
                )} payment${
                  attentionSummary.pendingManualPaymentsCount === 1 ? '' : 's'
                } awaiting review.`,
                cta: 'Review Payments',
                href: instRoute('manual-payments.index'),
                icon: BellAlertIcon,
                accent: {
                  border: useColorModeValue('orange.200', 'orange.700'),
                  bg: useColorModeValue(
                    'linear-gradient(135deg, rgba(251, 191, 36, 0.18), rgba(249, 115, 22, 0.10))',
                    'linear-gradient(135deg, rgba(251, 191, 36, 0.14), rgba(124, 45, 18, 0.45))'
                  ),
                  badgeBg: useColorModeValue('orange.500', 'orange.300'),
                  badgeColor: useColorModeValue('white', 'gray.900'),
                  iconBg: useColorModeValue('orange.100', 'orange.900'),
                  iconColor: useColorModeValue('orange.600', 'orange.200'),
                  textColor: useColorModeValue('gray.800', 'gray.100'),
                  mutedColor: useColorModeValue('gray.600', 'gray.300'),
                },
              },
            ]
          : []),
        ...(attentionSummary.unreadChatCount > 0
          ? [
              {
                key: 'unread-chats',
                title: 'Unread chats',
                description: `${numberFormat(
                  attentionSummary.unreadChatCount
                )} unread chat${
                  attentionSummary.unreadChatCount === 1 ? '' : 's'
                } need your response.`,
                cta: 'Open Chats',
                href: instRoute('chats.index'),
                icon: EnvelopeIcon,
                accent: {
                  border: useColorModeValue('emerald.200', 'emerald.700'),
                  bg: useColorModeValue(
                    'linear-gradient(135deg, rgba(16, 185, 129, 0.18), rgba(6, 182, 212, 0.10))',
                    'linear-gradient(135deg, rgba(5, 150, 105, 0.18), rgba(8, 145, 178, 0.35))'
                  ),
                  badgeBg: useColorModeValue('emerald.500', 'emerald.300'),
                  badgeColor: useColorModeValue('white', 'gray.900'),
                  iconBg: useColorModeValue('emerald.100', 'emerald.900'),
                  iconColor: useColorModeValue('emerald.600', 'emerald.200'),
                  textColor: useColorModeValue('gray.800', 'gray.100'),
                  mutedColor: useColorModeValue('gray.600', 'gray.300'),
                },
              },
            ]
          : []),
        ...(!attentionSummary.hasBankAccounts
          ? [
              {
                key: 'bank-accounts',
                title: 'Bank accounts not set',
                description:
                  "Add the school's bank account so manual transfer payments can be directed correctly.",
                cta: attentionSummary.canManageBankAccounts
                  ? 'Configure Accounts'
                  : 'View Bank Accounts',
                href: instRoute('inst-bank-accounts.index'),
                icon: BuildingLibraryIcon,
                accent: {
                  border: useColorModeValue('blue.200', 'blue.700'),
                  bg: useColorModeValue(
                    'linear-gradient(135deg, rgba(59, 130, 246, 0.16), rgba(6, 182, 212, 0.10))',
                    'linear-gradient(135deg, rgba(37, 99, 235, 0.16), rgba(8, 145, 178, 0.35))'
                  ),
                  badgeBg: useColorModeValue('blue.500', 'blue.300'),
                  badgeColor: useColorModeValue('white', 'gray.900'),
                  iconBg: useColorModeValue('blue.100', 'blue.900'),
                  iconColor: useColorModeValue('blue.600', 'blue.200'),
                  textColor: useColorModeValue('gray.800', 'gray.100'),
                  mutedColor: useColorModeValue('gray.600', 'gray.300'),
                },
              },
            ]
          : []),
      ]
    : [];

  const items: ItemCardProps[] = [
    {
      title: 'Users',
      desc: 'List all users',
      route: instRoute('users.index'),
      icon: UsersIcon,
      roles: [InstitutionUserType.Admin],
      count: numberFormat(dashboardData.num_staff + dashboardData.num_students),
    },
    {
      title: 'Subjects',
      desc: 'Show subjects',
      route: instRoute('courses.index'),
      icon: AcademicCapIcon,
      count: numberFormat(dashboardData.num_subjects),
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
      count: numberFormat(dashboardData.num_classes),
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
      title: 'Wallet Balance',
      desc: 'Credit Balance',
      route: instRoute('fundings.create'),
      icon: CurrencyDollarIcon,
      roles: accountant,
      count: formatAsCurrency(institutionGroup.credit_wallet),
    },
    {
      title: 'Debt Balance',
      desc: 'Debt Balance',
      route: instRoute('fundings.index'),
      icon: CurrencyDollarIcon,
      roles: accountant,
      count: formatAsCurrency(institutionGroup.debt_wallet),
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
      {forAccountant && attentionItems.length > 0 && (
        <Slab mb={6}>
          <Stack spacing={5}>
            <Flex
              align={{ base: 'start', md: 'center' }}
              justify="space-between"
              direction={{ base: 'column', md: 'row' }}
              gap={2}
            >
              <Box>
                <Text
                  textTransform="uppercase"
                  letterSpacing="0.16em"
                  fontSize="xs"
                  fontWeight="bold"
                  color={useColorModeValue('brand.600', 'brand.200')}
                >
                  Attention
                </Text>
                <PageTitle mb={0}>Items that need quick action</PageTitle>
              </Box>
              <Text
                fontSize="sm"
                color={useColorModeValue('gray.500', 'gray.400')}
              >
                Review and resolve operational blockers from one place.
              </Text>
            </Flex>

            <SimpleGrid columns={{ base: 1, lg: 2 }} spacing={4}>
              {attentionItems.map((item) => (
                <Box
                  key={item.key}
                  position="relative"
                  overflow="hidden"
                  rounded="2xl"
                  borderWidth={1}
                  borderColor={item.accent.border}
                  bg={item.accent.bg}
                  p={{ base: 5, md: 6 }}
                  boxShadow="0 18px 40px rgba(15, 23, 42, 0.08)"
                >
                  <Box
                    position="absolute"
                    top="-18px"
                    right="-18px"
                    w="110px"
                    h="110px"
                    rounded="full"
                    bg={useColorModeValue(
                      'rgba(255,255,255,0.32)',
                      'rgba(255,255,255,0.05)'
                    )}
                  />
                  <Stack spacing={5} position="relative">
                    <Flex align="start" justify="space-between" gap={4}>
                      <HStack spacing={4} align="start">
                        <Flex
                          w="52px"
                          h="52px"
                          rounded="xl"
                          align="center"
                          justify="center"
                          bg={item.accent.iconBg}
                          color={item.accent.iconColor}
                          boxShadow="inset 0 1px 0 rgba(255,255,255,0.35)"
                        >
                          <Icon as={item.icon} boxSize={6} />
                        </Flex>
                        <Box>
                          <Text
                            fontSize="lg"
                            fontWeight="semibold"
                            color={item.accent.textColor}
                          >
                            {item.title}
                          </Text>
                          <Text
                            mt={1}
                            fontSize="sm"
                            lineHeight="tall"
                            color={item.accent.mutedColor}
                          >
                            {item.description}
                          </Text>
                        </Box>
                      </HStack>
                      <Box
                        px={3}
                        py={1}
                        rounded="full"
                        bg={item.accent.badgeBg}
                        color={item.accent.badgeColor}
                        fontSize="xs"
                        fontWeight="bold"
                        letterSpacing="0.08em"
                        textTransform="uppercase"
                      >
                        Action
                      </Box>
                    </Flex>

                    <LinkButton
                      href={item.href}
                      title={item.cta}
                      alignSelf="start"
                      rightIcon={<Icon as={ArrowRightIcon} />}
                      borderRadius="full"
                      px={5}
                    />
                  </Stack>
                </Box>
              ))}
            </SimpleGrid>
          </Stack>
        </Slab>
      )}
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
      {isGuardian && (
        <Div my={2} w={'full'}>
          <WalletDisplay
            user={currentUser}
            reservedAccounts={reservedAccounts}
          />
          <UpdateBvnNinForm
            mt={2}
            bg={useColorModeValue('white', 'gray.900')}
            w={'full'}
          />
        </Div>
      )}
      <SimpleGrid spacing={6} columns={{ base: 1, sm: 2, md: 3 }} mt={6}>
        {items.map(function (item) {
          if (item.roles && !item.roles.includes(currentInstitutionUser.role)) {
            return null;
          }
          return <DashboardItemCard {...item} key={item.title} />;
        })}
      </SimpleGrid>
      {isAdmin && (
        <>
          <br />
          <Div>
            {/* <PageTitle mb={0}>Dashboard Overview</PageTitle> */}
            {/* <DashboardStats data={dashboardData} /> */}
            <DashboardCharts data={dashboardData} />
          </Div>
        </>
      )}
    </DashboardLayout>
  );
}

function WalletDisplay({
  user,
  reservedAccounts,
}: {
  user: User;
  reservedAccounts: ReservedAccount[];
}) {
  const listReservedAccountModalToggle = useModalToggle();
  const reservedAccount = reservedAccounts[0] ?? null;
  return (
    <Div
      bg={useColorModeValue('white', 'gray.900')}
      p={4}
      borderRadius={'5px'}
      boxShadow="md"
      // _hover={{ bg: 'gray.100' }}
      // transition="background 0.2s"
    >
      <Stack
        direction={{ base: 'column', md: 'row' }}
        justify={'space-between'}
      >
        <Flex align="center" gap={4}>
          <Icon as={WalletIcon} boxSize={8} color="brand.500" />
          <Div>
            <Text fontSize="lg" fontWeight="bold">
              Bal: {formatAsCurrency(user.wallet)}
            </Text>
            <Text fontSize="sm" color="gray.500">
              Fund your wallet to pay fees and other payments
            </Text>
          </Div>
        </Flex>
        <HStack>
          {reservedAccount ? (
            <VStack align={'end'} spacing={1} w={'full'}>
              <LabelText
                label="Bank Name"
                text={reservedAccount.bank_name}
                width={'250px'}
              />
              <LabelText
                label="Account No"
                lineHeight={'2rem'}
                width={'250px'}
                text={
                  <HStack align={'stretch'} justify={'space-between'}>
                    <Text>{reservedAccount.account_number}</Text>
                    <IconButton
                      aria-label={'Copy'}
                      icon={<Icon as={ClipboardIcon} />}
                      size={'sm'}
                      onClick={() =>
                        copyToClipboard(
                          reservedAccount.account_number,
                          `Account number ${reservedAccount.account_number} copied`
                        )
                      }
                      variant={'unstyled'}
                    />
                  </HStack>
                }
              />
              <LabelText
                label="Account Name"
                text={reservedAccount.account_name}
                width={'250px'}
              />
            </VStack>
          ) : (
            <Div px={4}>{BvnNinReminderMessage}</Div>
          )}
          <IconButton
            aria-label="More"
            icon={<Icon as={ArrowRightIcon} />}
            onClick={() => listReservedAccountModalToggle.open()}
            px={3}
            py={2}
          />
        </HStack>
      </Stack>
      <ListReservedAccountsModal
        {...listReservedAccountModalToggle.props}
        reservedAccounts={reservedAccounts}
      />
    </Div>
  );
}
