import React from 'react';
import { Button, HStack, Text, VStack } from '@chakra-ui/react';
import useWebForm from '@/hooks/use-web-form';
import GenericModal from '@/components/generic-modal';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import FormControlBox from '../forms/form-control-box';
import EnumSelect from '../dropdown-select/enum-select';
import { InstitutionUser } from '@/types/models';
import { InstitutionUserType } from '@/types/types';

interface Props {
  institutionUser: InstitutionUser;
  isOpen: boolean;
  onClose(): void;
  onSuccess(): void;
}

export default function ChangeInstitutionUserTypeModal({
  isOpen,
  onSuccess,
  onClose,
  institutionUser,
}: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const webForm = useWebForm({ type: institutionUser.type });

  const onSubmit = async () => {
    const res = await webForm.submit((data, web) =>
      web.post(instRoute('users.change-type', [institutionUser]), data)
    );

    if (!handleResponseToast(res)) return;

    onClose();
    webForm.reset();
    onSuccess();
  };

  return (
    <GenericModal
      props={{ isOpen, onClose }}
      headerContent="Change Institution User Type"
      bodyContent={
        <VStack spacing={3} align="stretch">
          <Text fontSize="sm" color="gray.600">
            The institution user type affects access to school features.
          </Text>
          <FormControlBox
            form={webForm as any}
            title="User Type"
            formKey="type"
            isRequired
          >
            <EnumSelect
              enumData={InstitutionUserType}
              selectValue={webForm.data.type}
              onChange={(option: any) =>
                webForm.setValue('type', option?.value ?? '')
              }
              isClearable={false}
              isDisabled={webForm.processing}
              required
            />
          </FormControlBox>
        </VStack>
      }
      footerContent={
        <HStack spacing={2}>
          <Button variant="ghost" onClick={onClose}>
            Close
          </Button>
          <Button
            colorScheme="brand"
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
