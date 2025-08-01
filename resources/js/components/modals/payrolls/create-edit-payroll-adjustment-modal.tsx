import React from 'react';
import {
  Button,
  HStack,
  Icon,
  IconButton,
  Input,
  Textarea,
  VStack,
} from '@chakra-ui/react';
import useWebForm from '@/hooks/use-web-form';
import GenericModal from '@/components/generic-modal';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import {
  PayrollAdjustmentType,
  PayrollAdjustment,
  PayrollSummary,
} from '@/types/models';
import FormControlBox from '../../forms/form-control-box';
import { InstitutionUserType, SelectOptionType } from '@/types/types';
import AdjustmentTypeSelect from '../../selectors/payroll-adjustment-type-select';
import { PlusIcon } from '@heroicons/react/24/solid';
import { Div } from '@/components/semantic';
import InstitutionUserSelect from '../../selectors/institution-user-select';
import useModalToggle from '@/hooks/use-modal-toggle';
import { Inertia } from '@inertiajs/inertia';
import CreateEditAdjustmentTypeModal from './create-edit-adjustment-type-modal';
import { MultiValue } from 'react-select';
import { generateUniqueString, ucFirst } from '@/util/util';

interface Props {
  isOpen: boolean;
  onClose(): void;
  onSuccess(): void;
  payrollSummary: PayrollSummary;
  payrollAdjustment?: PayrollAdjustment;
  payrollAdjustmentTypes: PayrollAdjustmentType[];
}

export default function CreateEditPayrollAdjustmentModal({
  isOpen,
  onSuccess,
  onClose,
  payrollSummary,
  payrollAdjustment,
  payrollAdjustmentTypes,
}: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const createAdjustmentTypeModal = useModalToggle();

  const isUpdate = payrollAdjustment?.id;
  const webForm = useWebForm({
    payroll_adjustment_type_id:
      payrollAdjustment?.payroll_adjustment_type_id ?? '',
    description: payrollAdjustment?.description ?? '',
    amount: payrollAdjustment?.amount ?? '',
    institution_user_ids: (isUpdate
      ? [
          {
            label: payrollAdjustment.institution_user!.user!.full_name,
            value: payrollAdjustment.institution_user_id,
          },
        ]
      : []) as MultiValue<SelectOptionType<number>>,
  });

  const onSubmit = async () => {
    const reference = generateUniqueString('');
    const res = await webForm.submit(async (data, web) => {
      return isUpdate
        ? web.put(
            instRoute('payroll-adjustments.update', [payrollAdjustment.id]),
            data
          )
        : web.post(
            instRoute('payroll-summaries.payroll-adjustments.store', [
              payrollSummary.id,
            ]),
            {
              ...data,
              reference: reference,
              institution_user_ids: data.institution_user_ids?.map(
                (item) => item.value
              ),
            }
          );
    });

    if (!handleResponseToast(res)) {
      return;
    }

    onClose();
    webForm.reset();
    onSuccess();
  };
  const monthYear = `${ucFirst(payrollSummary.month)}, ${payrollSummary.year}`;

  return (
    <>
      <GenericModal
        props={{ isOpen, onClose }}
        headerContent={`${
          isUpdate ? 'Update' : 'Create'
        } Salary Adjustment for ${monthYear}`}
        bodyContent={
          <VStack spacing={3}>
            <FormControlBox
              title="Staff"
              form={webForm as any}
              formKey="institution_user_ids"
            >
              <InstitutionUserSelect
                value={webForm.data.institution_user_ids}
                isClearable={true}
                rolesIn={[
                  InstitutionUserType.Admin,
                  InstitutionUserType.Accountant,
                  InstitutionUserType.Teacher,
                ]}
                onChange={(e) => webForm.setValue('institution_user_ids', e)}
                isMulti={true}
                required
                isDisabled={isUpdate ? true : false}
              />
            </FormControlBox>

            <FormControlBox
              form={webForm as any}
              title="Adjustment Type"
              formKey="payroll_adjustment_type_id"
              isRequired
            >
              <HStack align={'stretch'}>
                <Div width={'full'}>
                  <AdjustmentTypeSelect
                    selectValue={webForm.data.payroll_adjustment_type_id}
                    isMulti={false}
                    isClearable={true}
                    onChange={(e: any) =>
                      webForm.setValue('payroll_adjustment_type_id', e?.value)
                    }
                    required
                    payrollAdjustmentTypes={payrollAdjustmentTypes}
                  />
                </Div>

                <IconButton
                  aria-label="Add adjustment type"
                  icon={<Icon as={PlusIcon} />}
                  onClick={createAdjustmentTypeModal.open}
                  colorScheme="brand"
                />
              </HStack>
            </FormControlBox>
            <FormControlBox
              form={webForm as any}
              title="Amount"
              formKey="amount"
              isRequired
            >
              <Input
                type="number"
                onChange={(e) =>
                  webForm.setValue('amount', e.currentTarget.value)
                }
                value={webForm.data.amount}
              />
            </FormControlBox>
            {/* {!isUpdate && (
              <>
                <FormControlBox
                  form={webForm as any}
                  title="Affected Month"
                  formKey="month"
                >
                  <EnumSelect
                    enumData={YearMonth}
                    selectValue={webForm.data.month}
                    isMulti={false}
                    isClearable={true}
                    onChange={(e: any) => webForm.setValue('month', e?.value)}
                    required
                  />
                </FormControlBox>

                <FormControlBox
                  form={webForm as any}
                  title="Affected Year"
                  formKey="year"
                  isRequired
                >
                  <YearSelect
                    selectValue={webForm.data.year}
                    isMulti={false}
                    isClearable={true}
                    onChange={(e: any) => webForm.setValue('year', e?.value)}
                    required
                  />
                </FormControlBox>
              </>
            )} */}

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
              {isUpdate ? 'Update' : 'Create'}
            </Button>
          </HStack>
        }
      />

      <CreateEditAdjustmentTypeModal
        payrollAdjustmentTypes={payrollAdjustmentTypes}
        {...createAdjustmentTypeModal.props}
        onSuccess={() => Inertia.reload()}
      />
    </>
  );
}
