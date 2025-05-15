import React from 'react';
import {
  Button,
  HStack,
  VStack,
  Input,
} from '@chakra-ui/react';
import useWebForm from '@/hooks/use-web-form';
import GenericModal from '@/components/generic-modal';
import useMyToast from '@/hooks/use-my-toast';
import { User } from '@/types/models';
import FormControlBox from '../forms/form-control-box';
import route from '@/util/route';
import EnumSelect from '../dropdown-select/enum-select';
import { Gender } from '@/types/types';

interface Props {
  manager: User;
  isOpen: boolean;
  onClose(): void;
  onSuccess(): void;
}

export default function EditManagerModal({
  isOpen,
  onSuccess,
  onClose,
  manager,
}: Props) {

  const { handleResponseToast } = useMyToast();
  const webForm = useWebForm({
    first_name: manager.first_name??'',
    last_name: manager.last_name??'',
    other_names: manager.other_names??'',
    phone: manager.phone??'',
    email: manager.email??'',
    gender: manager.gender??'',
  });

  const onSubmit = async () => {
    let res = await webForm.submit((data, web) => {
      return web.post(
        route('managers.update', [manager.id]),
        data
      );
    });

    if (!handleResponseToast(res)) {
      return;
    }

    onClose();
    onSuccess();
  };

  return (
    <GenericModal
      props={{ isOpen, onClose }}
      headerContent={`Edit Manager Profile`}
      bodyContent={
        <VStack spacing={3}>
          <FormControlBox
            form={webForm}
            title="First Name"
            formKey="first_name"
          >
            <Input
              type="text"
              onChange={(e) =>
                webForm.setValue('first_name', e.currentTarget.value)
              }
              value={webForm.data.first_name}
              required
            />
          </FormControlBox>
          <FormControlBox form={webForm} title="Last Name" formKey="last_name">
            <Input
              type="text"
              onChange={(e) =>
                webForm.setValue('last_name', e.currentTarget.value)
              }
              value={webForm.data.last_name}
              required
            />
          </FormControlBox>
          <FormControlBox
            form={webForm}
            title="Other Names"
            formKey="other_names"
          >
            <Input
              type="text"
              onChange={(e) =>
                webForm.setValue('other_names', e.currentTarget.value)
              }
              value={webForm.data.other_names}
            />
          </FormControlBox>
          <FormControlBox form={webForm} title="Phone" formKey="phone">
            <Input
              type="tel"
              onChange={(e) => webForm.setValue('phone', e.currentTarget.value)}
              value={webForm.data.phone}
            />
          </FormControlBox>
          <FormControlBox form={webForm} title="Email" formKey="email">
            <Input
              type="email"
              onChange={(e) => webForm.setValue('email', e.currentTarget.value)}
              value={webForm.data.email}
              required
            />
          </FormControlBox>

          <FormControlBox form={webForm} title="Gender" formKey="gender">
            <EnumSelect
              enumData={Gender}
              selectValue={webForm.data.gender}
              onChange={(e: any) => webForm.setValue('gender', e?.value)}
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
