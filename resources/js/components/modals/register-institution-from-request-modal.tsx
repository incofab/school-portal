import React from 'react';
import { Button, HStack, VStack } from '@chakra-ui/react';
import useWebForm from '@/hooks/use-web-form';
import GenericModal from '@/components/generic-modal';
import useMyToast from '@/hooks/use-my-toast';
import FormControlBox from '../forms/form-control-box';
import { InstitutionGroup, RegistrationRequest } from '@/types/models';
import route from '@/util/route';
import InstitutionGroupSelect from '../selectors/institution-group-select';

interface Props {
  registrationRequest: RegistrationRequest;
  institutionGroups: InstitutionGroup[];
  isOpen: boolean;
  onClose(): void;
  onSuccess(): void;
}

export default function RegisterInstitutionFromRequestModal({
  isOpen,
  onSuccess,
  onClose,
  registrationRequest,
  institutionGroups,
}: Props) {
  const { handleResponseToast } = useMyToast();
  const webForm = useWebForm({
    institution_group_id: '',
  });

  const onSubmit = async () => {
    const res = await webForm.submit((data, web) =>
      web.post(
        route('managers.registration-requests.institutions.store', [
          data.institution_group_id,
          registrationRequest.id,
        ])
      )
    );

    if (!handleResponseToast(res)) {
      return;
    }

    onClose();
    webForm.setData({ institution_group_id: '' });
    onSuccess();
  };

  return (
    <GenericModal
      props={{ isOpen, onClose }}
      headerContent={'Register Institution'}
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
