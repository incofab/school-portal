import { Div } from '@/components/semantic';
import {
  Alert,
  AlertIcon,
  Button,
  Icon,
  Text,
  useColorModeValue,
  VStack,
} from '@chakra-ui/react';
import React from 'react';
import { ArrowDownIcon } from '@heroicons/react/24/solid';
import { AdmissionApplication, BankAccount } from '@/types/models';
import { LabelText } from '@/components/result-helper-components';
import { formatAsCurrency } from '@/util/util';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { PaymentMerchantType } from '@/types/types';
import FormControlBox from '@/components/forms/form-control-box';
import MySelect from '@/components/dropdown-select/my-select';
import BankAccountList from '@/components/payments/bank-account-list';

interface Props {
  admissionApplication: AdmissionApplication;
  bankAccounts: BankAccount[];
}

export default function BuyAdmissionApplication({
  admissionApplication,
  bankAccounts,
}: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const webForm = useWebForm({
    merchant: PaymentMerchantType.Monnify,
  });

  const paymentOptions = [
    {
      label: 'Instant Payment',
      value: PaymentMerchantType.Monnify,
    },
    {
      label: 'Pay from Wallet (not available here)',
      value: PaymentMerchantType.UserWallet,
      isDisabled: true,
    },
    {
      label: 'Manual Payment',
      value: PaymentMerchantType.Manual,
    },
  ] as any;

  async function submit() {
    const res = await webForm.submit((data, web) =>
      web.post(
        instRoute('admission-forms.buy', [
          admissionApplication.admission_form_id!,
          admissionApplication.id,
        ]),
        data
      )
    );

    if (!handleResponseToast(res)) return;

    if (webForm.data.merchant === PaymentMerchantType.Manual) {
      window.location.href = res.data.redirect_url;
      return;
    }
    window.location.href = res.data.authorization_url;
  }

  return (
    <Div background={'brand.50'} height={'100vh'}>
      <Div
        rounded={'md'}
        border={'1px solid'}
        borderColor={'green.600'}
        bg={useColorModeValue('green.50', 'gray.900')}
        textAlign={'center'}
        p={8}
      >
        <Text fontSize={'3xl'}>Final Step on your Registration Process</Text>
        <br />
        <Div>
          <LabelText
            label="Name"
            text={`${admissionApplication.first_name} ${admissionApplication.last_name}`}
          />
          <LabelText
            label="Application Number"
            text={admissionApplication.application_no}
          />
        </Div>
        <Icon as={ArrowDownIcon} w={10} h={10} mt={6} />
        <Text mb={2} fontWeight={'semibold'} fontSize={'2xl'}>
          {admissionApplication.admission_form!.title}
        </Text>
        <Text mb={4} fontWeight={'bold'} fontSize={'3xl'} color={'green.500'}>
          {formatAsCurrency(admissionApplication.admission_form!.price)}
        </Text>
        <VStack maxW="420px" mx="auto" spacing={3}>
          <FormControlBox
            form={webForm as any}
            title="Payment Method"
            formKey="merchant"
          >
            <MySelect
              getOptions={() => paymentOptions}
              selectValue={webForm.data.merchant}
              isMulti={false}
              isClearable={false}
              onChange={(e: any) =>
                webForm.setValue(
                  'merchant',
                  e?.value ?? PaymentMerchantType.Monnify
                )
              }
            />
          </FormControlBox>
          {webForm.data.merchant === PaymentMerchantType.Manual ? (
            <>
              <BankAccountList
                accounts={bankAccounts}
                introText="Pay into any of our bank accounts below, then continue to the
                  next page to upload payment proof."
              />
            </>
          ) : null}
          <Button
            variant={'outline'}
            colorScheme="brand"
            mt={4}
            size={'sm'}
            onClick={submit}
            isLoading={webForm.processing}
          >
            {webForm.data.merchant === PaymentMerchantType.Manual
              ? 'Continue'
              : 'Pay Now'}
          </Button>
        </VStack>
      </Div>
    </Div>
  );
}
