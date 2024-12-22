import React from 'react';
import { Button, HStack, VStack } from '@chakra-ui/react';
import useWebForm from '@/hooks/use-web-form';
import GenericModal from '@/components/generic-modal';
import useMyToast from '@/hooks/use-my-toast';
import FormControlBox from '../forms/form-control-box';
import { InstitutionGroup, Billing } from '@/types/models';
import route from '@/util/route';
import InstitutionGroupSelect from '../selectors/institution-group-select';
import InputForm from '../forms/input-form';
import EnumSelect from '../dropdown-select/enum-select';
import { PaymentStructure, PriceType } from '@/types/types';

interface Props {
  priceList?: Billing | null;
  institutionGroups: InstitutionGroup[];
  isOpen: boolean;
  onClose(): void;
  onSuccess(): void;
}

export default function BillInstitutionGroupModal({
  isOpen,
  onSuccess,
  onClose,
  institutionGroups,
  priceList,
}: Props) {
  const { handleResponseToast } = useMyToast();

  const webForm = useWebForm({
    institution_group_id: priceList?.institution_group_id ?? '',
    billable: priceList?.type ?? '',
    payment_structure: priceList?.payment_structure ?? '',
    amount: priceList?.amount ?? '',
  });

  const onSubmit = async () => {
    const res = await webForm.submit((data, web) =>
      web.post(
        route('managers.billings.store', {
          ...data,
        })
      )
    );

    if (!handleResponseToast(res)) {
      return;
    }

    webForm.reset();
    onClose();
    onSuccess();
  };

  return (
    <GenericModal
      props={{ isOpen, onClose }}
      headerContent={'Bill an Institution Group'}
      bodyContent={
        <VStack spacing={2}>
          <FormControlBox
            form={webForm as any}
            title="Institution Group"
            formKey="institution_group_id"
          >
            <InstitutionGroupSelect
              value={webForm.data.institution_group_id}
              selectValue={webForm.data.institution_group_id}
              isMulti={false}
              isClearable={true}
              onChange={(e: any) => {
                webForm.setValue('institution_group_id', e?.value);
              }}
              institutionGroups={institutionGroups}
              required
            />
          </FormControlBox>

          <FormControlBox
            form={webForm as any}
            title="Bill Type"
            formKey="billable"
          >
            <EnumSelect
              enumData={PriceType}
              selectValue={webForm.data.billable}
              isClearable={true}
              onChange={(e: any) => webForm.setValue('billable', e?.value)}
              required
            />
          </FormControlBox>

          <FormControlBox
            form={webForm as any}
            title="Payment Structure"
            formKey="payment_structure"
          >
            <EnumSelect
              enumData={PaymentStructure}
              selectValue={webForm.data.payment_structure}
              isClearable={true}
              onChange={(e: any) =>
                webForm.setValue('payment_structure', e?.value)
              }
              required
            />
          </FormControlBox>

          <InputForm
            form={webForm as any}
            formKey="amount"
            title="Amount"
            isRequired
          />
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
            Submit
          </Button>
        </HStack>
      }
    />
  );
}
