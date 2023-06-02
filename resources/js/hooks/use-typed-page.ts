import { usePage } from '@inertiajs/inertia-react';
import { SharedProps } from '@/hooks/use-shared-props';

export default function useTypedPage<T>() {
  const page = usePage();
  const props = page.props as unknown as SharedProps & T;

  return { props };
}
