import axios, { AxiosInstance, AxiosResponse } from 'axios';
import { useRef, useState } from 'react';

export interface WebForm<
  Data = Record<string, any>,
  Errors = Record<keyof Data, string>
> {
  data: Data;
  errors: Errors;
  processing: boolean;
  setValue<Key extends keyof Data>(key: Key, value: Data[Key]): void;
  setData(data: Data): void;
  reset(): void;
  submit(
    cb: (data: Data, web: AxiosInstance) => Promise<AxiosResponse>
  ): Promise<
    | { ok: true; data: any; errors: null; message: null }
    | { ok: false; errors: Errors; data: null; message: string }
  >;
}

export default function useWebForm<Data = Record<string, any>>(
  initialData: Data | (() => Data)
): WebForm<Data> {
  type FormErrors = Record<keyof Data, string>;

  const [data, setData] = useState<Data>(initialData);
  const [errors, setErrors] = useState<FormErrors>({} as FormErrors);
  const [processing, setProcessing] = useState(false);
  const initialRef = useRef<Data>(data);
  const web = useWeb();

  return {
    data,
    errors,
    processing,
    setData,
    setValue(key, value) {
      setData((old) => ({ ...old, [key]: value }));
    },
    reset() {
      setData({ ...initialRef.current });
      setErrors({} as FormErrors);
    },
    async submit(cb) {
      setProcessing(true);
      try {
        const response = await cb(data, web);
        setErrors({} as FormErrors);
        return { ok: true, data: response.data, errors: null, message: null };
      } catch (e: any) {
        if (e?.response?.status === 422) {
          setErrors(e.response.data?.errors ?? {});
        } else {
          setErrors({} as FormErrors);
        }
        return {
          ok: false,
          data: null,
          errors: e.response?.data?.errors ?? null,
          message: e.response?.data?.message,
        };
      } finally {
        setProcessing(false);
      }
    },
  };
}

export function useWeb() {
  const web = axios.create({
    headers: {
      common: {
        'X-Requested-With': 'XMLHttpRequest',
      },
    },
    baseURL: '/',
  });
  return web;
}
