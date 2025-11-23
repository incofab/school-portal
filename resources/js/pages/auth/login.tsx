import { Div } from '@/components/semantic';
import { preventNativeSubmit } from '@/util/util';
import route from '@/util/route';
import {
  Button,
  FormControl,
  FormErrorMessage,
  FormLabel,
  HStack,
  Icon,
  Input,
  Menu,
  MenuButton,
  MenuItem,
  MenuList,
  VStack,
} from '@chakra-ui/react';
import { InertiaLink } from '@inertiajs/inertia-react';
import React, { useEffect, useRef } from 'react';
import useSharedProps from '@/hooks/use-shared-props';
import { Inertia } from '@inertiajs/inertia';
import PasswordInput from '@/components/password-input';
import CenteredLayout from '@/components/centered-layout';
import { InstitutionGroup } from '@/types/models';
import useMyToast from '@/hooks/use-my-toast';
import useWebForm from '@/hooks/use-web-form';
import { LinkButton } from '@/components/buttons';
import { ArrowRightIcon } from '@heroicons/react/24/outline';

export default function Login({
  institutionGroup,
}: {
  institutionGroup?: InstitutionGroup;
}) {
  const { message, csrfToken } = useSharedProps();
  const { toastError, toastSuccess, handleResponseToast } = useMyToast();
  const webForm = useWebForm({
    email: '',
    password: '',
  });

  async function onSubmit() {
    const res = await webForm.submit((data, web) =>
      web.post(route('login.store'), data)
    );
    // console.log(res);

    if (!handleResponseToast(res)) return;

    Inertia.visit(route('user.dashboard'));
    // window.location.href = route('user.dashboard');
  }

  useEffect(() => {
    message?.error && toastError(message.error);

    message?.success && toastSuccess(message.success);
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
    <CenteredLayout
      title={'Login'}
      rightHeader={
        <LinkButton
          title="Check Result"
          href={route('activate-term-result.create')}
          colorScheme="brand"
          variant="outline"
          rightIcon={<Icon as={ArrowRightIcon} />}
        />
      }
      bgImage={institutionGroup?.banner}
      boxProps={{ opacity: 0.92 }}
    >
      <VStack
        spacing={4}
        align={'stretch'}
        as={'form'}
        onSubmit={preventNativeSubmit(onSubmit)}
      >
        <FormControl isInvalid={!!webForm.errors.email}>
          <FormLabel htmlFor="email">Email address</FormLabel>
          <Input
            id="email"
            type="text"
            value={webForm.data.email}
            onChange={(e) => webForm.setValue('email', e.currentTarget.value)}
          />
          <FormErrorMessage>{webForm.errors.email}</FormErrorMessage>
        </FormControl>
        <FormControl isInvalid={!!webForm.errors.password}>
          <FormLabel htmlFor="password">Password</FormLabel>
          <PasswordInput
            id={'password'}
            value={webForm.data.password}
            onChange={(e) =>
              webForm.setValue('password', e.currentTarget.value)
            }
          />
          <FormErrorMessage>{webForm.errors.password}</FormErrorMessage>
        </FormControl>
        <HStack align={'stretch'} justify={'space-between'}>
          <Button
            as={InertiaLink}
            href={route('student-login')}
            colorScheme={'brand'}
            variant={'link'}
            float={'right'}
          >
            Student Login
          </Button>
          <Button
            as={InertiaLink}
            href={route('forgot-password')}
            colorScheme={'brand'}
            variant={'link'}
            float={'right'}
          >
            Forgot Password?
          </Button>
        </HStack>
        <Button
          isLoading={webForm.processing}
          loadingText="Logging in"
          type="submit"
          colorScheme={'brand'}
          id="login"
        >
          Login
        </Button>
        <HStack align={'stretch'} justify={'space-between'}>
          <Menu>
            <MenuButton
              as={Button}
              variant={'link'}
              colorScheme={'brand'}
              fontWeight={'normal'}
            >
              Exam Login
            </MenuButton>
            <MenuList>
              <MenuItem
                as={InertiaLink}
                href={route('student.exam.login.create')}
                py={2}
              >
                Student Test
              </MenuItem>
              <MenuItem
                as={InertiaLink}
                href={route('admissions.exam.login.create')}
                py={2}
              >
                Admission Exam
              </MenuItem>
              <MenuItem
                as={InertiaLink}
                href={route('student.exam.login.create')}
                py={2}
              >
                Recruitment Text
              </MenuItem>
            </MenuList>
          </Menu>
          {/* <Button
            as={InertiaLink}
            href={route('student.exam.login.create')}
            colorScheme={'brand'}
            variant={'link'}
            float={'right'}
          >
            Exam Login
          </Button> */}
          {!institutionGroup && (
            <Div textAlign={'center'}>
              <InertiaLink href={route('registration-requests.create')}>
                <Button colorScheme={'brand'} variant={'link'}>
                  Need an account?
                </Button>
              </InertiaLink>
            </Div>
          )}
        </HStack>
      </VStack>
    </CenteredLayout>
  );
}
