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
import useWebForm from '@/hooks/use-web-form';
import { preventNativeSubmit } from '@/util/util';
import useMyToast from '@/hooks/use-my-toast';
import { Inertia } from '@inertiajs/inertia';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { formatAsCurrency } from '@/util/util';

interface Props {
  classifications: Classification[];
  publicationBilling?: PublicationBilling | null;
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
}: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const insufficientBalanceModal = useDisclosure();
  const [insufficientBalance, setInsufficientBalance] =
    useState<PublicationBilling | null>(null);

  //== Set up the state to track which checkboxes are selected
  const [selectedClassifications, setSelectedClassifications] = useState<
    number[]
  >(classifications.map((classification) => classification.id));

  const webForm = useWebForm({});

  const fundingUrl = (billing?: PublicationBilling | null) =>
    billing?.funding_url ??
    `${instRoute('fundings.create')}?amount=${billing?.amount_needed ?? ''}`;

  const goToFunding = (billing?: PublicationBilling | null) => {
    window.location.href = fundingUrl(billing);
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
