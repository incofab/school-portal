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

interface Props {
  email: string;
  token: string;
}

export default function ResetPassword({ email, token }: Props) {
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
    <Box className="pageContainer flex">
      <Show above="md">
        <Box className="authPage-Left"></Box>
      </Show>

      <Box className="authPage-Right">
        <Box className="formContainer">
          <Box className="subTitle" mb={12}>
            <center>Reset Password.</center>
          </Box>
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
                onChange={(e) =>
                  form.setData('password', e.currentTarget.value)
                }
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
