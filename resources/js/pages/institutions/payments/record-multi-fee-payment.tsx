import React, { useState } from 'react';
import { Checkbox, Divider, FormControl, Text, VStack } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import useWebForm from '@/hooks/use-web-form';
import { formatAsCurrency, preventNativeSubmit } from '@/util/util';
import { Inertia } from '@inertiajs/inertia';
import {
  Classification,
  ClassificationGroup,
  Fee,
  ReceiptType,
} from '@/types/models';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import CenteredBox from '@/components/centered-box';
import { FormButton } from '@/components/buttons';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import FormControlBox from '@/components/forms/form-control-box';
import EnumSelect from '@/components/dropdown-select/enum-select';
import { SelectOptionType, TermType } from '@/types/types';
import ReceiptTypeSelect from '@/components/selectors/receipt-type-select';
import AcademicSessionSelect from '@/components/selectors/academic-session-select';
import InputForm from '@/components/forms/input-form';
import StudentSelect from '@/components/selectors/student-select';
import useSharedProps from '@/hooks/use-shared-props';
import ClassificationSelect from '@/components/selectors/classification-select';
import ClassificationGroupSelect from '@/components/selectors/classification-group-select';

interface Props {
  fees: Fee[];
  receiptTypes: ReceiptType[];
  classificationGroups: ClassificationGroup[];
  classifications: Classification[];
}

export default function RecordMultiFeePayment({
  fees,
  receiptTypes,
  classificationGroups,
  classifications,
}: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const { currentAcademicSessionId, currentTerm } = useSharedProps();
  const webForm = useWebForm({
    title: '',
    receipt_type_id: '',
    term: currentTerm,
    academic_session_id: currentAcademicSessionId,
    fee_ids: [] as number[],
    user_id: {} as SelectOptionType<number>,
    transaction_reference: '',
    method: '',
    classification_id: '', // Not used, just to keep track
    classification_group_id: '', // Also, not used, just to keep track
  });

  const submit = async () => {
    const res = await webForm.submit((data, web) =>
      web.post(instRoute('fee-payments.multi-fee-payment.store'), {
        ...data,
        user_id: data.user_id.value,
      })
    );
    if (!handleResponseToast(res)) return;
    Inertia.visit(instRoute('fee-payments.index'));
  };

  function getTotalAmount() {
    var amount = 0;
    webForm.data.fee_ids.map((id) => {
      let fee = fees.find((fee) => fee.id === id);
      amount += fee?.amount ?? 0;
    });
    return amount;
  }

  return (
    <DashboardLayout>
      <CenteredBox>
        <Slab>
          <SlabHeading title={`Multi Fee Payment`} />
          <SlabBody>
            <VStack
              spacing={4}
              as={'form'}
              onSubmit={preventNativeSubmit(submit)}
              align={'stretch'}
            >
              <FormControlBox
                form={webForm as any}
                title="Fee Category"
                formKey="receipt_type_id"
              >
                <ReceiptTypeSelect
                  selectValue={webForm.data.receipt_type_id}
                  isMulti={false}
                  isClearable={true}
                  onChange={(e: any) =>
                    webForm.setValue('receipt_type_id', e?.value)
                  }
                  receiptTypes={receiptTypes}
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
                />
              </FormControlBox>
              <FormControlBox form={webForm as any} title="Term" formKey="term">
                <EnumSelect
                  enumData={TermType}
                  selectValue={webForm.data.term}
                  isMulti={false}
                  isClearable={true}
                  onChange={(e: any) => webForm.setValue('term', e?.value)}
                />
              </FormControlBox>
              <FormControlBox
                form={webForm as any}
                title="Class Group"
                formKey="classification_group_id"
              >
                <ClassificationGroupSelect
                  selectValue={webForm.data.classification_group_id}
                  isMulti={false}
                  isClearable={true}
                  classificationGroups={classificationGroups}
                  onChange={(e: any) =>
                    webForm.setData({
                      ...webForm.data,
                      classification_group_id: e?.value,
                      classification_id: '',
                    })
                  }
                />
              </FormControlBox>
              {webForm.data.classification_group_id && (
                <FormControlBox
                  form={webForm as any}
                  title="Class"
                  formKey="classification_id"
                >
                  <ClassificationSelect
                    selectValue={webForm.data.classification_id}
                    classifications={classifications}
                    classGroupId={webForm.data.classification_group_id}
                    isMulti={false}
                    isClearable={true}
                    onChange={(e: any) =>
                      webForm.setValue('classification_id', e?.value)
                    }
                  />
                </FormControlBox>
              )}
              <FormControlBox
                form={webForm as any}
                title="Student"
                formKey="student"
              >
                <StudentSelect
                  value={webForm.data.user_id}
                  isMulti={false}
                  isClearable={true}
                  valueKey={'user_id'}
                  onChange={(e: any) => webForm.setValue('user_id', e)}
                  classification={parseInt(webForm.data.classification_id)}
                  required
                />
              </FormControlBox>
              <InputForm
                form={webForm as any}
                formKey="transaction_reference"
                title="Transaction Id / Receipt No / Teller No etc..."
              />
              <Divider />
              <Text my={2} fontWeight={'bold'}>
                {formatAsCurrency(getTotalAmount())}
              </Text>
              <FeeBoxSelector
                fees={fees}
                selected_fee_ids={webForm.data.fee_ids}
                updateSelection={(feeIds: number[]) =>
                  webForm.setValue('fee_ids', feeIds)
                }
                receipt_type_id={parseInt(webForm.data.receipt_type_id)}
                classification_group_id={parseInt(
                  webForm.data.classification_group_id
                )}
                classification_id={parseInt(webForm.data.classification_id)}
              />
              <FormControl>
                <FormButton isLoading={webForm.processing} />
              </FormControl>
            </VStack>
          </SlabBody>
        </Slab>
      </CenteredBox>
    </DashboardLayout>
  );
}

function FeeBoxSelector({
  fees,
  selected_fee_ids,
  updateSelection,
  receipt_type_id,
  classification_group_id,
  classification_id,
}: {
  fees: Fee[];
  selected_fee_ids: number[];
  updateSelection: (feeIds: number[]) => void;
  receipt_type_id?: number;
  classification_group_id?: number;
  classification_id?: number;
}) {
  let filteredFees = fees.filter((fee) => {
    if (
      classification_group_id &&
      fee.classification_group_id &&
      fee.classification_group_id !== classification_group_id
    ) {
      return false;
    }
    if (
      classification_id &&
      fee.classification_id &&
      fee.classification_id !== classification_id
    ) {
      return false;
    }
    if (receipt_type_id && fee.receipt_type_id !== receipt_type_id) {
      return false;
    }
    return true;
  });
  const allSelected = filteredFees.length === selected_fee_ids.length;
  return (
    <VStack spacing={2} align={'stretch'}>
      <Checkbox
        isChecked={allSelected}
        onChange={(e) => {
          updateSelection(
            e.currentTarget.checked ? filteredFees.map((fee) => fee.id) : []
          );
        }}
        size={'md'}
        colorScheme="brand"
      >
        Select All
      </Checkbox>
      <Divider my={2} />
      {filteredFees.map((fee) => {
        return (
          <Checkbox
            key={fee.id}
            isChecked={selected_fee_ids.includes(fee.id)}
            onChange={(e) => {
              if (e.currentTarget.checked) {
                selected_fee_ids.push(fee.id);
              } else {
                selected_fee_ids = selected_fee_ids.filter(
                  (item) => item !== fee.id
                );
              }
              updateSelection(selected_fee_ids);
            }}
            size={'md'}
            colorScheme="brand"
          >
            {fee.title} ({formatAsCurrency(fee.amount)})
          </Checkbox>
        );
      })}
    </VStack>
  );
}
