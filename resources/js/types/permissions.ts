export const InstitutionPermission = {
  // UI-only marker for routes whose access is determined by role or ownership.
  NaturalAccess: 'natural-access',
  ManageRoles: 'manage-roles',
  ManageAdmissions: 'manage-admissions',
  ManageRecruitment: 'manage-recruitment',
  ManageLibrary: 'manage-library',
  ManageAttendance: 'manage-attendance',
  ManageFees: 'manage-fees',
  ManageFinance: 'manage-finance',
  ManagePayroll: 'manage-payroll',
  ManageNotifications: 'manage-notifications',
  ManageChat: 'manage-chat',
} as const;

export type InstitutionPermissionName =
  (typeof InstitutionPermission)[keyof typeof InstitutionPermission];

export function hasInstitutionPermission(
  assignedPermissions: readonly string[],
  requiredPermission: string
): boolean {
  if (requiredPermission === InstitutionPermission.NaturalAccess) {
    return true;
  }

  return (
    assignedPermissions.includes('*') ||
    assignedPermissions.includes(requiredPermission)
  );
}
