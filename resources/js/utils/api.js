export function isSuspendMessage(message) {
  return /suspend/i.test(message || '');
}

export function isForbiddenError(err) {
  if (err?.response?.status !== 403) return false;
  return !isSuspendMessage(err.response?.data?.message || '');
}

export function toastApiError(err, showToast, t, genericKey) {
  if (isForbiddenError(err)) {
    showToast(t('common.no_permission'), 'error');
    return true;
  }
  showToast(t(genericKey), 'error');
  return false;
}
