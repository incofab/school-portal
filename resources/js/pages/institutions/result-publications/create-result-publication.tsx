import { Classification } from '@/types/models';
import React, { useState } from 'react';

import {
  Alert,
  AlertDescription,
  AlertIcon,
  AlertTitle,
  Button,
  Checkbox,
  FormControl,
  HStack,
  Modal,
  ModalBody,
  ModalCloseButton,
  ModalContent,
  ModalFooter,
  ModalHeader,
  ModalOverlay,
  Text,
  VStack,
  useDisclosure,
} from '@chakra-ui/react';

import DashboardLayout from '@/layout/dashboard-layout';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import CenteredBox from '@/components/centered-box';
import { FormButton } from '@/components/buttons';
import FormControlBox from '@/components/forms/form-control-box';
import useWebForm from '@/hooks/use-web-form';
import { preventNativeSubmit } from '@/util/util';
import useMyToast from '@/hooks/use-my-toast';
import { Inertia } from '@inertiajs/inertia';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { formatAsCurrency } from '@/util/util';
import AcademicSessionSelect from '@/components/selectors/academic-session-select';
import EnumSelect from '@/components/dropdown-select/enum-select';
import { TermType } from '@/types/types';
import useSharedProps from '@/hooks/use-shared-props';

interface Props {
  classifications: Classification[];
  publicationBilling?: PublicationBilling | null;
  academic_session_id?: number;
  term?: TermType;
}

interface PublicationBilling {
  amount_to_pay: number;
  wallet_balance: number;
  amount_needed: number;
  has_insufficient_balance: boolean;
  can_get_loan: boolean;
  results_to_publish_count: number;
  num_of_students: number;
  payment_structure: string;
  unit_amount: number;
  funding_url: string;
}

export default function CreateResultPublications({
  classifications,
  publicationBilling,
  academic_session_id: academicSessionId,
  term,
}: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const { currentAcademicSessionId, currentTerm, lockTermSession } =
    useSharedProps();
  const insufficientBalanceModal = useDisclosure();
  const [insufficientBalance, setInsufficientBalance] =
    useState<PublicationBilling | null>(null);

  //== Set up the state to track which checkboxes are selected
  const [selectedClassifications, setSelectedClassifications] = useState<
    number[]
  >(classifications.map((classification) => classification.id));

  const webForm = useWebForm({
    academic_session_id: academicSessionId ?? currentAcademicSessionId,
    term: (term ?? currentTerm) as TermType | null,
  });

  function reloadWithSelection(
    academicSessionValue: number | null,
    termValue: TermType | null
  ) {
    const nextUrl = new URL(
      instRoute('result-publications.create'),
      window.location.origin
    );

    if (academicSessionValue) {
      nextUrl.searchParams.set(
        'academic_session_id',
        String(academicSessionValue)
      );
    }

    if (termValue) {
      nextUrl.searchParams.set('term', termValue);
    }

    Inertia.visit(nextUrl.toString(), {
      preserveScroll: true,
      preserveState: false,
    });
  }

  const goToFunding = (billing?: PublicationBilling | null) => {
    if (billing?.funding_url) {
      window.location.href = billing.funding_url;
      return;
    } else {
      Inertia.visit(
        `${instRoute('fundings.create')}?amount=${billing?.amount_needed ?? ''}`
      );
    }
  };

  //== Handle the checkbox change event
  const handleCheckboxChange = (event: React.ChangeEvent<HTMLInputElement>) => {
    const classificationId = Number(event.target.value); // Ensure the ID is treated as a number

    if (event.target.checked) {
      setSelectedClassifications((prev) => [...prev, classificationId]);
    } else {
      setSelectedClassifications((prev) =>
        prev.filter((id) => id !== classificationId)
      );
    }
  };

  const submit = async () => {
    const res = await webForm.submit((data, web) => {
      const postData = {
        classifications: selectedClassifications,
        academic_session_id: data.academic_session_id,
        term: data.term,
      };

      return web.post(instRoute('result-publications.store'), postData);
    });
    if (!res.ok && res.data?.insufficient_balance) {
      setInsufficientBalance(res.data.billing);
      insufficientBalanceModal.onOpen();
      return;
    }

    if (!handleResponseToast(res)) {
      return;
    }
    Inertia.visit(instRoute('dashboard'));
  };

  return (
    <DashboardLayout>
      <CenteredBox>
        <Slab>
          <SlabHeading title="Result Publication" />

          <SlabBody>
            <VStack
              spacing={4}
              align={'stretch'}
              as={'form'}
              onSubmit={preventNativeSubmit(submit)}
            >
              {publicationBilling?.has_insufficient_balance && (
                <Alert status="warning" borderRadius="md" alignItems="start">
                  <AlertIcon />
                  <VStack align={'stretch'} spacing={2} flex={1}>
                    <AlertTitle>Fund your wallet before publishing</AlertTitle>
                    <AlertDescription>
                      Publishing these results requires{' '}
                      {formatAsCurrency(publicationBilling.amount_to_pay)}. Your
                      credit wallet balance is{' '}
                      {formatAsCurrency(publicationBilling.wallet_balance)}, so
                      you need{' '}
                      {formatAsCurrency(publicationBilling.amount_needed)} more.
                    </AlertDescription>
                    <HStack>
                      <Button
                        colorScheme="brand"
                        size="sm"
                        onClick={() => goToFunding(publicationBilling)}
                      >
                        Pay Now
                      </Button>
                    </HStack>
                  </VStack>
                </Alert>
              )}

              <FormControlBox
                form={webForm as any}
                title="Academic Session"
                formKey="academic_session_id"
              >
                <AcademicSessionSelect
                  selectValue={webForm.data.academic_session_id}
                  isMulti={false}
                  isClearable={false}
                  required
                  isDisabled={lockTermSession}
                  onChange={(e: any) => {
                    const nextAcademicSessionId = e?.value ?? null;
                    webForm.setValue(
                      'academic_session_id',
                      nextAcademicSessionId
                    );
                    reloadWithSelection(
                      nextAcademicSessionId,
                      webForm.data.term
                    );
                  }}
                />
              </FormControlBox>

              <FormControlBox form={webForm as any} title="Term" formKey="term">
                <EnumSelect
                  enumData={TermType}
                  selectValue={webForm.data.term}
                  isMulti={false}
                  isClearable={false}
                  required
                  isDisabled={lockTermSession}
                  onChange={(e: any) => {
                    const nextTerm = e?.value ?? null;
                    webForm.setValue('term', nextTerm);
                    reloadWithSelection(
                      webForm.data.academic_session_id,
                      nextTerm
                    );
                  }}
                />
              </FormControlBox>

              <Text>Select classes whose results should be published.</Text>
              {classifications.map((classification) => (
                <FormControl key={classification.id}>
                  <Checkbox
                    isChecked={selectedClassifications.includes(
                      classification.id
                    )}
                    onChange={handleCheckboxChange}
                    value={classification.id}
                    size={'md'}
                    colorScheme="brand"
                  >
                    {classification.title}
                  </Checkbox>
                </FormControl>
              ))}

              <FormControl>
                <FormButton isLoading={webForm.processing} />
              </FormControl>
            </VStack>
          </SlabBody>
        </Slab>
      </CenteredBox>

      <Modal
        isOpen={insufficientBalanceModal.isOpen}
        onClose={insufficientBalanceModal.onClose}
        isCentered
      >
        <ModalOverlay />
        <ModalContent>
          <ModalHeader>Insufficient wallet balance</ModalHeader>
          <ModalCloseButton />
          <ModalBody>
            <VStack align={'stretch'} spacing={3}>
              <Text>
                Your wallet does not have enough credit to publish these
                results.
              </Text>
              <Text>
                Amount required:{' '}
                <strong>
                  {formatAsCurrency(insufficientBalance?.amount_to_pay)}
                </strong>
              </Text>
              <Text>
                Current balance:{' '}
                <strong>
                  {formatAsCurrency(insufficientBalance?.wallet_balance)}
                </strong>
              </Text>
              <Text>
                Amount to fund:{' '}
                <strong>
                  {formatAsCurrency(insufficientBalance?.amount_needed)}
                </strong>
              </Text>
            </VStack>
          </ModalBody>
          <ModalFooter>
            <Button
              colorScheme="brand"
              onClick={() => goToFunding(insufficientBalance)}
            >
              Pay
            </Button>
          </ModalFooter>
        </ModalContent>
      </Modal>
    </DashboardLayout>
  );
}
