import React from 'react';
import { Button, HStack, Input, Text, VStack } from '@chakra-ui/react';
import GenericModal from '@/components/generic-modal';
import FormControlBox from '@/components/forms/form-control-box';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import { InstitutionGroup } from '@/types/models';
import route from '@/util/route';

interface Props {
  institutionGroup: InstitutionGroup;
  isOpen: boolean;
  onClose(): void;
  onSuccess(): void;
}

export default function ChangeInstitutionGroupPartnerModal({
  institutionGroup,
  isOpen,
  onClose,
  onSuccess,
}: Props) {
  const { handleResponseToast } = useMyToast();
  const form = useWebForm({
    email: '',
  });

  async function submit() {
    const res = await form.submit((data, web) =>
      web.post(
        route('managers.institution-groups.update-partner-user', [
          institutionGroup.id,
        ]),
        data
      )
    );

    if (!handleResponseToast(res)) {
      return;
    }

    onClose();
    onSuccess();
  }

  return (
    <GenericModal
      props={{ isOpen, onClose }}
      headerContent="Change Partner"
      bodyContent={
        <VStack align="stretch" spacing={3}>
          <Text fontSize="sm">
            Current partner: {institutionGroup.partner?.full_name ?? 'N/A'}
          </Text>
          <FormControlBox
            form={form}
            title="Partner User Email"
            formKey="email"
          >
            <Input
              type="email"
              value={form.data.email}
              onChange={(e) => form.setValue('email', e.currentTarget.value)}
              autoFocus
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
            onClick={submit}
            isLoading={form.processing}
          >
            Update Partner
          </Button>
        </HStack>
      }
    />
  );
}
