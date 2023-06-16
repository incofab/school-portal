import { Div } from '@/components/semantic';
import {
  FormControl,
  FormErrorMessage,
  FormLabel,
  Input,
  useToast,
  Grid,
  GridItem,
  Text,
  Avatar,
  HStack,
} from '@chakra-ui/react';
import React, { ChangeEvent } from 'react';
import {
  bytesToMb,
  MAX_FILE_SIZE_BYTES,
  FileDropperType,
} from '@/components/file-dropper/common';
import { resizeImage } from '@/util/util';
import { Inertia } from '@inertiajs/inertia';
import useSharedProps from '@/hooks/use-shared-props';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import useWebForm, { useWeb } from '@/hooks/use-web-form';
import useInstitutionRoute from '@/hooks/use-institution-route';
import useMyToast from '@/hooks/use-my-toast';
import { Student, User } from '@/types/models';
import DashboardLayout from '@/layout/dashboard-layout';
import Dt from '@/components/dt';
import { SelectOptionType } from '@/types/types';
import { BrandButton } from '@/components/buttons';
import useIsAdmin from '@/hooks/use-is-admin';
import DestructivePopover from '@/components/destructive-popover';

interface Props {
  user: User;
  student: Student;
}

export default function Profile({ user, student }: Props) {
  const { currentUser } = useSharedProps();
  const { instRoute } = useInstitutionRoute();
  const { handleResponseToast } = useMyToast();
  const form = useWebForm({
    first_name: user.first_name,
    last_name: user.last_name,
    other_names: user.other_names,
    email: user.email,
    phone: user.phone,
    photo: user.photo,
  });
  const toast = useToast();
  const web = useWeb();
  const isAdmin = useIsAdmin();
  const extensions = FileDropperType.Image.extensionLabels;

  async function resetPassword(onClose: () => void) {
    const res = await form.submit((data, web) => {
      return web.put(instRoute('users.reset-password', [user]), data);
    });

    if (!handleResponseToast(res)) return;
    onClose();
  }

  async function uploadImage(e: ChangeEvent<HTMLInputElement>) {
    e.preventDefault();
    const { files } = e.target;
    if (!files) {
      return;
    }
    const file: File = files[0];
    const imageBlob = await resizeImage(file, 300, 300);

    const res = await form.submit(async () => {
      const formData = new FormData();
      formData.append('photo', imageBlob as Blob);
      return web.post(instRoute('users.upload-photo', [user]), formData);
    });
    if (!handleResponseToast(res)) return;
    form.setValue('photo', res.data.url);
    Inertia.reload({ only: ['user'] });
  }

  const profileData: SelectOptionType[] = [
    { label: 'First name', value: user.first_name },
    { label: 'Last name', value: user.last_name },
    { label: 'Other names', value: user.other_names },
    { label: 'Email', value: user.email },
    { label: 'Phone', value: user.phone },
    { label: 'Gender', value: user.gender },
    ...(student
      ? [
          { label: 'Student Id', value: student.code },
          { label: 'Guardian Phone', value: student.guardian_phone },
          { label: 'Class', value: student.classification?.title ?? '' },
        ]
      : []),
  ];

  return (
    <div>
      <Slab>
        <SlabHeading
          title={
            user.id === currentUser.id
              ? 'Your Profile'
              : `${user.full_name}'s Profile`
          }
        />
        <SlabBody>
          {/* <form onSubmit={preventNativeSubmit(onSubmit)}> */}
          <Grid templateColumns={{ lg: 'repeat(3, 1fr)' }} gap={4}>
            <GridItem colSpan={{ lg: 2 }}>
              <Dt contentData={profileData} spacing={4} labelWidth={'150px'} />
              {/* 
                <VStack spacing={4}>
                  <FormControl isInvalid={!!form.errors.first_name}>
                    <FormLabel htmlFor="first_name">First Name</FormLabel>
                    <Input
                      id="first_name"
                      value={form.data.first_name}
                      onChange={(e) =>
                        form.setValue('first_name', e.currentTarget.value)
                      }
                    />
                    <FormErrorMessage>
                      {form.errors.first_name}
                    </FormErrorMessage>
                  </FormControl>
                  <FormControl isInvalid={!!form.errors.last_name}>
                    <FormLabel htmlFor="last_name">Last Name</FormLabel>
                    <Input
                      id="last_name"
                      value={form.data.last_name}
                      onChange={(e) =>
                        form.setValue('last_name', e.currentTarget.value)
                      }
                    />
                    <FormErrorMessage>{form.errors.last_name}</FormErrorMessage>
                  </FormControl>
                  <FormControlBox
                    form={form}
                    title="Other Names"
                    formKey="other_names"
                  >
                    <Input
                      type="text"
                      onChange={(e) =>
                        form.setValue('other_names', e.currentTarget.value)
                      }
                      value={form.data.other_names}
                    />
                  </FormControlBox>
                  <FormControl isInvalid={!!form.errors.phone}>
                    <FormLabel htmlFor="phone">Phone</FormLabel>
                    <Input
                      type="phone"
                      onChange={(e) =>
                        form.setValue('phone', e.currentTarget.value)
                      }
                      value={form.data.phone}
                    />
                    <FormErrorMessage>{form.errors.phone}</FormErrorMessage>
                  </FormControl>
                  <FormControlBox form={form} title="Email" formKey="email">
                    <Input
                      type="email"
                      onChange={(e) =>
                        form.setValue('email', e.currentTarget.value)
                      }
                      value={form.data.email}
                      required
                    />
                  </FormControlBox>
                </VStack>
                   */}
            </GridItem>
            <GridItem colSpan={{ lg: 1 }}>
              {currentUser.id !== user.id && isAdmin && (
                <HStack>
                  <DestructivePopover
                    label={`Reset user's password to default?`}
                    onConfirm={(onClose) => resetPassword(onClose)}
                    isLoading={form.processing}
                    positiveButtonLabel="Reset"
                  >
                    <BrandButton title="Reset Password" />
                  </DestructivePopover>
                </HStack>
              )}
              <FormControl isInvalid={!!form.errors.photo}>
                <Div
                  mt={{ lg: 4 }}
                  display={'flex'}
                  alignItems={'center'}
                  flexDirection={{ base: 'column' }}
                >
                  <Div
                    display={'flex'}
                    alignItems={'center'}
                    justifyContent={'center'}
                    w={200}
                    h={200}
                    borderWidth={1}
                    borderColor={'gray.200'}
                  >
                    <Avatar size={'2xl'} src={form.data.photo || user.photo} />
                  </Div>
                  <Div mt={4} textAlign={'center'}>
                    <FormLabel
                      htmlFor="photo"
                      textColor={'brand.500'}
                      display={'inline-block'}
                      cursor={'pointer'}
                      m={0}
                      p={0}
                    >
                      <Input
                        type={'file'}
                        id="photo"
                        hidden
                        accept={'image/jpeg,image/png,image/jpg'}
                        onChange={(e) => uploadImage(e)}
                      />
                      Change profile photo
                    </FormLabel>
                    <Text fontSize={'sm'} color={'blackAlpha.700'}>
                      Allowed extensions {extensions.join(', ')}
                    </Text>
                    <Text fontSize={'sm'} color={'blackAlpha.700'}>
                      Maximum size {Math.floor(bytesToMb(MAX_FILE_SIZE_BYTES))}
                      MB
                    </Text>
                    <FormErrorMessage>{form.errors.photo}</FormErrorMessage>
                  </Div>
                </Div>
              </FormControl>
            </GridItem>
          </Grid>
        </SlabBody>
      </Slab>
    </div>
  );
}

Profile.layout = (page: any) => <DashboardLayout children={page} />;
