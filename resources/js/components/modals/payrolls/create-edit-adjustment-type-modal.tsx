import React, { useState } from 'react';
import { Button, HStack, Input, Textarea, VStack } from '@chakra-ui/react';
import useWebForm from '@/hooks/use-web-form';
import GenericModal from '@/components/generic-modal';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { PayrollAdjustmentType } from '@/types/models';
import FormControlBox from '../../forms/form-control-box';
import { TransactionType } from '@/types/types';
import MySelect from '@/components/dropdown-select/my-select';

interface Props {
  isOpen: boolean;
  onClose(): void;
  onSuccess(): void;
  payrollAdjustmentType?: PayrollAdjustmentType;
  payrollAdjustmentTypes: PayrollAdjustmentType[];
}

export default function CreateEditAdjustmentTypeModal({
  isOpen,
  onSuccess,
  onClose,
  payrollAdjustmentType,
}: Props) {
  const { handleResponseToast, toastError } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const [usesFixedAmount, setusesFixedAmount] = useState<boolean>(
    Boolean(payrollAdjustmentType?.parent_id ?? true)
  );

  const webForm = useWebForm({
    type: payrollAdjustmentType?.type ?? '',
    title: payrollAdjustmentType?.title ?? '',
    description: payrollAdjustmentType?.description ?? '',
    parent_id: payrollAdjustmentType?.parent_id ?? '',
    percentage: payrollAdjustmentType?.percentage ?? '',
  });

  const isUpdate = payrollAdjustmentType?.id;

  const onSubmit = async () => {
    if (
      !usesFixedAmount &&
      (!webForm.data.parent_id || !webForm.data.percentage)
    ) {
      return toastError(
        'Supply both the parent and percentage for relative adjustment'
      );
    }
    const res = await webForm.submit((data, web) => {
      const postData = {
        ...data,
        ...(usesFixedAmount
          ? {
              parent_id: '',
              percentage: '',
            }
          : {}),
      };
      return isUpdate
        ? web.put(
            instRoute('payroll-adjustment-types.update', [
              payrollAdjustmentType,
            ]),
            postData
          )
        : web.post(instRoute('payroll-adjustment-types.store'), postData);
    });

    if (!handleResponseToast(res)) {
      return;
    }

    onClose();
    webForm.reset();
    onSuccess();
  };

  return (
    <GenericModal
      props={{ isOpen, onClose }}
      headerContent={`${isUpdate ? 'Update' : 'Create'} Adjustment Type`}
      bodyContent={
        <VStack spacing={3}>
          <FormControlBox
            form={webForm as any}
            title="Title"
            formKey="title"
            isRequired
          >
            <Input
              type="text"
              onChange={(e) => webForm.setValue('title', e.currentTarget.value)}
              value={webForm.data.title}
            />
          </FormControlBox>

          <FormControlBox form={webForm as any} title="Type" formKey="type">
            <MySelect
              selectValue={webForm.data.type}
              getOptions={() => [
                { label: 'Bonus', value: TransactionType.Credit },
                { label: 'Deduction', value: TransactionType.Debit },
              ]}
              isClearable={true}
              isMulti={false}
              onChange={(e: any) => webForm.setValue('type', e?.value)}
              required
            />
          </FormControlBox>
          {/* 
          // We do not intend to use relative Payroll Adjustment Type at this time
          // Need for it may arise tomorrow that's why it's not being removed entirely
          <ButtonSwitch
            items={[
              {
                value: true,
                label: 'Fixed Amount',
                onClick: () => setusesFixedAmount(true),
              },
              {
                value: false,
                label: 'Relative Amount',
                onClick: () => setusesFixedAmount(false),
              },
            ]}
            value={usesFixedAmount}
          /> 
          {usesFixedAmount && (
            <>
              <FormControlBox
                form={webForm as any}
                title="Parent Adjustment"
                formKey="parent_id"
                isRequired
              >
                <AdjustmentTypeSelect
                  selectValue={webForm.data.parent_id}
                  isMulti={false}
                  isClearable={true}
                  onChange={(e: any) => webForm.setValue('parent_id', e?.value)}
                  required
                  payrollAdjustmentTypes={payrollAdjustmentTypes}
                />
              </FormControlBox>

              {webForm.data.parent_id && (
                <FormControlBox
                  form={webForm as any}
                  title="Percentage"
                  formKey="percentage"
                >
                  <Input
                    type="number"
                    onChange={(e) =>
                      webForm.setValue('percentage', e.currentTarget.value)
                    }
                    value={webForm.data.percentage}
                  />
                </FormControlBox>
              )}
            </>
          )}
          */}

          <FormControlBox
            form={webForm as any}
            title="Description [optional]"
            formKey="description"
          >
            <Textarea
              value={webForm.data.description}
              onChange={(e) =>
                webForm.setValue('description', e.currentTarget.value)
              }
            ></Textarea>
          </FormControlBox>
        </VStack>
      }
      footerContent={
        <HStack spacing={2}>
          <Button variant={'ghost'} onClick={onClose}>
            Close
          </Button>
          <Button
            colorScheme={'brand'}
            onClick={onSubmit}
            isLoading={webForm.processing}
          >
            Create
          </Button>
        </HStack>
      }
    />
  );
}
