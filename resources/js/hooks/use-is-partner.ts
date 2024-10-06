import useSharedProps from '@/hooks/use-shared-props';
import { ManagerRole } from '@/types/types';

export default function useIsPartner() {
  const { currentUser } = useSharedProps();
  const role = currentUser.roles?.filter(
    (role) => role.name === ManagerRole.Partner
  );
  return Number(role?.length) > 0;
}
