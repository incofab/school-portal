import { InstitutionSettingType, Message } from '@/types/types';
import {
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
  shared__currentAcademicSession: number;
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
    currentAcademicSession: props.shared__currentAcademicSession,
    ...prepareSettings(currentInstitution.institution_settings),
  };
}

function prepareSettings(institutionSettings?: InstitutionSetting[]) {
  let usesMidTermResult = false;
  let resultTemplate = '';
  if (!institutionSettings) {
    return { usesMidTermResult, resultTemplate };
  }
  const settings = useMemo(() => {
    const instSetting = {} as { [key: string]: InstitutionSetting };
    institutionSettings.map((item) => {
      instSetting[item.key] = item;
    });
    return instSetting;
  }, []);

  // const currentTerm = settings[InstitutionSettingType.CurrentTerm]?.value;
  // const currentAcademicSession =
  //   settings[InstitutionSettingType.CurrentAcademicSession]?.value;
  usesMidTermResult = Boolean(
    parseInt(settings[InstitutionSettingType.UsesMidTermResult]?.value)
  );
  resultTemplate = settings[InstitutionSettingType.ResultTemplate]?.value;

  return { usesMidTermResult, resultTemplate };
}
