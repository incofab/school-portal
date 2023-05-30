import { useToast } from '@chakra-ui/react';

interface ToastParams {
  ok: boolean;
  message: string | null;
}

export default function useMyToast() {
  const toast = useToast();
  function handleResponseToast(res: ToastParams): boolean {
    if (!res.ok) {
      toast({
        title: res.message ?? 'Error process request',
        status: 'error',
      });
    } else {
      toast({
        title: 'operation successful',
        status: 'success',
      });
    }
    return res.ok;
  }

  function toastError(message: string) {
    return void toast({
      title: message,
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
