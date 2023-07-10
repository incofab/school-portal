import React from 'react';
import { Button, Checkbox, HStack, VStack } from '@chakra-ui/react';
import useWebForm from '@/hooks/use-web-form';
import GenericModal from '@/components/generic-modal';
import { TermType } from '@/types/types';
import useIsAdmin from '@/hooks/use-is-admin';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import FormControlBox from '../forms/form-control-box';
import AcademicSessionSelect from '../selectors/academic-session-select';
import EnumSelect from '../dropdown-select/enum-select';
import ClassificationSelect from '../selectors/classification-select';
import useSharedProps from '@/hooks/use-shared-props';

interface Props {
  isOpen: boolean;
  onClose(): void;
  onSuccess(): void;
}

export default function CalculateClassResultInfoModal({
  isOpen,
  onSuccess,
  onClose,
}: Props) {
  const isAdmin = useIsAdmin();
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const { currentAcademicSession, currentTerm, usesMidTermResult } =
    useSharedProps();
  const webForm = useWebForm({
    academic_session_id: currentAcademicSession,
    term: currentTerm,
    classification: '',
    for_mid_term: false,
  });

  const onSubmit = async () => {
    const res = await webForm.submit((data, web) => {
      return web.post(
        instRoute('class-result-info.calculate', [data.classification]),
        data
      );
    });

    if (!handleResponseToast(res)) return;

    onClose();
    webForm.reset();
    onSuccess();
  };

  return (
    <GenericModal
      props={{ isOpen, onClose }}
      headerContent={'Evaluate Student Result'}
      bodyContent={
        <VStack>
          {isAdmin && (
            <FormControlBox
              form={webForm as any}
              formKey="classification"
              title="Class"
            >
              <ClassificationSelect
                value={webForm.data.classification}
                isMulti={false}
                isClearable={true}
                onChange={(e: any) =>
                  webForm.setValue('classification', e.value)
                }
                required
              />
            </FormControlBox>
          )}
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
                webForm.setValue('academic_session_id', e.value)
              }
              required
            />
          </FormControlBox>
          <FormControlBox form={webForm as any} title="Term" formKey="term">
            <EnumSelect
              enumData={TermType}
              selectValue={webForm.data.term}
              isClearable={true}
              onChange={(e: any) => webForm.setValue('term', e.value)}
              required
            />
          </FormControlBox>
          {usesMidTermResult && (
            <FormControlBox
              form={webForm as any}
              formKey="for_mid_term"
              title=""
            >
              <Checkbox
                isChecked={webForm.data.for_mid_term}
                onChange={(e) =>
                  webForm.setValue('for_mid_term', e.currentTarget.checked)
                }
              >
                For Mid-Term Result
              </Checkbox>
            </FormControlBox>
          )}
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
            Evaluate
          </Button>
        </HStack>
      }
    />
  );
}
