import React, { useState, ChangeEvent } from 'react';
import {
  Avatar,
  FormControl,
  FormErrorMessage,
  FormLabel,
  Grid,
  GridItem,
  Input,
  Text,
  VStack,
  HStack,
  Button,
  IconButton,
  Tooltip,
  Icon,
  Spinner,
} from '@chakra-ui/react';
import useWebForm, { useWeb } from '@/hooks/use-web-form';
import { preventNativeSubmit, resizeImage } from '@/util/util';
import { Inertia } from '@inertiajs/inertia';
import { InstitutionGroup } from '@/types/models';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import CenteredBox from '@/components/centered-box';
import { FormButton } from '@/components/buttons';
import InputForm from '@/components/forms/input-form';
import useMyToast from '@/hooks/use-my-toast';
import route from '@/util/route';
import ManagerDashboardLayout from '@/layout/managers/manager-dashboard-layout';
import { Div } from '@/components/semantic';
import {
  bytesToMb,
  MAX_FILE_SIZE_BYTES,
  FileDropperType,
} from '@/components/file-dropper/common';
import EnumSelect from '@/components/dropdown-select/enum-select';
import { BrandColor } from '@/util/color-util';

interface Props {
  institutionGroup?: InstitutionGroup;
}

export default function UpdateInstitutionGroup({ institutionGroup }: Props) {
  const [uploading, setUploading] = useState(false);
  const { handleResponseToast } = useMyToast();
  const web = useWeb();
  const extensions = FileDropperType.Image.extensionLabels;

  const webForm = useWebForm({
    name: institutionGroup?.name ?? '',
    loan_limit: institutionGroup?.loan_limit ?? '',
    website: institutionGroup?.website ?? '',
    brand_color: institutionGroup?.brand_color ?? '',
  });

  const form = useWebForm({
    banner: institutionGroup?.banner,
  });

  async function uploadImage(e: ChangeEvent<HTMLInputElement>) {
    e.preventDefault();
    const { files } = e.target;
    if (!files) {
      return;
    }

    const file: File = files[0];
    setUploading(true);

    const res = await form.submit(async () => {
      const formData = new FormData();
      formData.append('banner', file as Blob);

      return web.post(
        route('managers.institution-groups.upload-banner', [institutionGroup]),
        formData
      );
    });

    setUploading(false);
    if (!handleResponseToast(res)) return;
    form.setValue('banner', res.data.url);
    Inertia.reload();
  }

  const submit = async () => {
    const res = await webForm.submit((data, web) => {
      const postData = {
        ...data,
      };
      return institutionGroup
        ? web.put(
            route('managers.institution-groups.update', [institutionGroup]),
            postData
          )
        : web.post(route('managers.institution-groups.store'), postData);
    });
    if (!handleResponseToast(res)) return;
    Inertia.visit(route('managers.institution-groups.index'));
  };

  return (
    <ManagerDashboardLayout>
      <CenteredBox>
        <Slab>
          <SlabHeading
            title={`${institutionGroup ? 'Update' : 'Create'} Group`}
          />
          <SlabBody>
            <Grid templateColumns={{ lg: 'repeat(3, 1fr)' }} gap={4}>
              <GridItem colSpan={{ lg: 2 }}>
                <VStack
                  spacing={4}
                  as={'form'}
                  onSubmit={preventNativeSubmit(submit)}
                >
                  <InputForm
                    form={webForm as any}
                    formKey="name"
                    title="Institution Group Name"
                  />

                  <InputForm
                    form={webForm as any}
                    formKey="loan_limit"
                    title="Loan Limit"
                  />

                  <InputForm
                    form={webForm as any}
                    formKey="website"
                    title="Website"
                  />

                  <FormControl>
                    <EnumSelect
                      enumData={BrandColor}
                      selectValue={webForm.data['brand_color']}
                      onChange={(e: any) =>
                        webForm.setValue('brand_color', e.value)
                      }
                    />
                  </FormControl>

                  <FormControl>
                    <FormButton isLoading={webForm.processing} />
                  </FormControl>
                </VStack>
              </GridItem>
              <GridItem colSpan={{ lg: 1 }}>
                <FormControl isInvalid={!!form.errors.banner}>
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
                      {uploading ? (
                        <Spinner size="xl" color="brand.500" />
                      ) : (
                        <Avatar
                          size={'2xl'}
                          src={form.data.banner || institutionGroup?.banner}
                        />
                      )}
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
                        Change school banner
                      </FormLabel>
                      <Text fontSize={'sm'} color={'blackAlpha.700'}>
                        Allowed extensions {extensions.join(', ')}
                      </Text>
                      <Text fontSize={'sm'} color={'blackAlpha.700'}>
                        Maximum size{' '}
                        {Math.floor(bytesToMb(MAX_FILE_SIZE_BYTES))}
                        MB (1500px by 860px)
                      </Text>
                      <FormErrorMessage>{form.errors.banner}</FormErrorMessage>
                    </Div>
                  </Div>
                </FormControl>
              </GridItem>
            </Grid>
          </SlabBody>
        </Slab>
      </CenteredBox>
    </ManagerDashboardLayout>
  );
}
