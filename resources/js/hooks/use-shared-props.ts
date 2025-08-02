import {
  InstitutionSettingType,
  Message,
  PaymentKey,
  ResultSettingType,
} from '@/types/types';
import {
  AcademicSession,
  Institution,
  InstitutionSetting,
  InstitutionUser,
  User,
} from '@/types/models';
import { usePage } from '@inertiajs/inertia-react';
import { useMemo } from 'react';

export interface SharedProps {
  shared__currentUser: User;
  shared__currentInstitution: Institution;
  shared__currentInstitutionUser: InstitutionUser;
  shared__currentAcademicSessionId: number;
  shared__currentAcademicSession: AcademicSession;
  shared__currentTerm: string;
  shared__isImpersonating: boolean;
  shared__csrfToken: string;
  shared__message: Message;
}

export default function useSharedProps() {
  const page = usePage();
  const props = page.props as unknown as SharedProps;

  const currentInstitution = props.shared__currentInstitution;

  return {
    currentUser: props.shared__currentUser as User,
    isImpersonating: props.shared__isImpersonating as boolean,
    csrfToken: props.shared__csrfToken as string,
    message: props.shared__message as Message,
    currentInstitution: currentInstitution,
    currentInstitutionUser: props.shared__currentInstitutionUser,
    currentTerm: props.shared__currentTerm,
    currentAcademicSessionId: props.shared__currentAcademicSessionId,
    currentAcademicSession: props.shared__currentAcademicSession,
    ...prepareSettings(currentInstitution?.institution_settings),
  };
}

function prepareSettings(institutionSettings?: InstitutionSetting[]) {
  const settings = useMemo(() => {
    const instSetting = {} as { [key: string]: InstitutionSetting };
    if (institutionSettings) {
      institutionSettings.map((item) => {
        instSetting[item.key] = item;
      });
    }
    return instSetting;
  }, []);

  const usesMidTermResult = Boolean(
    parseInt(settings[InstitutionSettingType.UsesMidTermResult]?.value)
  );
  return {
    usesMidTermResult: usesMidTermResult,
    currentlyOnMidTerm:
      usesMidTermResult &&
      Boolean(
        parseInt(settings[InstitutionSettingType.CurrentlyOnMidTerm]?.value)
      ),
    resultSetting: settings[InstitutionSettingType.Result]?.value as {
      [key: string]: string;
    },
    stamp: settings[InstitutionSettingType.Stamp]?.value,
    paymentKeys: settings[InstitutionSettingType.PaymentKeys]?.value as {
      [key: string]: PaymentKey;
    },
    lockTermSession: Boolean(
      parseInt(settings[InstitutionSettingType.LockTermSession]?.value ?? 1)
    ),
  };
}
