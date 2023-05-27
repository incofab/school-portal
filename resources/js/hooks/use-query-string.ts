import { Inertia } from '@inertiajs/inertia';

export default function useQueryString() {
  const params = (() => {
    const url = new URL(window.location.href);
    const params: { [key: string]: string } = {};
    url.searchParams.forEach((val, key) => {
      params[key] = val;
    });
    return params;
  })();

  function updateQueryString(
    newParams: { [key: string]: string | string[] },
    { visit = true, preserveState = true, preserveScroll = true }
  ) {
    const url = new URL(window.location.href);
    for (const key in newParams) {
      const value = newParams[key];
      if (value === '') {
        url.searchParams.delete(key);
      } else if (Array.isArray(value) && value.length === 0) {
        url.searchParams.delete(`${key}[]`);
      } else if (typeof value === 'string') {
        url.searchParams.set(key, value);
      } else if (Array.isArray(value)) {
        url.searchParams.delete(`${key}[]`);
        for (const item of value) {
          url.searchParams.append(`${key}[]`, item);
        }
      }
    }
    if (visit) {
      Inertia.visit(url.toString(), { preserveState, preserveScroll });
    } else {
      window.history.replaceState(null, '', url);
    }
    return url;
  }

  function clearQueryString({ visit = true }) {
    const url = new URL(window.location.href);
    url.searchParams.forEach((val, key) => {
      url.searchParams.delete(key);
    });
    if (visit) {
      Inertia.visit(url.toString());
    } else {
      window.history.replaceState(null, '', url);
    }
  }

  return { params, updateQueryString, clearQueryString };
}
