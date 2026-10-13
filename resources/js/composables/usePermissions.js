function readUser() {
  try {
    return JSON.parse(localStorage.getItem('user') || '{}');
  } catch {
    return {};
  }
}

export function usePermissions() {
  function isSuperAdmin() {
    return !!readUser().is_super_admin;
  }

  function can(permission) {
    if (!permission) return true;
    const user = readUser();
    if (user.is_super_admin) return true;
    return (user.permissions || []).includes(permission);
  }

  function role() {
    const roles = readUser().roles || [];
    return roles.length ? roles[0] : '';
  }

  return { can, isSuperAdmin, role };
}
