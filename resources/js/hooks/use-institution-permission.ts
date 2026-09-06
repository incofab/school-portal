import useInstitutionPermissions, {
  PermissionRequirements,
} from './use-institution-permissions';

/**
 * Checks one institution permission without creating a one-item permission map.
 *
 * const canEdit = useInstitutionPermission(InstitutionPermission.ManageFees);
 */
export default function useInstitutionPermission(
  permissions: PermissionRequirements,
  match: 'all' | 'any' = 'any'
): boolean {
  const { allowed } = useInstitutionPermissions({
    allowed: { permissions, match },
  });

  return allowed;
}
