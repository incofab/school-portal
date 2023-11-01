import { Div } from '@/components/semantic';
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
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import FormControlBox from '@/components/forms/form-control-box';
import useWebForm, { useWeb } from '@/hooks/use-web-form';
import useInstitutionRoute from '@/hooks/use-institution-route';
import useMyToast from '@/hooks/use-my-toast';
import { Institution } from '@/types/models';
import { preventNativeSubmit } from '@/util/util';
import DashboardLayout from '@/layout/dashboard-layout';
import InputForm from '@/components/forms/input-form';

interface Props {
  institution: Institution;
}
export default function InstitutionProfile({ institution }: Props) {
  const { instRoute } = useInstitutionRoute();
  const { handleResponseToast } = useMyToast();
  const form = useWebForm({
    name: institution.name,
    phone: institution.phone,
    email: institution.email,
    address: institution.address,
    photo: institution.photo,
    subtitle: institution.subtitle,
    caption: institution.caption,
    website: institution.website,
  });

  const toast = useToast();
  const web = useWeb();
  const extensions = FileDropperType.Image.extensionLabels;

  async function onSubmit() {
    const res = await form.submit((data, web) => {
      return web.put(instRoute('update'), data);
    });

    if (!handleResponseToast(res)) return;

    Inertia.reload({ only: ['institution'] });
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
      return web.post(instRoute('upload-photo'), formData);
    });
    if (!handleResponseToast(res)) return;
    form.setValue('photo', res.data.url);
    Inertia.reload({ only: ['institution'] });
  }

  return (
    <div> 
      <Slab>
        <SlabHeading title={`${institution.name}'s Profile`} />
        <SlabBody>
          <form onSubmit={preventNativeSubmit(onSubmit)}>
            <Grid templateColumns={{ lg: 'repeat(3, 1fr)' }} gap={4}>
              <GridItem colSpan={{ lg: 2 }}>
                <VStack spacing={4}>
                  <FormControlBox form={form} formKey="name" title="Name">
                    <Input
                      id="name"
                      value={form.data.name}
                      onChange={(e) =>
                        form.setValue('name', e.currentTarget.value)
                      }
                    />
                  </FormControlBox>
                  <InputForm
                    form={form as any}
                    formKey="subtitle"
                    title="Sub Title [optional]"
                    onChange={(e) =>
                      form.setValue('subtitle', e.currentTarget.value)
                    }
                  />
                  <InputForm
                    form={form as any}
                    formKey="caption"
                    title="Caption [optional]"
                    onChange={(e) =>
                      form.setValue('caption', e.currentTarget.value)
                    }
                  />
                  <FormControlBox form={form} formKey="phone" title="Phone">
                    <Input
                      id="phone"
                      value={form.data.phone}
                      onChange={(e) =>
                        form.setValue('phone', e.currentTarget.value)
                      }
                    />
                  </FormControlBox>
                  <FormControlBox form={form} formKey="email" title="Email">
                    <Input
                      id="email"
                      value={form.data.email}
                      onChange={(e) =>
                        form.setValue('email', e.currentTarget.value)
                      }
                    />
                  </FormControlBox>
                  <FormControlBox form={form} formKey="address" title="Address">
                    <Input
                      id="address"
                      value={form.data.address}
                      onChange={(e) =>
                        form.setValue('address', e.currentTarget.value)
                      }
                    />
                  </FormControlBox>
                  <InputForm
                    form={form as any}
                    formKey="website"
                    title="Website URL [optional]"
                    onChange={(e) =>
                      form.setValue('website', e.currentTarget.value)
                    }
                  />
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
                      <Avatar size={'2xl'} src={form.data.photo} />
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

InstitutionProfile.layout = (page: any) => <DashboardLayout children={page} />;
