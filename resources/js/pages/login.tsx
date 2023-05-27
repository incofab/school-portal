import { Div } from '@/components/semantic';
import { preventNativeSubmit } from '@/util/util';
import route from '@/util/route';
import {
  Button,
  FormControl,
  FormErrorMessage,
  FormLabel,
  Input,
  useToast,
  VStack,
} from '@chakra-ui/react';
import { InertiaLink, useForm } from '@inertiajs/inertia-react';
import React, { useEffect, useRef } from 'react';
import useSharedProps from '@/hooks/use-shared-props';
import { Inertia } from '@inertiajs/inertia';
import PasswordInput from '@/components/password-input';

export default function Login() {
  const { message, csrfToken } = useSharedProps();
  const toast = useToast();
  const form = useForm({
    email: '',
    password: '',
  });

  function onSubmit() {
    form.post(route('login.store'));
  }

  useEffect(() => {
    message.error &&
      toast({
        title: message.error,
        status: 'error',
      });

    message.success &&
      toast({
        title: message.success,
        status: 'success',
      });
  }, [message]);

  const isSessionTimedOut = useRef(false);

  const handleVisibilityChange = () => {
    if (document.hidden || !isSessionTimedOut.current) {
      return;
    }
    isSessionTimedOut.current = false;

    Inertia.reload();
  };

  useEffect(() => {
    setTimeout(function () {
      isSessionTimedOut.current = true;
      handleVisibilityChange();
    }, 2 * 60 * 60 * 1000);

    document.addEventListener('visibilitychange', handleVisibilityChange);

    return () => {
      isSessionTimedOut.current = false;
      document.removeEventListener('visibilitychange', handleVisibilityChange);
    };
  }, [csrfToken]);

  return (
    <Div bg={'blue.50'} py={12} minH={'100vh'}>
      <Div
        bg={'white'}
        p={6}
        mx={'auto'}
        w={'full'}
        maxW={'md'}
        shadow={'md'}
        rounded={'md'}
      >
        <VStack
          spacing={4}
          align={'stretch'}
          as={'form'}
          onSubmit={preventNativeSubmit(onSubmit)}
        >
          <FormControl isInvalid={!!form.errors.email}>
            <FormLabel htmlFor="email">Email address</FormLabel>
            <Input
              id="email"
              type="text"
              value={form.data.email}
              onChange={(e) => form.setData('email', e.currentTarget.value)}
            />
            <FormErrorMessage>{form.errors.email}</FormErrorMessage>
          </FormControl>
          <FormControl isInvalid={!!form.errors.password}>
            <FormLabel htmlFor="password">Password</FormLabel>
            <PasswordInput
              id={'password'}
              value={form.data.password}
              onChange={(e) => form.setData('password', e.currentTarget.value)}
            />
            <FormErrorMessage>{form.errors.password}</FormErrorMessage>
          </FormControl>
          <FormControl>
            <Button
              as={InertiaLink}
              href={route('forgot-password')}
              colorScheme={'brand'}
              variant={'link'}
              float={'right'}
            >
              Forgot Password?
            </Button>
          </FormControl>
          <Button
            isLoading={form.processing}
            loadingText="Logging in"
            type="submit"
            colorScheme={'brand'}
            id="login"
          >
            Login
          </Button>
          <Div textAlign={'center'}>
            <InertiaLink href={route('register')}>
              <Button colorScheme={'brand'} variant={'link'}>
                Need an account?
              </Button>
            </InertiaLink>
          </Div>
        </VStack>
      </Div>
    </Div>
  );
}
