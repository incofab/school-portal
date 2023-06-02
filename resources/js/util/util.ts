import { Resizer } from './image-file-resizer';

export const dateFormat = 'yyyy-MM-dd';

type PreventDefault = Pick<React.FormEvent, 'preventDefault'>['preventDefault'];
type StopPropagation = Pick<
  React.FormEvent,
  'stopPropagation'
>['stopPropagation'];

export function preventNativeSubmit<
  T extends {
    preventDefault: PreventDefault;
    stopPropagation: StopPropagation;
  }
>(callback: any) {
  return function (e: T) {
    e.preventDefault();
    e.stopPropagation();
    callback(e);
  };
}

export function formatAsCurrency(num: number) {
  const formatter = new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'NGN',
  });
  return formatter.format(num);
}

export function resizeImage(file: Blob, maxWidth: number, maxHeight: number) {
  return new Promise((resolve) => {
    Resizer.createResizedImage(
      file,
      maxWidth,
      maxHeight,
      'JPEG',
      100,
      0,
      (uri: string) => {
        resolve(uri);
      },
      'blob'
    );
  });
}

export function setUrlFilterOptions(
  option: string,
  filters: {
    [key: string]:
      | { label: string; value: string }
      | null
      | undefined
      | number
      | boolean
      | string;
  },
  url: URL
) {
  const optionValue = filters[option];

  if (!optionValue) {
    url.searchParams.delete(option);
    url.searchParams.delete(`${option}Label`);
    return;
  }

  if (
    typeof optionValue === 'string' ||
    typeof optionValue === 'number' ||
    typeof optionValue === 'boolean'
  ) {
    url.searchParams.set(option, optionValue + '');
    return;
  }

  url.searchParams.set(option, optionValue?.value ?? '');
  url.searchParams.set(`${option}Label`, optionValue?.label ?? '');
}
