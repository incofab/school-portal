import useSharedProps from '@/hooks/use-shared-props';
import { InstitutionUserType } from '@/types/types';

export default function useIsStaff() {
  const { currentInstitutionUser } = useSharedProps();
  return (
    !!currentInstitutionUser &&
    currentInstitutionUser.type !== InstitutionUserType.Guardian &&
    currentInstitutionUser.type !== InstitutionUserType.Student &&
    currentInstitutionUser.type !== InstitutionUserType.Alumni
  );
}
