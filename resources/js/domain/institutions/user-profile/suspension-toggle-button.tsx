import React, { useState } from 'react';
import {
  Button,
  FormLabel,
  HStack,
  Spinner,
  Switch,
  Textarea,
  VStack,
} from '@chakra-ui/react';
import { InstitutionUser } from '@/types/models';
import useInstitutionRoute from '@/hooks/use-institution-route';
import useMyToast from '@/hooks/use-my-toast';
import useWebForm from '@/hooks/use-web-form';
import { InstitutionUserStatus } from '@/types/types';
import GenericModal from '@/components/generic-modal';
import { useModalValueToggle } from '@/hooks/use-modal-toggle';

export interface Props {
  institutionUser: InstitutionUser;
  showLabel?: boolean;
}

export default function SuspensionToggleButton({
  institutionUser,
  showLabel,
}: Props) {
  const { instRoute } = useInstitutionRoute();
  const { handleResponseToast } = useMyToast();
  const suspensionForm = useWebForm({ status: institutionUser.status });
  const suspendedReasonModalToggle = useModalValueToggle<InstitutionUser>();

  async function toggleSuspended(
    status: InstitutionUserStatus,
    status_message?: string
  ) {
    const res = await suspensionForm.submit((data, web) => {
      return web.post(
        instRoute('institution-users.update-status', [institutionUser.id]),
        {
          status,
          status_message,
        }
      );
    });

    if (!handleResponseToast(res)) return;

    institutionUser.status = status;
    suspensionForm.setValue('status', status);
  }

  function isSuspended() {
    return suspensionForm.data.status === InstitutionUserStatus.Suspended;
  }

  return (
    <>
      {suspensionForm.processing ? (
        <Spinner size="md" color="brand.500" />
      ) : (
        <FormLabel>
          <Switch
            isChecked={isSuspended()}
            onChange={() => {
              if (!isSuspended()) {
                suspendedReasonModalToggle.open(institutionUser);
              } else {
                if (window.confirm('Do you want to lift this suspension?')) {
                  toggleSuspended(InstitutionUserStatus.Active);
                }
              }
            }}
            colorScheme={'red'}
            disabled={suspensionForm.processing}
          />
          {showLabel ? (isSuspended() ? 'Suspended' : 'Active') : ''}
        </FormLabel>
      )}
      {suspendedReasonModalToggle.state && (
        <SuspendedReasonModal
          institutionUser={suspendedReasonModalToggle.state}
          {...suspendedReasonModalToggle.props}
          onSuccess={(reason) =>
            toggleSuspended(InstitutionUserStatus.Suspended, reason)
          }
        />
      )}
    </>
  );
}

interface SuspendedReasonModalProps {
  isOpen: boolean;
  institutionUser: InstitutionUser;
  onClose(): void;
  onSuccess(status_message: string): void;
}

export function SuspendedReasonModal({
  isOpen,
  institutionUser,
  onSuccess,
  onClose,
}: SuspendedReasonModalProps) {
  const [reason, setReason] = useState<string>(institutionUser.status_message);

  const onSubmit = async () => {
    onClose();
    onSuccess(reason);
  };
  const fullname = institutionUser?.user?.full_name;
  return (
    <GenericModal
      props={{ isOpen, onClose }}
      headerContent={`Suspension Message ${fullname ? ` for ${fullname}` : ''}`}
      bodyContent={
        <VStack spacing={2}>
          <Textarea
            value={reason}
            onChange={(e) => setReason(e.currentTarget.value)}
          ></Textarea>
        </VStack>
      }
      footerContent={
        <HStack spacing={2}>
          <Button variant={'ghost'} onClick={onClose}>
            Close
          </Button>
          <Button colorScheme={'brand'} onClick={onSubmit}>
            Submit
          </Button>
        </HStack>
      }
    />
  );
}
