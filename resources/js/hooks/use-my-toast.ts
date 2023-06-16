import { useToast } from '@chakra-ui/react';

interface ToastParams {
  ok: boolean;
  message: string | null;
  data?: any;
}

export default function useMyToast() {
  const toast = useToast();
  function handleResponseToast(res: ToastParams): boolean {
    if (!res || !res.ok) {
      toast({
        title: res.message ?? 'Error process request',
        status: 'error',
      });
    } else {
      toast({
        title: res.data?.message ?? 'operation successful',
        status: 'success',
      });
    }
    return res?.ok;
  }

  function toastError(message?: string) {
    return void toast({
      title: message ?? 'Error process request',
      status: 'error',
    });
  }

  function toastSuccess(message: string) {
    return void toast({
      title: message,
      status: 'success',
    });
  }
  return { handleResponseToast, toastSuccess, toastError };
}
