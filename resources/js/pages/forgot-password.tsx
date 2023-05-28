import React from 'react';
import { Link, useForm } from '@inertiajs/inertia-react';
import {
  Box,
  Center,
  Input,
  FormControl,
  FormLabel,
  Button,
  Show,
  useToast,
  FormErrorMessage,
} from '@chakra-ui/react';
import '../../css/app.css';
import { preventNativeSubmit } from '@/util/util';
import route from '@/util/route';

export default function ForgotPassword() {
  const toast = useToast();
  const form = useForm({
    email: '',
  });
  function handleSubmit() {
    form.post(route('forgot-password.store'), {
      onSuccess: () =>
        toast({
          title: 'Reset password link has been sent to your mail',
          status: 'success',
        }),
      onError: () =>
        toast({ status: 'error', title: 'Error: password reset failed' }),
    });
  }

  return (
    <Box className="pageContainer flex">
      <Show above="md">
        <Box className="authPage-Left"></Box>
      </Show>

      <Box className="authPage-Right">
        <Box className="formContainer">
          <Box className="subTitle" mb={12}>
            <center>Forgot Password.</center>
          </Box>
          <form onSubmit={preventNativeSubmit(handleSubmit)}>
            <FormControl mb={6} isRequired isInvalid={!!form.errors.email}>
              <FormLabel>Email</FormLabel>
              <Input
                type="email"
                name="email"
                placeholder="Enter Your Email"
                onChange={(e) => form.setData('email', e.currentTarget.value)}
                value={form.data.email}
                required
              />
              <FormErrorMessage>{form.errors.email}</FormErrorMessage>
            </FormControl>
            <FormControl>
              <Center>
                <Button
                  mt={10}
                  mb={5}
                  colorScheme="facebook"
                  type="submit"
                  style={{ width: '100%', maxWidth: '200px' }}
                  isLoading={form.processing}
                  loadingText="Submitting"
                >
                  ... Submit ...
                </Button>
              </Center>
            </FormControl>
          </form>

          <Box ml={5}>
            <ul>
              <li>
                <Link href={route('register.create')} className="authPgLink">
                  No Account? - Register
                </Link>
              </li>
              <li>
                <Link href={route('login')} className="authPgLink">
                  Already Registered? - Login
                </Link>
              </li>
            </ul>
          </Box>
        </Box>
      </Box>
    </Box>
  );
}
