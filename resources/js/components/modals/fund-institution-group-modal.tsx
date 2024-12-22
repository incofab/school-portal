import React from 'react';
import {
  Button,
  HStack,
  Textarea,
  VStack,
  Checkbox,
  FormControl,
} from '@chakra-ui/react';
import useWebForm from '@/hooks/use-web-form';
import GenericModal from '@/components/generic-modal';
import useMyToast from '@/hooks/use-my-toast';
import FormControlBox from '../forms/form-control-box';
import { InstitutionGroup } from '@/types/models';
import route from '@/util/route';
import InstitutionGroupSelect from '../selectors/institution-group-select';
import InputForm from '../forms/input-form';
import { generateRandomString } from '@/util/util';

interface Props {
  institutionGroups: InstitutionGroup[];
  isOpen: boolean;
  onClose(): void;
  onSuccess(): void;
}

export default function FundInstitutionGroupModal({
  isOpen,
  onSuccess,
  onClose,
  institutionGroups,
}: Props) {
  const { handleResponseToast } = useMyToast();
  const webForm = useWebForm({
    institution_group_id: '',
    amount: '',
    is_debt: false,
    remark: '',
  });

  const onSubmit = async () => {
    const res = await webForm.submit((data, web) =>
      web.post(
        route('managers.funding.store', {
          ...data,
          reference:
            webForm.data.institution_group_id + generateRandomString(16),
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
      headerContent={'Add Fund to Institution Group'}
      bodyContent={
        <VStack spacing={2}>
          <FormControlBox
            form={webForm as any}
            title="Institution Group"
            formKey="institution_group_id"
          >
            <InstitutionGroupSelect
              value={webForm.data.institution_group_id}
              isMulti={false}
              isClearable={true}
              onChange={(e: any) =>
                webForm.setValue('institution_group_id', e?.value)
              }
              institutionGroups={institutionGroups}
              required
            />
          </FormControlBox>

          <InputForm
            form={webForm as any}
            formKey="amount"
            title="Amount"
            isRequired
          />

          <FormControlBox
            form={webForm as any}
            title="Remark [optional]"
            formKey="remark"
          >
            <Textarea
              onChange={(e) =>
                webForm.setValue('remark', e.currentTarget.value)
              }
            ></Textarea>
          </FormControlBox>

          <FormControl>
            <Checkbox
              isChecked={webForm.data.is_debt}
              onChange={(e) =>
                webForm.setData({
                  ...webForm.data,
                  is_debt: e.currentTarget.checked,
                })
              }
              size={'md'}
              colorScheme="brand"
            >
              Mark as Debt (Loan)
            </Checkbox>
          </FormControl>
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
