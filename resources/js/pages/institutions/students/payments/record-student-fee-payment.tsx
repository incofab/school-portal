import React from 'react';
import { Divider, Alert, AlertIcon, Text, VStack } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import useWebForm from '@/hooks/use-web-form';
import { BankAccount, Fee, Student } from '@/types/models';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import CenteredBox from '@/components/centered-box';
import { FormButton } from '@/components/buttons';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import FormControlBox from '@/components/forms/form-control-box';
import EnumSelect from '@/components/dropdown-select/enum-select';
import { PaymentMerchantType, SelectOptionType, TermType } from '@/types/types';
import AcademicSessionSelect from '@/components/selectors/academic-session-select';
import useSharedProps from '@/hooks/use-shared-props';
import FeeSelect from '@/components/selectors/fee-select';
import InputForm from '@/components/forms/input-form';
import MySelect from '@/components/dropdown-select/my-select';
import { Inertia } from '@inertiajs/inertia';
import BankAccountList from '@/components/payments/bank-account-list';
import { formatAsCurrency } from '@/util/util';

interface Props {
  student: Student;
  fees: Fee[];
  bankAccounts: BankAccount[];
}

export default function RecordStudentFeePayment({
  student,
  fees,
  bankAccounts,
}: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const {
    currentAcademicSessionId,
    currentTerm,
    currentUser,
    lockTermSession,
  } = useSharedProps();

  const webForm = useWebForm({
    term: currentTerm,
    academic_session_id: currentAcademicSessionId,
    fee_id: '',
    amount: 0,
    merchant: PaymentMerchantType.Monnify,
  });

  const paymentOptions: SelectOptionType<string>[] = [
    {
      label: 'Instant Payment',
      value: PaymentMerchantType.Monnify,
    },
    {
      label: `Pay from Wallet (${formatAsCurrency(currentUser.wallet)})`,
      value: PaymentMerchantType.UserWallet,
    },
    {
      label: 'Manual Payment',
      value: PaymentMerchantType.Manual,
    },
  ];

  const submit = async () => {
    const merchant = webForm.data.merchant;
    const res = await webForm.submit((data, web) =>
      web.post(instRoute('students.fee-payments.store', [student.id]), {
        ...data,
      })
    );

    if (!handleResponseToast(res)) return;

    if (merchant === PaymentMerchantType.UserWallet) {
      Inertia.visit(instRoute('students.receipts.index', [student.id]));
      return;
    }
    if (merchant === PaymentMerchantType.Manual) {
      window.location.href = res.data.redirect_url;
      return;
    }
    window.location.href = res.data.authorization_url;
  };

  return (
    <DashboardLayout>
      <CenteredBox>
        <Slab>
          <SlabHeading title={`Pay Fees`} />
          <SlabBody>
            <VStack spacing={4} align={'stretch'}>
              <FormControlBox
                form={webForm as any}
                title="Fee Category"
                formKey="receipt_type_id"
              >
                <FeeSelect
                  selectValue={webForm.data.fee_id}
                  isMulti={false}
                  isClearable={true}
                  onChange={(e: any) => {
                    const feeId = e.value;
                    const fee = fees.find(
                      (item) => `${item.id}` === `${feeId}`
                    );
                    webForm.setData({
                      ...webForm.data,
                      fee_id: feeId,
                      amount: fee?.amount ?? 0,
                    });
                  }}
                  fees={fees}
                  required
                />
              </FormControlBox>
              <FormControlBox
                form={webForm as any}
                title="Academic Session"
                formKey="academic_session_id"
              >
                <AcademicSessionSelect
                  selectValue={webForm.data.academic_session_id}
                  isMulti={false}
                  isClearable={true}
                  onChange={(e: any) =>
                    webForm.setValue('academic_session_id', e?.value)
                  }
                  isDisabled={lockTermSession}
                />
              </FormControlBox>
              <FormControlBox form={webForm as any} title="Term" formKey="term">
                <EnumSelect
                  enumData={TermType}
                  selectValue={webForm.data.term}
                  isMulti={false}
                  isClearable={true}
                  onChange={(e: any) => webForm.setValue('term', e?.value)}
                  isDisabled={lockTermSession}
                />
              </FormControlBox>
              <InputForm
                form={webForm as any}
                title={'Amount'}
                formKey={'amount'}
              />
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
                    introText="Send the transfer to any of our bank accounts below, then
                      continue to submit your payment proof."
                  />
                </>
              ) : null}
              <FormButton
                isLoading={webForm.processing}
                title={
                  webForm.data.merchant === PaymentMerchantType.Manual
                    ? 'Continue'
                    : webForm.data.merchant === PaymentMerchantType.UserWallet
                    ? 'Pay from Wallet'
                    : 'Pay Now'
                }
                onClick={submit}
              />
            </VStack>
          </SlabBody>
        </Slab>
      </CenteredBox>
    </DashboardLayout>
  );
}
