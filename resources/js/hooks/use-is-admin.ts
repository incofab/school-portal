import useSharedProps from '@/hooks/use-shared-props';
import { UserRoleType } from '@/types/types';

export default function useIsAdmin() {
  const { currentUser } = useSharedProps();
  return currentUser.role === UserRoleType.Admin;
}
