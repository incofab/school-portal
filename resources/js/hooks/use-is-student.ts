import useSharedProps from '@/hooks/use-shared-props';
import { UserRoleType } from '@/types/types';

export default function useIsStudent() {
  const { currentUser } = useSharedProps();
  return currentUser.role === UserRoleType.Student;
}
