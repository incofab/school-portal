import useSharedProps from '@/hooks/use-shared-props';
import { ManagerRole } from '@/types/types';

export default function useIsAdminManager() {
  const { currentUser } = useSharedProps();
  return currentUser.manager_role === ManagerRole.Admin;
}
