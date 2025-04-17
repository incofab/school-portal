import React from 'react';
import { InertiaLink, useForm } from '@inertiajs/inertia-react';
import {
  Box,
  Center,
  Input,
  FormControl,
  FormLabel,
  Button,
  useToast,
  FormErrorMessage,
} from '@chakra-ui/react';
import '../../../css/app.css';
import { preventNativeSubmit } from '@/util/util';
import route from '@/util/route';
import CenteredLayout from '@/components/centered-layout';

interface Props {
  email: string;
  token: string;
  imageUrl?: string;
}

export default function ResetPassword({ email, token, imageUrl }: Props) {
  const form = useForm({
    password: '',
    password_confirmation: '',
    email,
    token,
  });
  const toast = useToast();
  function handleSubmit() {
    form.post(route('password.update'), {
      onSuccess() {
        toast({
          status: 'success',
          title: 'Your password was reset',
          description: 'Please login again',
        });
      },
    });
  }

  return (
    <CenteredLayout title="Reset Password" bgImage={imageUrl}>
      <form onSubmit={preventNativeSubmit(handleSubmit)}>
        <FormControl isRequired mb={6} isInvalid={!!form.errors.email}>
          <FormLabel>Email:</FormLabel>
          <Input
            type="email"
            name="email"
            placeholder="Enter Your Email"
            // onChange={e => form.setData('email', e.currentTarget.value)}
            value={form.data.email}
            required
            readOnly
            disabled
          />
          <FormErrorMessage>{form.errors.email}</FormErrorMessage>
        </FormControl>
        <FormControl isRequired mb={6} isInvalid={!!form.errors.password}>
          <FormLabel>New Password:</FormLabel>
          <Input
            type="password"
            name="password"
            placeholder="Enter a new Password"
            onChange={(e) => form.setData('password', e.currentTarget.value)}
            required
          />
          <FormErrorMessage>{form.errors.password}</FormErrorMessage>
        </FormControl>
        <FormControl
          isRequired
          mb={6}
          isInvalid={!!form.errors.password_confirmation}
        >
          <FormLabel>Confirm Password:</FormLabel>
          <Input
            type="password"
            name="password_confirmation"
            placeholder="Re-enter Password"
            onChange={(e) =>
              form.setData('password_confirmation', e.currentTarget.value)
            }
            value={form.data.password_confirmation}
            required
          />
          <FormErrorMessage>
            {form.errors.password_confirmation}
          </FormErrorMessage>
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
