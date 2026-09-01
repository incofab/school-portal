import React, { useMemo, useState } from 'react';
import {
  Alert,
  AlertIcon,
  Avatar,
  Badge,
  Box,
  Button,
  Divider,
  FormControl,
  FormErrorMessage,
  FormHelperText,
  FormLabel,
  HStack,
  Icon,
  Input,
  Radio,
  RadioGroup,
  SimpleGrid,
  Text,
  VStack,
  useColorModeValue,
} from '@chakra-ui/react';
import {
  BanknotesIcon,
  CheckCircleIcon,
  CheckIcon,
  CreditCardIcon,
  LockClosedIcon,
} from '@heroicons/react/24/outline';
import { BankAccount, Institution, Student } from '@/types/models';
import { DefaultPaymentMerchantType, PaymentMerchantType } from '@/types/types';
import BankAccountList from '@/components/payments/bank-account-list';
import useMyToast from '@/hooks/use-my-toast';
import useWebForm from '@/hooks/use-web-form';
import { formatAsCurrency } from '@/util/util';
import route from '@/util/route';

interface PublicFee {
  id: number;
  title: string;
  amount: number;
  amount_paid: number;
  amount_remaining: number;
  status: 'paid' | 'partial' | 'due';
  term?: string | null;
  payment_interval?: string | null;
  academic_session?: string | null;
  fee_items?: Array<{ title?: string; amount?: number }>;
}

interface Props {
  institution: Institution;
  student: Student;
  fees: PublicFee[];
  academicSession?: { id: number; title: string } | null;
  term?: string | null;
  bankAccounts: BankAccount[];
}

export default function StudentFeePayment({
  institution,
  student,
  fees,
  academicSession,
  term,
  bankAccounts,
}: Props) {
  const dueFees = useMemo(
    () => fees.filter((fee) => fee.status !== 'paid'),
    [fees]
  );
  const firstFee = dueFees[0];
  const [selectedFeeId, setSelectedFeeId] = useState<number | null>(
    firstFee?.id ?? null
  );
  const selectedFee = fees.find((fee) => fee.id === selectedFeeId);
  const { handleResponseToast } = useMyToast();
  const webForm = useWebForm({
    fee_id: firstFee?.id ?? '',
    amount: firstFee?.amount_remaining ?? 0,
    merchant: DefaultPaymentMerchantType,
  });

  const pageBackground = useColorModeValue('gray.50', 'gray.900');
  const surfaceBackground = useColorModeValue('white', 'gray.800');
  const mutedBackground = useColorModeValue('gray.50', 'gray.700');
  const brandText = useColorModeValue('brand.700', 'brand.200');
  const totalOutstanding = dueFees.reduce(
    (total, fee) => total + fee.amount_remaining,
    0
  );

  function selectFee(fee: PublicFee) {
    if (fee.status === 'paid') return;
    setSelectedFeeId(fee.id);
    webForm.setData({
      ...webForm.data,
      fee_id: fee.id,
      amount: fee.amount_remaining,
    });
  }

  async function submit() {
    if (!selectedFee) return;

    const res = await webForm.submit((data, web) =>
      web.post(
        route('public.student-fee-payment.store', [
          institution.code,
          student.code,
        ]),
        data
      )
    );

    if (!handleResponseToast(res)) return;

    window.location.href =
      res.data.authorization_url ??
      res.data.redirect_url ??
      route('public.student-fee-payment', [institution.code, student.code]);
  }

  return (
    <Box minH="100vh" bg={pageBackground} pb={{ base: 8, md: 12 }}>
      <Box
        bg="brand.700"
        color="white"
        px={{ base: 4, md: 8 }}
        py={{ base: 5, md: 7 }}
      >
        <Box maxW="1180px" mx="auto">
          <HStack justify="space-between" align="center" spacing={4}>
            <HStack spacing={3} minW={0}>
              <Avatar
                size="md"
                name={institution.name}
                src={institution.photo ?? undefined}
                bg="whiteAlpha.300"
              />
              <Box minW={0}>
                <Text
                  fontWeight="bold"
                  fontSize={{ base: 'md', md: 'lg' }}
                  noOfLines={1}
                >
                  {institution.name}
                </Text>
                <Text fontSize="sm" color="whiteAlpha.800">
                  Secure fee payment
                </Text>
              </Box>
            </HStack>
            <HStack
              display={{ base: 'none', sm: 'flex' }}
              spacing={2}
              color="whiteAlpha.900"
            >
              <Icon as={LockClosedIcon} boxSize={4} />
              <Text fontSize="sm">Safe and simple</Text>
            </HStack>
          </HStack>
        </Box>
      </Box>

      <Box
        maxW="1180px"
        mx="auto"
        px={{ base: 4, md: 8 }}
        mt={{ base: 5, md: 8 }}
      >
        <Box
          bg={surfaceBackground}
          border="1px solid"
          borderColor={useColorModeValue('gray.200', 'gray.700')}
          borderRadius="2xl"
          p={{ base: 5, md: 7 }}
          shadow="sm"
        >
          <HStack align={{ base: 'start', md: 'center' }} spacing={4}>
            <Avatar
              size={{ base: 'md', md: 'lg' }}
              name={student.user?.full_name}
              src={student.user?.photo_url}
              bg="brand.100"
              color={brandText}
            />
            <Box minW={0}>
              <Text
                fontSize={{ base: 'xl', md: '2xl' }}
                fontWeight="bold"
                color={brandText}
                noOfLines={2}
              >
                {student.user?.full_name ?? 'Student'}
              </Text>
              <HStack mt={1} spacing={2} flexWrap="wrap">
                <Badge colorScheme="brand">
                  {student.full_code ?? student.code}
                </Badge>
                {student.classification?.title ? (
                  <Text fontSize="sm" color="gray.500">
                    {student.classification.title}
                  </Text>
                ) : null}
              </HStack>
            </Box>
          </HStack>

          <SimpleGrid columns={{ base: 1, sm: 3 }} spacing={3} mt={6}>
            <SummaryStat label="Fees listed" value={`${fees.length}`} />
            <SummaryStat
              label="Outstanding"
              value={formatAsCurrency(totalOutstanding)}
              emphasize
            />
            <SummaryStat
              label="Current period"
              value={`${term ? `${term} term` : ''}${
                term && academicSession ? ' · ' : ''
              }${academicSession?.title ?? 'Current'}`}
            />
          </SimpleGrid>
        </Box>

        <SimpleGrid
          columns={{ base: 1, lg: 2 }}
          spacing={{ base: 5, lg: 7 }}
          mt={{ base: 5, md: 7 }}
          alignItems="start"
        >
          <Box id="fee-list">
            <Text fontSize="xl" fontWeight="bold" color={brandText}>
              Choose a fee to pay
            </Text>
            <Text color="gray.500" mt={1} mb={4}>
              Paid fees are shown for your records. Select any outstanding fee
              to continue.
            </Text>

            {fees.length ? (
              <VStack align="stretch" spacing={3}>
                {fees.map((fee) => (
                  <FeeCard
                    key={fee.id}
                    fee={fee}
                    selected={fee.id === selectedFeeId}
                    onSelect={() => selectFee(fee)}
                    surfaceBackground={surfaceBackground}
                    mutedBackground={mutedBackground}
                  />
                ))}
              </VStack>
            ) : (
              <Alert status="info" borderRadius="xl">
                <AlertIcon />
                There are no fees available for this student right now.
              </Alert>
            )}
          </Box>

          <Box
            bg={surfaceBackground}
            border="1px solid"
            borderColor={useColorModeValue('gray.200', 'gray.700')}
            borderRadius="2xl"
            p={{ base: 5, md: 7 }}
            shadow="sm"
            position={{ lg: 'sticky' }}
            top={{ lg: 5 }}
          >
            <HStack justify="space-between" align="start" mb={5}>
              <Box>
                <Text fontSize="xl" fontWeight="bold" color={brandText}>
                  Payment details
                </Text>
                <Text fontSize="sm" color="gray.500" mt={1}>
                  Review your selection, then choose how to pay.
                </Text>
              </Box>
              <Icon as={LockClosedIcon} boxSize={5} color="brand.500" />
            </HStack>

            {selectedFee ? (
              <VStack align="stretch" spacing={5}>
                <Box bg={mutedBackground} borderRadius="xl" p={4}>
                  <Text fontSize="sm" color="gray.500">
                    Selected fee
                  </Text>
                  <HStack
                    justify="space-between"
                    align="start"
                    mt={1}
                    spacing={3}
                  >
                    <Text fontWeight="bold" color={brandText}>
                      {selectedFee.title}
                    </Text>
                    <Text fontWeight="bold" whiteSpace="nowrap">
                      {formatAsCurrency(selectedFee.amount_remaining)}
                    </Text>
                  </HStack>
                  <Button
                    variant="link"
                    colorScheme="brand"
                    size="sm"
                    mt={2}
                    onClick={() =>
                      document
                        .getElementById('fee-list')
                        ?.scrollIntoView({ behavior: 'smooth' })
                    }
                  >
                    Change selection
                  </Button>
                </Box>

                <FormControl isInvalid={!!webForm.errors.amount}>
                  <FormLabel htmlFor="payment-amount" mb={1}>
                    Amount to pay
                  </FormLabel>
                  <Input
                    id="payment-amount"
                    type="number"
                    min={1}
                    max={selectedFee.amount_remaining}
                    value={webForm.data.amount}
                    onChange={(event) =>
                      webForm.setValue('amount', Number(event.target.value))
                    }
                    size="lg"
                    borderRadius="lg"
                  />
                  <FormHelperText>
                    You can pay all or part of the outstanding{' '}
                    {formatAsCurrency(selectedFee.amount_remaining)}.
                  </FormHelperText>
                  <FormErrorMessage>{webForm.errors.amount}</FormErrorMessage>
                </FormControl>

                <Box>
                  <Text fontWeight="semibold" mb={3}>
                    Choose a payment method
                  </Text>
                  <RadioGroup
                    value={webForm.data.merchant}
                    onChange={(value) =>
                      webForm.setValue('merchant', value as PaymentMerchantType)
                    }
                  >
                    <VStack align="stretch" spacing={3}>
                      <PaymentMethodCard
                        value={DefaultPaymentMerchantType}
                        selected={
                          webForm.data.merchant === DefaultPaymentMerchantType
                        }
                        icon={CreditCardIcon}
                        title="Automated payment"
                        description="Pay securely online with your card or bank transfer."
                      />
                      <PaymentMethodCard
                        value={PaymentMerchantType.Manual}
                        selected={
                          webForm.data.merchant === PaymentMerchantType.Manual
                        }
                        icon={BanknotesIcon}
                        title="Manual bank transfer"
                        description="Transfer to the school account and submit your proof."
                      />
                    </VStack>
                  </RadioGroup>
                </Box>

                {webForm.data.merchant === PaymentMerchantType.Manual ? (
                  <Box>
                    <Divider mb={4} />
                    <Text fontWeight="semibold" mb={2}>
                      School bank accounts
                    </Text>
                    <BankAccountList
                      accounts={bankAccounts}
                      introText="Transfer the amount above to any account below. The next page lets you add your transfer details and proof."
                    />
                  </Box>
                ) : null}

                <Button
                  colorScheme="brand"
                  size="lg"
                  borderRadius="lg"
                  onClick={submit}
                  isLoading={webForm.processing}
                  isDisabled={selectedFee.status === 'paid'}
                  rightIcon={<Icon as={CheckIcon} />}
                >
                  {webForm.data.merchant === PaymentMerchantType.Manual
                    ? 'Continue to transfer details'
                    : 'Pay securely now'}
                </Button>
                <Text textAlign="center" fontSize="xs" color="gray.500">
                  Your payment will be matched to{' '}
                  {student.user?.full_name ?? 'this student'}.
                </Text>
              </VStack>
            ) : (
              <Alert status="success" borderRadius="xl">
                <AlertIcon />
                All listed fees have been paid. Thank you.
              </Alert>
            )}
          </Box>
        </SimpleGrid>
      </Box>
    </Box>
  );
}

function SummaryStat({
  label,
  value,
  emphasize = false,
}: {
  label: string;
  value: string;
  emphasize?: boolean;
}) {
  return (
    <Box bg="gray.50" borderRadius="lg" p={4}>
      <Text
        fontSize="xs"
        color="gray.500"
        textTransform="uppercase"
        letterSpacing="wide"
      >
        {label}
      </Text>
      <Text
        mt={1}
        fontWeight={emphasize ? 'bold' : 'semibold'}
        fontSize={emphasize ? 'lg' : 'md'}
      >
        {value}
      </Text>
    </Box>
  );
}

function FeeCard({
  fee,
  selected,
  onSelect,
  surfaceBackground,
  mutedBackground,
}: {
  fee: PublicFee;
  selected: boolean;
  onSelect(): void;
  surfaceBackground: string;
  mutedBackground: string;
}) {
  const isPaid = fee.status === 'paid';
  const statusColor = isPaid
    ? 'green'
    : fee.status === 'partial'
    ? 'orange'
    : 'brand';
  return (
    <Box
      as="button"
      type="button"
      onClick={onSelect}
      textAlign="left"
      w="full"
      bg={isPaid ? mutedBackground : surfaceBackground}
      border="2px solid"
      borderColor={selected ? 'brand.500' : 'gray.200'}
      borderRadius="xl"
      p={4}
      opacity={isPaid ? 0.78 : 1}
      cursor={isPaid ? 'not-allowed' : 'pointer'}
      disabled={isPaid}
      _hover={isPaid ? undefined : { borderColor: 'brand.300', shadow: 'sm' }}
      _focusVisible={{ outline: '3px solid', outlineColor: 'brand.200' }}
      aria-label={`${fee.title}, ${isPaid ? 'paid' : 'select to pay'}`}
    >
      <HStack justify="space-between" align="start" spacing={3}>
        <HStack align="start" spacing={3}>
          <Icon
            as={
              isPaid
                ? CheckCircleIcon
                : fee.status === 'partial'
                ? BanknotesIcon
                : CreditCardIcon
            }
            boxSize={6}
            color={`${statusColor}.500`}
            mt={0.5}
          />
          <Box>
            <Text fontWeight="bold">{fee.title}</Text>
            <Text fontSize="sm" color="gray.500" mt={1}>
              {fee.academic_session ?? 'Current period'}
              {fee.term ? ` · ${fee.term} term` : ''}
            </Text>
          </Box>
        </HStack>
        <Badge colorScheme={statusColor} whiteSpace="nowrap">
          {isPaid
            ? 'Paid'
            : fee.status === 'partial'
            ? 'Partially paid'
            : 'Outstanding'}
        </Badge>
      </HStack>
      <HStack justify="space-between" mt={4} pl={9}>
        <Text fontSize="sm" color="gray.500">
          {isPaid
            ? 'Paid in full'
            : fee.status === 'partial'
            ? `${formatAsCurrency(fee.amount_paid)} paid`
            : 'Amount due'}
        </Text>
        <Text fontWeight="bold" color={isPaid ? 'green.600' : 'gray.800'}>
          {formatAsCurrency(isPaid ? fee.amount : fee.amount_remaining)}
        </Text>
      </HStack>
    </Box>
  );
}

function PaymentMethodCard({
  value,
  selected,
  icon,
  title,
  description,
}: {
  value: string;
  selected: boolean;
  icon: React.ElementType;
  title: string;
  description: string;
}) {
  return (
    <Box
      as="label"
      display="block"
      border="2px solid"
      borderColor={selected ? 'brand.500' : 'gray.200'}
      bg={selected ? 'brand.50' : 'transparent'}
      borderRadius="xl"
      p={4}
      cursor="pointer"
      _hover={{ borderColor: 'brand.300' }}
    >
      <HStack align="start" spacing={3}>
        <Radio value={value} mt={1} colorScheme="brand" />
        <Icon as={icon} boxSize={6} color="brand.500" />
        <Box>
          <Text fontWeight="semibold">{title}</Text>
          <Text fontSize="sm" color="gray.500" mt={1}>
            {description}
          </Text>
        </Box>
      </HStack>
    </Box>
  );
}
