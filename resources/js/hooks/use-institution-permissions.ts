import useSharedProps from './use-shared-props';
import {
  hasInstitutionPermission,
  InstitutionPermissionName,
} from '@/types/permissions';

export type PermissionRequirement = InstitutionPermissionName | string;
export type PermissionRequirements =
  | PermissionRequirement
  | readonly PermissionRequirement[];

export type PermissionDefinition =
  | PermissionRequirements
  | {
      permissions: PermissionRequirements;
      match?: 'all' | 'any';
    };

export type PermissionDefinitions = Record<string, PermissionDefinition>;

export type PermissionChecks<Definitions extends PermissionDefinitions> = {
  [Key in keyof Definitions]: boolean;
};

/**
 * Resolves several institution permissions in one hook call.
 *
 * Usage:
 * const permissions = useInstitutionPermissions({
 *   canEdit: InstitutionPermission.ManageFees,
 *   canManageResults: [
 *     InstitutionPermission.ManageFees,
 *   ],
 * });
 *
 * <StudentActions {...permissions} />
 */
export default function useInstitutionPermissions<
  Definitions extends PermissionDefinitions
>(definitions: Definitions): PermissionChecks<Definitions> {
  const { currentUserPermissions } = useSharedProps();
  const hasPermission = (definition: PermissionDefinition): boolean => {
    const isGroupedDefinition =
      typeof definition === 'object' &&
      !Array.isArray(definition) &&
      definition !== null &&
      'permissions' in definition;
    const permissions = isGroupedDefinition
      ? definition.permissions
      : definition;
    const requiredPermissions = Array.isArray(permissions)
      ? permissions
      : [permissions];
    const match = isGroupedDefinition ? definition.match ?? 'any' : 'any';

    return match === 'all'
      ? requiredPermissions.every((permission) =>
          hasInstitutionPermission(currentUserPermissions, permission)
        )
      : requiredPermissions.some((permission) =>
          hasInstitutionPermission(currentUserPermissions, permission)
        );
  };

  return Object.fromEntries(
    Object.entries(definitions).map(([key, definition]) => [
      key,
      hasPermission(definition),
    ])
  ) as PermissionChecks<Definitions>;
}
