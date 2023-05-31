import React from 'react';
import { Button, HStack, VStack } from '@chakra-ui/react';
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
  const webForm = useWebForm({
    classification: '',
    academic_session_id: '',
    term: '',
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
      headerContent={'Upload course results'}
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
