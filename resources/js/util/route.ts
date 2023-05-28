import useSharedProps from '@/hooks/use-shared-props';
import { RouteParamsWithQueryOverload, Router } from 'ziggy-js';

function route(
  name?: undefined,
  params?: RouteParamsWithQueryOverload,
  absolute?: boolean
): Router;

function route(
  name: string,
  params?: RouteParamsWithQueryOverload,
  absolute?: boolean
): string;

function route(
  name?: any,
  params?: RouteParamsWithQueryOverload,
  absolute?: boolean
): any {
  return (window as any).route(name, params, absolute);
}

export function instRoute(name?: any, params?: any[], absolute?: boolean): any {
  const { currentInstitution } = useSharedProps();
  return (window as any).route(
    `institutions.${name}`,
    [currentInstitution.uuid, ...(params ? params : [])],
    absolute
  );
}

export default route;
