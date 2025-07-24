import React, { useState } from 'react';
import { Button, HStack, Input, Textarea, VStack } from '@chakra-ui/react';
import useWebForm from '@/hooks/use-web-form';
import GenericModal from '@/components/generic-modal';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { SalaryType } from '@/types/models';
import FormControlBox from '../../forms/form-control-box';
import SalaryTypeSelect from '../../selectors/salary-type-select';
import ButtonSwitch from '@/components/button-switch';
import MySelect from '@/components/dropdown-select/my-select';
import { TransactionType } from '@/types/types';

interface Props {
  isOpen: boolean;
  onClose(): void;
  onSuccess(): void;
  salaryType?: SalaryType;
  salaryTypes: SalaryType[];
}

export default function CreateEditSalaryTypeModal({
  isOpen,
  onSuccess,
  onClose,
  salaryType,
  salaryTypes,
}: Props) {
  const { handleResponseToast, toastError } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const [usesFixedAmount, setusesFixedAmount] = useState<boolean>(
    Boolean(!salaryType?.parent_id)
  );

  const webForm = useWebForm({
    type: salaryType?.type ?? '',
    title: salaryType?.title ?? '',
    description: salaryType?.description ?? '',
    parent_id: salaryType?.parent_id ?? '',
    percentage: salaryType?.percentage ?? '',
  });

  const isUpdate = salaryType?.id;

  const onSubmit = async () => {
    if (
      !usesFixedAmount &&
      (!webForm.data.parent_id || !webForm.data.percentage)
    ) {
      return toastError(
        'Supply both the parent and percentage for relative salary'
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
        ? web.put(instRoute('salary-types.update', [salaryType]), postData)
        : web.post(instRoute('salary-types.store'), postData);
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
      headerContent={`${isUpdate ? 'Update' : 'Create'} Salary Type`}
      bodyContent={
        <VStack spacing={3} align={'start'}>
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
                { label: 'Earnings/Income', value: TransactionType.Credit },
                { label: 'Deduction', value: TransactionType.Debit },
              ]}
              isClearable={true}
              isMulti={false}
              onChange={(e: any) => webForm.setValue('type', e?.value)}
              required
            />
          </FormControlBox>

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

          {!usesFixedAmount && (
            <>
              <FormControlBox
                form={webForm as any}
                title="Related Salary Component"
                formKey="parent_id"
                isRequired
              >
                <SalaryTypeSelect
                  selectValue={webForm.data.parent_id}
                  isMulti={false}
                  isClearable={true}
                  onChange={(e: any) => webForm.setValue('parent_id', e?.value)}
                  required
                  salaryTypes={salaryTypes}
                />
              </FormControlBox>
              {webForm.data.parent_id && (
                <FormControlBox
                  form={webForm as any}
                  title="Percentage (%)"
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

          <FormControlBox
            form={webForm as any}
            title="Description [optional]"
            formKey="description"
            noOfLines={2}
          >
            <Textarea
              value={webForm.data.description}
              onChange={(e) =>
                webForm.setValue('description', e.currentTarget.value)
              }
              placeholder={'Enter description here...'}
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
