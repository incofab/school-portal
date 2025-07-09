import React from 'react';
import {
  Button,
  FormControl,
  FormLabel,
  HStack,
  VStack,
} from '@chakra-ui/react';
import useWebForm from '@/hooks/use-web-form';
import GenericModal from '@/components/generic-modal';
import useInstitutionRoute from '@/hooks/use-institution-route';
import FormControlBox from '../forms/form-control-box';
import useSharedProps from '@/hooks/use-shared-props';
import { SelectOptionType, TermType } from '@/types/types';
import AcademicSessionSelect from '../selectors/academic-session-select';
import EnumSelect from '../dropdown-select/enum-select';
import ClassificationSelect from '../selectors/classification-select';
import { Inertia } from '@inertiajs/inertia';

interface Props {
  isOpen: boolean;
  onClose(): void;
}

export default function RetrievePaymentSummaryModal({
  isOpen,
  onClose,
}: Props) {
  const { currentAcademicSessionId, currentTerm } = useSharedProps();
  const { instRoute } = useInstitutionRoute();
  const webForm = useWebForm({
    academic_session_id: currentAcademicSessionId,
    term: currentTerm,
    classification_id: {} as SelectOptionType<number>,
  });

  function onSubmit() {
    var url = instRoute('fee-payments.summary', {
      ...webForm.data,
      classification_id: webForm.data.classification_id?.value,
    });
    console.log('URL', url);

    Inertia.visit(url);
  }

  return (
    <GenericModal
      props={{ isOpen, onClose }}
      headerContent={'Record Student Payment'}
      bodyContent={
        <VStack spacing={2}>
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
              selectValue={webForm.data.term}
              enumData={TermType}
              isMulti={false}
              isClearable={true}
              onChange={(e: any) => webForm.setValue('term', e?.value)}
            />
          </FormControlBox>
          <FormControl>
            <FormLabel>Class</FormLabel>
            <ClassificationSelect
              selectValue={webForm.data.classification_id}
              isMulti={false}
              isClearable={true}
              onChange={(e: any) => webForm.setValue('classification_id', e)}
            />
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
            Save
          </Button>
        </HStack>
      }
    />
  );
}
