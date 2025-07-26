import React from 'react';
import { Button, HStack, VStack } from '@chakra-ui/react';
import useWebForm from '@/hooks/use-web-form';
import GenericModal from '@/components/generic-modal';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import FormControlBox from '../../forms/form-control-box';
import EnumSelect from '../../dropdown-select/enum-select';
import { YearMonth } from '@/types/types';
import YearSelect from '../../selectors/year-select';

interface Props {
  isOpen: boolean;
  onClose(): void;
  onSuccess(): void;
}

export default function CreatePayrollSummaryModal({
  isOpen,
  onSuccess,
  onClose,
}: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();

  const webForm = useWebForm({
    month: '',
    year: '',
  });

  const onSubmit = async () => {
    const res = await webForm.submit(async (data, web) => {
      return web.post(instRoute('payroll-summaries.store'), data);
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
      headerContent={`Start Payroll`}
      bodyContent={
        <VStack spacing={3}>
          <FormControlBox form={webForm as any} title="Month" formKey="month">
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
            title="Year"
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
            Start
          </Button>
        </HStack>
      }
    />
  );
}
