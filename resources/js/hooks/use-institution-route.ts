import useSharedProps from './use-shared-props';

export default function useInstitutionRoute() {
  const { currentInstitution } = useSharedProps();
  function instRoute(name?: any, params?: any[], absolute?: boolean): any {
    return (window as any).route(
      `institutions.${name}`,
      [currentInstitution.uuid, ...(params ? params : [])],
      absolute
    );
  }
  return { instRoute };
}
