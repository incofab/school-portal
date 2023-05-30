import useSharedProps from '@/hooks/use-shared-props';
import { InstitutionUserType } from '@/types/types';

export default function useIsAlumni() {
  const { currentInstitutionUser } = useSharedProps();
  return currentInstitutionUser.role === InstitutionUserType.Alumni;
}
