import useSharedProps from '@/hooks/use-shared-props';
import { InstitutionUserType } from '@/types/types';

export default function useIsGuardian() {
  const { currentInstitutionUser } = useSharedProps();
  return currentInstitutionUser.role === InstitutionUserType.Guardian;
}
