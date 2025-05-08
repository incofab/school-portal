import React from 'react';
import { FormLabel, Switch } from '@chakra-ui/react';
import { InstitutionUser } from '@/types/models';
import useInstitutionRoute from '@/hooks/use-institution-route';
import useMyToast from '@/hooks/use-my-toast';
import useWebForm from '@/hooks/use-web-form';
import { InstitutionUserStatus } from '@/types/types';

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

  async function toggleSuspended() {
    if (!window.confirm('Do you want to change suspension status?')) {
      return;
    }
    const newStatus = isSuspended()
      ? InstitutionUserStatus.Active
      : InstitutionUserStatus.Suspended;
    const res = await suspensionForm.submit((data, web) => {
      return web.post(
        instRoute('institution-users.update-status', [institutionUser.id]),
        {
          status: newStatus,
        }
      );
    });

    if (!handleResponseToast(res)) return;

    institutionUser.status = newStatus as InstitutionUserStatus;
    suspensionForm.setValue('status', newStatus as InstitutionUserStatus);
    // onSuccess();
  }

  function isSuspended() {
    return suspensionForm.data.status === InstitutionUserStatus.Suspended;
  }

  return (
    <FormLabel>
      <Switch
        isChecked={isSuspended()}
        onChange={toggleSuspended}
        colorScheme={'red'}
        disabled={suspensionForm.processing}
      />
      {showLabel ? (isSuspended() ? 'Suspended' : 'Active') : ''}
    </FormLabel>
  );
}
