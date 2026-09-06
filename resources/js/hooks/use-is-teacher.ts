import useSharedProps from '@/hooks/use-shared-props';
import { InstitutionUserType } from '@/types/types';

export default function useIsTeacher() {
  const { currentInstitutionUser } = useSharedProps();
  return currentInstitutionUser?.type === InstitutionUserType.Teacher;
}
