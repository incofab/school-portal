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

export default route;
