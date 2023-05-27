import useSharedProps from '@/hooks/use-shared-props';
import { UserRoleType } from '@/types/types';

export default function useIsAlumni() {
  const { currentUser } = useSharedProps();
  return currentUser.role === UserRoleType.Alumni;
}
