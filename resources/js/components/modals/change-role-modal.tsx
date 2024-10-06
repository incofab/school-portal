import React from 'react';
import { Button, HStack, VStack } from '@chakra-ui/react';
import useWebForm from '@/hooks/use-web-form';
import GenericModal from '@/components/generic-modal';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import FormControlBox from '../forms/form-control-box';
import { InstitutionUser } from '@/types/models';
import { InstitutionUserType } from '@/types/types';
import EnumSelect from '../dropdown-select/enum-select';

interface Props {
  institutionUser: InstitutionUser;
  isOpen: boolean;
  onClose(): void;
  onSuccess(): void;
}

export default function ChangeRoleModal({
  isOpen,
  onSuccess,
  onClose,
  institutionUser,
}: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const webForm = useWebForm({
    role: institutionUser.role,
  });

  const onSubmit = async () => {
    const res = await webForm.submit((data, web) =>
      web.post(instRoute('users.change-role', [institutionUser]), data)
    );

    if (!handleResponseToast(res)) return;

    onClose();
    webForm.reset();
    onSuccess();
  };

  return (
    <GenericModal
      props={{ isOpen, onClose }}
      headerContent={'Change Role'}
      bodyContent={
        <VStack spacing={2}>
          <FormControlBox form={webForm as any} title="Role" formKey="role">
            <EnumSelect
              enumData={InstitutionUserType}
              selectValue={webForm.data.role}
              isMulti={false}
              isClearable={true}
              onChange={(e: any) => webForm.setValue('role', e?.value)}
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
