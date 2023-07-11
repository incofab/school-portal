import React from 'react';
import { Button, Divider, HStack, Text, VStack } from '@chakra-ui/react';
import useWebForm from '@/hooks/use-web-form';
import GenericModal from '@/components/generic-modal';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import FormControlBox from '../forms/form-control-box';
import { Assessment } from '@/types/models';
import { FullTermType } from '@/types/types';
import EnumSelect from '../dropdown-select/enum-select';

interface Props {
  assessment: Assessment;
  isOpen: boolean;
  onClose(): void;
  onSuccess(): void;
}

export default function SetAssessmentDependencyModal({
  isOpen,
  onSuccess,
  onClose,
  assessment,
}: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const webForm = useWebForm({
    depends_on: assessment.depends_on,
  });

  const onSubmit = async () => {
    const res = await webForm.submit((data, web) =>
      web.post(instRoute('assessments.set-dependency', [assessment]), data)
    );

    if (!handleResponseToast(res)) return;

    onClose();
    webForm.reset();
    onSuccess();
  };

  return (
    <GenericModal
      props={{ isOpen, onClose }}
      headerContent={'Set Assessment Reference'}
      bodyContent={
        <VStack spacing={2}>
          <Text>
            If the result of this assessment is extracted from a previoud
            result, Indicate the result where this assessment should pick the
            result
          </Text>
          <Divider />
          <FormControlBox
            form={webForm as any}
            title="Reference Term"
            formKey="depends_on"
          >
            <EnumSelect
              enumData={FullTermType}
              selectValue={webForm.data.depends_on}
              isMulti={false}
              isClearable={true}
              onChange={(e: any) => webForm.setValue('depends_on', e.value)}
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
            Update
          </Button>
        </HStack>
      }
    />
  );
}
