import useSharedProps from './use-shared-props';

export default function useHasInstitutionPermission() {
  const { currentUserPermissions } = useSharedProps();
  const hasWildcard = currentUserPermissions.includes('*');

  return (permission: string) =>
    hasWildcard || currentUserPermissions.includes(permission);
}
