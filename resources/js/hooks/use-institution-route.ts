import useSharedProps from './use-shared-props';

export default function useInstitutionRoute() {
  const { currentInstitution } = useSharedProps();
  function instRoute(
    name?: any,
    params?: any[] | { [key: string]: any },
    absolute?: boolean
  ): any {
    if (!Array.isArray(params) && typeof params === 'object') {
      params = Object.entries(params).map(([key, value]) =>
        value ? { [key]: value } : null
      );
    }

    return (window as any).route(
      `institutions.${name}`,
      // @ts-ignore
      [currentInstitution.uuid, ...(params ? params : [])],
      absolute
    );
  }
  return { instRoute };
}
