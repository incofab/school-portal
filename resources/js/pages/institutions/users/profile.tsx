import { Div } from '@/components/semantic';
import route from '@/util/route';
import {
  Button,
  FormControl,
  FormErrorMessage,
  FormLabel,
  Input,
  useToast,
  VStack,
  Grid,
  GridItem,
  Text,
  Avatar,
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
import FormControlBox from '@/components/forms/form-control-box';
import ProfileLayout from '@/domain/institutions/user-profile/profile-layout';
import useWebForm, { useWeb } from '@/hooks/use-web-form';
import useInstitutionRoute from '@/hooks/use-institution-route';
import useMyToast from '@/hooks/use-my-toast';
import { User } from '@/types/models';
import { preventNativeSubmit } from '@/util/util';
import DashboardLayout from '@/layout/dashboard-layout';

interface Props {
  user: User;
}
export default function Profile({ user }: Props) {
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
  const extensions = FileDropperType.Image.extensionLabels;

  async function onSubmit() {
    const res = await form.submit((data, web) => {
      return web.put(instRoute('users.update', [user]), data);
    });

    if (!handleResponseToast(res)) return;

    Inertia.reload({ only: ['user'] });
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
          <form onSubmit={preventNativeSubmit(onSubmit)}>
            <Grid templateColumns={{ lg: 'repeat(3, 1fr)' }} gap={4}>
              <GridItem colSpan={{ lg: 2 }}>
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
              </GridItem>
              <GridItem colSpan={{ lg: 1 }}>
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
                      <Avatar
                        size={'2xl'}
                        src={form.data.photo || user.photo}
                      />
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
                        Maximum size{' '}
                        {Math.floor(bytesToMb(MAX_FILE_SIZE_BYTES))}MB
                      </Text>
                      <FormErrorMessage>{form.errors.photo}</FormErrorMessage>
                    </Div>
                  </Div>
                </FormControl>
              </GridItem>
            </Grid>
            <Div mt={4} alignSelf={'start'}>
              <Button
                type="submit"
                isLoading={form.processing}
                loadingText="Saving"
                colorScheme={'brand'}
              >
                Save
              </Button>
            </Div>
          </form>
        </SlabBody>
      </Slab>
    </div>
  );
}

{
  /* <ProfileLayout children={page} /> */
}
Profile.layout = (page: any) => <DashboardLayout children={page} />;
