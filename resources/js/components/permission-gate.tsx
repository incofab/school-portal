import React, { ReactNode } from 'react';
import useSharedProps from '@/hooks/use-shared-props';
import {
  hasInstitutionPermission,
  InstitutionPermissionName,
} from '@/types/permissions';

type Permission = InstitutionPermissionName | string;

export interface PermissionGateProps {
  permissions: Permission | readonly Permission[];
  match?: 'all' | 'any';
  when?: boolean;
  fallback?: ReactNode;
  children: ReactNode;
}

/**
 * Renders its children only when the current institution user has the
 * requested permission(s). Multiple permissions match any by default.
 */
export default function PermissionGate({
  permissions,
  match = 'any',
  when = true,
  fallback = null,
  children,
}: PermissionGateProps) {
  const { currentUserPermissions } = useSharedProps();
  const requiredPermissions = Array.isArray(permissions)
    ? permissions
    : [permissions];
  const hasPermission = (permission: Permission) =>
    hasInstitutionPermission(currentUserPermissions, permission);
  const allowed =
    when &&
    (match === 'all'
      ? requiredPermissions.every(hasPermission)
      : requiredPermissions.some(hasPermission));

  return allowed ? <>{children}</> : <>{fallback}</>;
}

export function AnyPermission({
  permissions,
  children,
  fallback,
  when,
}: Omit<PermissionGateProps, 'match'>) {
  return (
    <PermissionGate permissions={permissions} when={when} fallback={fallback}>
      {children}
    </PermissionGate>
  );
}

export function AllPermissions({
  permissions,
  children,
  fallback,
  when,
}: Omit<PermissionGateProps, 'match'>) {
  return (
    <PermissionGate
      permissions={permissions}
      match="all"
      when={when}
      fallback={fallback}
    >
      {children}
    </PermissionGate>
  );
}
