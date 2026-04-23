import React, { useMemo, useState } from 'react';
import {
  Alert,
  AlertDescription,
  AlertIcon,
  Badge,
  Box,
  Button,
  HStack,
  Icon,
  Image,
  Input,
  Link,
  SimpleGrid,
  Text,
  VStack,
} from '@chakra-ui/react';
import {
  ArrowTopRightOnSquareIcon,
  PencilSquareIcon,
  PhotoIcon,
} from '@heroicons/react/24/outline';
import { BankAccount, ManualPayment } from '@/types/models';
import { Div } from '@/components/semantic';
import CenteredBox from '@/components/centered-box';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { formatAsCurrency } from '@/util/util';
import startCase from 'lodash/startCase';
import FormControlBox from '@/components/forms/form-control-box';
import useWebForm from '@/hooks/use-web-form';
import useInstitutionRoute from '@/hooks/use-institution-route';
import useMyToast from '@/hooks/use-my-toast';
import BankAccountList from '@/components/payments/bank-account-list';
import BankAccountSelect from '@/components/selectors/bank-account-select';
import InputForm from '@/components/forms/input-form';
import { FormButton } from '@/components/buttons';
import useSharedProps from '@/hooks/use-shared-props';

interface Props {
  manualPayment: ManualPayment;
  bankAccounts: BankAccount[];
  payableDetails?: PaymentEntityDetails | null;
  paymentableDetails?: PaymentEntityDetails | null;
}

export default function ManualPaymentPending({
  manualPayment,
  bankAccounts,
  payableDetails,
  paymentableDetails,
}: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const { currentUser } = useSharedProps();
  const [currentPayment, setCurrentPayment] = useState(manualPayment);

  const isPending = currentPayment.status === 'pending';
  const [isEditing, setIsEditing] = useState(
    !manualPayment.bank_account_id && isPending
  );

  const webForm = useWebForm({
    bank_account_id:
      manualPayment.bank_account_id ??
      (bankAccounts.length === 1 ? bankAccounts[0].id : ''),
    depositor_name: manualPayment.depositor_name ?? '',
    paid_at:
      manualPayment.paid_at?.slice(0, 10) ??
      new Date().toISOString().slice(0, 10),
    payment_proof: null as File | null,
    note: manualPayment.payload?.note ?? '',
  });

  const hasBankSelection = Boolean(currentPayment.bank_account_id);
  const selectedBankAccount = useMemo(
    () =>
      bankAccounts.find(
        (bankAccount) =>
          `${bankAccount.id}` === `${currentPayment.bank_account_id}`
      ) ?? currentPayment.bank_account,
    [bankAccounts, currentPayment]
  );
  const proofIsImage = Boolean(
    currentPayment.proof_url?.match(/\.(jpg|jpeg|png|gif|webp)$/i)
  );

  async function submit() {
    const res = await webForm.submit((data, web) => {
      const formData = new FormData();
      formData.append('bank_account_id', `${data.bank_account_id ?? ''}`);
      formData.append('depositor_name', data.depositor_name ?? '');
      formData.append('paid_at', data.paid_at ?? '');
      formData.append('note', data.note ?? '');
      if (data.payment_proof) {
        formData.append('payment_proof', data.payment_proof);
      }

      return web.post(
        instRoute('manual-payments.pending.update', [currentPayment.reference]),
        formData
      );
    });

    if (!handleResponseToast(res)) return;

    setCurrentPayment(res.data.manualPayment);
    webForm.setData({
      bank_account_id: res.data.manualPayment.bank_account_id ?? '',
      depositor_name: res.data.manualPayment.depositor_name ?? '',
      paid_at: res.data.manualPayment.paid_at?.slice(0, 10) ?? '',
      payment_proof: null,
      note: res.data.manualPayment.payload?.note ?? '',
    });
    setIsEditing(false);
  }

  function canEdit() {
    return (
      currentUser.id === manualPayment.user_id &&
      isPending &&
      hasBankSelection &&
      !isEditing
    );
  }

  return (
    <Div background={'brand.50'} minH={'100vh'} py={10} px={4}>
      <CenteredBox maxW="820px">
        <Slab>
          <SlabHeading title="Manual Payment" />
          <SlabBody>
            <VStack align="stretch" spacing={5}>
              <Alert
                status={
                  isPending
                    ? 'info'
                    : currentPayment.status === 'confirmed'
                    ? 'success'
                    : 'error'
                }
                borderRadius="lg"
                bg="white"
                border="1px solid"
                borderColor="gray.200"
              >
                <AlertIcon />
                <AlertDescription>
                  {isPending
                    ? 'Your payment is awaiting confirmation from the institution.'
                    : `This manual payment has been ${currentPayment.status}.`}
                </AlertDescription>
              </Alert>

              <SimpleGrid columns={{ base: 1, md: 3 }} spacing={4}>
                <InfoCard
                  label="Amount"
                  value={formatAsCurrency(currentPayment.amount)}
                />
                <InfoCard
                  label="Purpose"
                  value={startCase(currentPayment.purpose)}
                />
                <InfoCard
                  label="Status"
                  value={
                    <Badge
                      colorScheme={
                        currentPayment.status === 'confirmed'
                          ? 'green'
                          : currentPayment.status === 'cancelled'
                          ? 'red'
                          : 'yellow'
                      }
                    >
                      {startCase(currentPayment.status)}
                    </Badge>
                  }
                />
              </SimpleGrid>

              {payableDetails || paymentableDetails ? (
                <SimpleGrid columns={{ base: 1, xl: 2 }} spacing={4}>
                  {payableDetails ? (
                    <EntityDetailsCard details={payableDetails} />
                  ) : null}
                  {paymentableDetails ? (
                    <EntityDetailsCard details={paymentableDetails} />
                  ) : null}
                </SimpleGrid>
              ) : null}

              <Box
                bg="white"
                border="1px solid"
                borderColor="gray.200"
                borderRadius="lg"
                p={5}
                shadow="sm"
              >
                <HStack
                  justify="space-between"
                  align={{ base: 'start', md: 'center' }}
                  flexDir={{ base: 'column', md: 'row' }}
                  spacing={3}
                  mb={4}
                >
                  <VStack align="start" spacing={1}>
                    <Text fontSize="lg" fontWeight="semibold" color="gray.800">
                      Payment Details
                    </Text>
                    <Text fontSize="sm" color="gray.500">
                      Reference: {currentPayment.reference}
                    </Text>
                  </VStack>
                  {canEdit() && (
                    <Button
                      size="sm"
                      leftIcon={<Icon as={PencilSquareIcon} />}
                      onClick={() => setIsEditing(true)}
                    >
                      Edit
                    </Button>
                  )}
                </HStack>

                {isEditing ? (
                  <VStack align="stretch" spacing={4}>
                    <FormControlBox
                      form={webForm as any}
                      title="Bank Paid To"
                      formKey="bank_account_id"
                      isRequired
                    >
                      <BankAccountSelect
                        selectValue={webForm.data.bank_account_id}
                        bankAccounts={bankAccounts}
                        isMulti={false}
                        isClearable={true}
                        onChange={(e: any) =>
                          webForm.setValue('bank_account_id', e?.value ?? '')
                        }
                      />
                    </FormControlBox>
                    <InputForm
                      form={webForm as any}
                      title="Depositor Name [Optional]"
                      formKey="depositor_name"
                    />
                    <InputForm
                      form={webForm as any}
                      title="Date Paid [Optional]"
                      formKey="paid_at"
                      type="date"
                    />
                    <InputForm
                      form={webForm as any}
                      title="Note [Optional]"
                      formKey="note"
                    />
                    <FormControlBox
                      form={webForm as any}
                      title="Transaction Screenshot [Optional]"
                      formKey="payment_proof"
                    >
                      <Input
                        type="file"
                        accept="image/*,application/pdf"
                        onChange={(e) =>
                          webForm.setValue(
                            'payment_proof',
                            e.currentTarget.files?.[0] ?? null
                          )
                        }
                      />
                      <Text fontSize="sm" color="gray.500" mt={1}>
                        Upload an image or PDF proof if you have one.
                      </Text>
                    </FormControlBox>
                    <HStack justify="flex-end">
                      {hasBankSelection ? (
                        <Button
                          variant="ghost"
                          onClick={() => setIsEditing(false)}
                        >
                          Cancel
                        </Button>
                      ) : null}
                      <FormButton
                        title="Save Details"
                        isLoading={webForm.processing}
                        onClick={submit}
                      />
                    </HStack>
                  </VStack>
                ) : (
                  <SimpleGrid columns={{ base: 1, md: 2 }} spacing={4}>
                    <InfoCard
                      label="Bank Paid To"
                      value={
                        selectedBankAccount
                          ? `${selectedBankAccount.bank_name} - ${selectedBankAccount.account_number}`
                          : 'Not selected'
                      }
                    />
                    <InfoCard
                      label="Depositor Name"
                      value={currentPayment.depositor_name ?? 'Not provided'}
                    />
                    <InfoCard
                      label="Date Paid"
                      value={
                        currentPayment.paid_at
                          ? new Date(
                              currentPayment.paid_at
                            ).toLocaleDateString()
                          : 'Not provided'
                      }
                    />
                    <InfoCard
                      label="Note"
                      value={currentPayment.payload?.note ?? 'Not provided'}
                    />
                  </SimpleGrid>
                )}
              </Box>

              {currentPayment.proof_url ? (
                <Box
                  bg="white"
                  border="1px solid"
                  borderColor="gray.200"
                  borderRadius="lg"
                  p={5}
                  shadow="sm"
                >
                  <HStack justify="space-between" align="center" mb={4}>
                    <HStack spacing={3}>
                      <Icon as={PhotoIcon} color="brand.500" boxSize={5} />
                      <Text
                        fontSize="lg"
                        fontWeight="semibold"
                        color="gray.800"
                      >
                        Uploaded Proof
                      </Text>
                    </HStack>
                    <Link
                      href={currentPayment.proof_url}
                      target="_blank"
                      color="brand.500"
                      display="inline-flex"
                      alignItems="center"
                      gap={1}
                    >
                      Open
                      <Icon as={ArrowTopRightOnSquareIcon} boxSize={4} />
                    </Link>
                  </HStack>

                  {proofIsImage ? (
                    <Image
                      src={currentPayment.proof_url}
                      alt="Uploaded payment proof"
                      w="full"
                      maxH="420px"
                      objectFit="contain"
                      borderRadius="md"
                      border="1px solid"
                      borderColor="gray.200"
                    />
                  ) : (
                    <Box
                      border="1px dashed"
                      borderColor="gray.300"
                      borderRadius="md"
                      p={6}
                      textAlign="center"
                    >
                      <Text color="gray.600">
                        Proof uploaded successfully. Open the file to view it.
                      </Text>
                    </Box>
                  )}
                </Box>
              ) : null}

              {currentPayment.review_note ? (
                <Box
                  bg="white"
                  border="1px solid"
                  borderColor="gray.200"
                  borderRadius="lg"
                  p={5}
                  shadow="sm"
                >
                  <Text fontSize="sm" color="gray.500" mb={1}>
                    Review Note
                  </Text>
                  <Text color="gray.700">{currentPayment.review_note}</Text>
                </Box>
              ) : null}

              <BankAccountList
                accounts={bankAccounts}
                introText="Institution bank accounts available for this payment."
              />
            </VStack>
          </SlabBody>
        </Slab>
      </CenteredBox>
    </Div>
  );
}

function InfoCard({ label, value }: { label: string; value: React.ReactNode }) {
  return (
    <Box
      bg="white"
      border="1px solid"
      borderColor="gray.200"
      borderRadius="lg"
      p={4}
      shadow="sm"
    >
      <Text fontSize="sm" color="gray.500" mb={1}>
        {label}
      </Text>
      <Text color="gray.800" fontWeight="medium">
        {value}
      </Text>
    </Box>
  );
}

interface PaymentEntityAttribute {
  label: string;
  value: string | number;
}

interface PaymentEntityDetails {
  label: string;
  title: string;
  subtitle?: string;
  attributes: PaymentEntityAttribute[];
}

function EntityDetailsCard({ details }: { details: PaymentEntityDetails }) {
  return (
    <Box
      bg="white"
      border="1px solid"
      borderColor="gray.200"
      borderRadius="lg"
      p={5}
      shadow="sm"
    >
      <Text fontSize="sm" color="gray.500" mb={1}>
        {details.label}
      </Text>
      <Text fontSize="lg" fontWeight="semibold" color="gray.800">
        {details.title}
      </Text>
      {details.subtitle ? (
        <Text fontSize="sm" color="gray.500" mb={4}>
          {details.subtitle}
        </Text>
      ) : null}

      <VStack align="stretch" spacing={3}>
        {details.attributes.map((attribute) => (
          <Box key={`${details.label}-${attribute.label}`}>
            <Text fontSize="xs" color="gray.500" textTransform="uppercase">
              {attribute.label}
            </Text>
            <Text color="gray.700">{attribute.value}</Text>
          </Box>
        ))}
      </VStack>
    </Box>
  );
}
