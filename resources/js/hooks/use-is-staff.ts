import useSharedProps from '@/hooks/use-shared-props';
import { InstitutionUserType } from '@/types/types';

export default function useIsStaff() {
  const { currentInstitutionUser } = useSharedProps();
  return (
    currentInstitutionUser.role !== InstitutionUserType.Guardian &&
    currentInstitutionUser.role !== InstitutionUserType.Student &&
    currentInstitutionUser.role !== InstitutionUserType.Alumni
  );
}
