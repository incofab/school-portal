import { Div } from '@/components/semantic';
import { generateRandomString, preventNativeSubmit } from '@/util/util';
import route from '@/util/route';
import {
  Avatar,
  Divider,
  FormControl,
  FormErrorMessage,
  FormLabel,
  HStack,
  Input,
  Spacer,
  Text,
  Grid,
  GridItem,
  VStack,
} from '@chakra-ui/react';
import React, { useState } from 'react';
import FormControlBox from '@/components/forms/form-control-box';
import EnumSelect from '@/components/dropdown-select/enum-select';
import { Gender, Nationality, Religion } from '@/types/types';
import { AdmissionApplication, Institution } from '@/types/models';
import InputForm from '@/components/forms/input-form';
import { FormButton } from '@/components/buttons';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import { Inertia } from '@inertiajs/inertia';
import { PageTitle } from '@/components/page-header';
import {
  bytesToMb,
  MAX_FILE_SIZE_BYTES,
  FileDropperType,
} from '@/components/file-dropper/common';
import { resizeImage } from '@/util/util';

interface Props {
  institution: Institution;
}
interface AdmissionData extends AdmissionApplication {
  files: FileList | null;
}

export default function AdmissionApplicationPage({ institution }: Props) {
  const form = useWebForm({
    reference: String(institution.id) + generateRandomString(16),
    files: {} as FileList | null,
  } as AdmissionData);
  // AdmissionApplication & {
  //   files: FileList | null;
  // });

  const { handleResponseToast } = useMyToast();
  const extensions = FileDropperType.Image.extensionLabels;
  // const { instRoute } = useInstitutionRoute();

  const [uploadedPhoto, setUploadedPhoto] = useState(null);

  async function onSubmit() {
    const res = await form.submit(async (data, web) => {
      const formData = new FormData();
      const file = data.files![0];
      const imageBlob = await resizeImage(file, 300, 300);
      formData.append('photo', imageBlob as Blob);
      Object.entries(data).map(([key, value]) => {
        if (key !== 'files') {
          formData.append(key, value);
        }
      });

      return web.post(
        route('institutions.admissions.store', [institution.uuid]),
        formData
      );
    });

    if (!handleResponseToast(res)) return;

    Inertia.visit(
      route('institutions.admissions.success', [
        institution.uuid,
        res.data.data.id,
      ])
    );
  }

  function maxYear() {
    const maxYear = new Date().getFullYear() - 3;
    return `${maxYear}-01-01`;
  }

  return (
    <Div bg={'brand.50'} minH={'100vh'}>
      <Div shadow={'md'} py={5} px={5} background={'white'}>
        <HStack align={'stretch'} spacing={5}>
          <Avatar
            src={institution.photo}
            aria-label={institution.name + ' Logo'}
          />
          <Text fontWeight={'bold'} fontSize={'2xl'}>
            {institution.name}
          </Text>
        </HStack>
      </Div>
      <Div
        bg={'white'}
        mx={'auto'}
        shadow={'md'}
        rounded={'md'}
        as={'form'}
        my={12}
        onSubmit={preventNativeSubmit(onSubmit)}
        mt={3}
        maxW={{ base: '400px', md: '950px' }}
      >
        <PageTitle py={4} px={4} fontWeight={'semibold'} fontSize={'26px'}>
          Admission Application
        </PageTitle>
        <Divider mb={2} />
        <Grid templateColumns={{ lg: 'repeat(3, 1fr)' }} gap={4}>
          <GridItem colSpan={{ lg: 2 }}>
            <VStack spacing={4} align={'stretch'} p={6}>
              <FormControlBox
                form={form as any}
                title="First Name"
                formKey="first_name"
              >
                <Input
                  type="text"
                  onChange={(e) =>
                    form.setValue('first_name', e.currentTarget.value)
                  }
                  value={form.data.first_name}
                  required
                />
              </FormControlBox>
              <FormControlBox
                form={form as any}
                title="Last Name"
                formKey="last_name"
              >
                <Input
                  type="text"
                  onChange={(e) =>
                    form.setValue('last_name', e.currentTarget.value)
                  }
                  value={form.data.last_name}
                  required
                />
              </FormControlBox>
              <FormControlBox
                form={form as any}
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
              {/* <FormControlBox form={form as any} title="Phone" formKey="phone">
            <Input
              type="phone"
              onChange={(e) => form.setValue('phone', e.currentTarget.value)}
              value={form.data.phone}
            />
          </FormControlBox>
          <FormControlBox form={form as any} title="Email" formKey="email">
            <Input
              type="email"
              onChange={(e) => form.setValue('email', e.currentTarget.value)}
              value={form.data.email}
              required
            />
          </FormControlBox> */}
              <FormControlBox
                form={form as any}
                title="Gender"
                formKey="gender"
              >
                <EnumSelect
                  enumData={Gender}
                  onChange={(e: any) => form.setValue('gender', e.value)}
                  required
                />
              </FormControlBox>

              <InputForm
                form={form as any}
                title="Date of Birth"
                formKey="dob"
                type="date"
                max={maxYear()}
              />
              <FormControlBox
                form={form as any}
                title="Religion"
                formKey="religion"
              >
                <EnumSelect
                  enumData={Religion}
                  onChange={(e: any) => form.setValue('religion', e.value)}
                  required
                />
              </FormControlBox>
              <InputForm
                form={form as any}
                title="Local Govt. Area"
                formKey="lga"
              />

              <InputForm form={form as any} title="State" formKey="state" />

              <FormControlBox
                form={form as any}
                title="Nationality"
                formKey="nationality"
              >
                <EnumSelect
                  enumData={Nationality}
                  onChange={(e: any) => form.setValue('nationality', e.value)}
                  required
                />
              </FormControlBox>

              <InputForm
                form={form as any}
                title="Intended Class of Admission"
                formKey="intended_class_of_admission"
              />

              <InputForm
                form={form as any}
                title="Previous School Attended"
                formKey="previous_school_attended"
              />

              <HStack align={'stretch'}>
                <InputForm
                  form={form as any}
                  title="Father's name"
                  formKey="fathers_name"
                />
                <Spacer />
                <InputForm
                  form={form as any}
                  title="Father's Occupation"
                  formKey="fathers_occupation"
                />
              </HStack>
              <HStack align={'stretch'}>
                <InputForm
                  form={form as any}
                  title="Father's Phone"
                  formKey="fathers_phone"
                />
                <Spacer />
                <InputForm
                  form={form as any}
                  title="Father's Email"
                  formKey="fathers_email"
                />
              </HStack>

              <InputForm
                form={form as any}
                title="Father's Residential Address"
                formKey="fathers_residential_address"
              />

              <InputForm
                form={form as any}
                title="Father's Office Address"
                formKey="fathers_office_address"
              />

              <HStack align={'stretch'}>
                <InputForm
                  form={form as any}
                  title="Mother's name"
                  formKey="mothers_name"
                />
                <Spacer />
                <InputForm
                  form={form as any}
                  title="Mother's Occupation"
                  formKey="mothers_occupation"
                />
              </HStack>

              <HStack align={'stretch'}>
                <InputForm
                  form={form as any}
                  title="Mother's Phone"
                  formKey="mothers_phone"
                />
                <Spacer />
                <InputForm
                  form={form as any}
                  title="Mother's Email"
                  formKey="mothers_email"
                />
              </HStack>

              <InputForm
                form={form as any}
                title="Mother's Residential Address"
                formKey="mothers_residential_address"
              />

              <InputForm
                form={form as any}
                title="Mother's Office Address"
                formKey="mothers_office_address"
              />

              {/* <InputForm
            form={form as any}
            title="Parents Phone"
            formKey="guardian_phone"
          />
          <InputForm form={form as any} title="Address" formKey="address" /> */}

              <FormControl>
                <FormButton isLoading={form.processing} title="Apply" />
              </FormControl>
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
                  <Avatar size={'2xl'} src={uploadedPhoto ?? form.data.photo} />
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
                      isRequired
                      onChange={(e) => {
                        const file = e.target.files?.[0];

                        if (file) {
                          const imageUrl = URL.createObjectURL(file);
                          setUploadedPhoto(imageUrl);
                        }

                        form.setValue('files', e.target.files);
                      }}
                    />
                    Upload Passport
                  </FormLabel>
                  <Text fontSize={'sm'} color={'blackAlpha.700'}>
                    Allowed extensions {extensions.join(', ')}
                  </Text>
                  <Text fontSize={'sm'} color={'blackAlpha.700'}>
                    Maximum size {Math.floor(bytesToMb(MAX_FILE_SIZE_BYTES))}MB
                  </Text>
                  <FormErrorMessage>{form.errors.photo}</FormErrorMessage>
                </Div>
              </Div>
            </FormControl>
          </GridItem>
        </Grid>
      </Div>
      <Spacer height={'30px'} />
    </Div>
  );
}
