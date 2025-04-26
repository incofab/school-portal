import React from 'react';
import { InertiaLink, Link, useForm } from '@inertiajs/inertia-react';
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
import '../../../css/app.css';
import { preventNativeSubmit } from '@/util/util';
import route from '@/util/route';
import CenteredLayout from '@/components/centered-layout';

export default function ForgotPassword({ imageUrl }: { imageUrl?: string }) {
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
    <CenteredLayout title="Forgot Password" bgImage={imageUrl}>
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
              mt={2}
              mb={5}
              colorScheme="brand"
              type="submit"
              style={{ width: '100%', maxWidth: '200px' }}
              isLoading={form.processing}
              loadingText="Submitting"
            >
              Submit
            </Button>
          </Center>
        </FormControl>
      </form>

      <Box ml={5}>
        <ul>
          <li>
            <InertiaLink
              href={route('registration-requests.create')}
              className="authPgLink"
            >
              No Account? - Register
            </InertiaLink>
          </li>
          <li>
            <InertiaLink href={route('login')} className="authPgLink">
              Already Registered? - Login
            </InertiaLink>
          </li>
        </ul>
      </Box>
    </CenteredLayout>
  );
}
