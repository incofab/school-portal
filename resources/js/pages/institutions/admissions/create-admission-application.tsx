import { Div } from '@/components/semantic';
import { generateRandomString, preventNativeSubmit } from '@/util/util';
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
  useColorModeValue,
  Icon,
} from '@chakra-ui/react';
import React, { useState } from 'react';
import FormControlBox from '@/components/forms/form-control-box';
import EnumSelect from '@/components/dropdown-select/enum-select';
import {
  Gender,
  GuardianRelationship,
  Nationality,
  Religion,
} from '@/types/types';
import { AdmissionForm, Institution } from '@/types/models';
import InputForm from '@/components/forms/input-form';
import { BrandButton, FormButton } from '@/components/buttons';
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
import useInstitutionRoute from '@/hooks/use-institution-route';
import AdmissionFormSelect from '@/components/selectors/admission-form-select';
import { PlusIcon } from '@heroicons/react/24/outline';

interface Props {
  institution: Institution;
  admissionForms: AdmissionForm[];
}

interface GuardianProp {
  first_name: string;
  last_name: string;
  other_names: string;
  phone: string;
  email: string;
  relationship: string;
}

export default function CreateAdmissionApplication({
  institution,
  admissionForms,
}: Props) {
  const emptyGuardian = {
    first_name: '',
    last_name: '',
    other_names: '',
    phone: '',
    email: '',
    relationship: '',
  } as GuardianProp;
  const form = useWebForm({
    reference: String(institution.id) + generateRandomString(16),
    admission_form_id: '',
    first_name: '',
    last_name: '',
    other_names: '',
    gender: '',
    dob: '',
    address: '',
    religion: '',
    lga: '',
    state: '',
    nationality: '',
    intended_class_of_admission: '',
    previous_school_attended: '',
    phone: '',
    email: '',
    photo: '',
    guardians: [emptyGuardian] as GuardianProp[],
    files: {} as FileList | null,
  });

  const { handleResponseToast } = useMyToast();
  const extensions = FileDropperType.Image.extensionLabels;
  const { instRoute } = useInstitutionRoute();

  const [uploadedPhoto, setUploadedPhoto] = useState<string | null>(null);

  async function onSubmit() {
    const res = await form.submit(async (data, web) => {
      const formData = new FormData();
      const file = data.files ? data.files[0] : null;
      if (file) {
        const imageBlob = await resizeImage(file, 300, 300);
        formData.append('photo', imageBlob as Blob);
      }
      Object.entries(data).map(([key, value]) => {
        if (key === 'files' || key === 'photo') {
          return;
        }
        if (key === 'guardians') {
          (value as []).forEach((v, i) => {
            Object.entries(v).map(([key1, value1]) => {
              formData.append(`guardians[${i}][${key1}]`, String(value1));
            });
          });
          return;
        }
        formData.append(key, String(value));
      });

      return web.post(instRoute('admissions.store'), formData);
    });

    if (!handleResponseToast(res)) return;

    Inertia.visit(
      instRoute('admission-applications.preview', [
        res.data.admissionApplication.id,
      ])
    );
  }

  function maxYear() {
    const maxYear = new Date().getFullYear() - 3;
    return `${maxYear}-01-01`;
  }

  return (
    <Div bg={useColorModeValue('brand.50', 'gray.800')} minH={'100vh'}>
      <Div
        shadow={'md'}
        py={5}
        px={5}
        background={useColorModeValue('white', 'gray.900')}
      >
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
        bg={useColorModeValue('white', 'gray.900')}
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
                title="Admission Form"
                formKey="admission_form_id"
              >
                <AdmissionFormSelect
                  admissionForms={admissionForms}
                  onChange={(e: any) =>
                    form.setValue('admission_form_id', e?.value)
                  }
                  selectValue={form.data.admission_form_id}
                  isMulti={false}
                  isClearable={true}
                />
              </FormControlBox>
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
                title="Residential Address"
                formKey="address"
              />

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
                title="Intended Class of Admission [Optional]"
                formKey="intended_class_of_admission"
              />

              <InputForm
                form={form as any}
                title="Previous School Attended"
                formKey="previous_school_attended"
              />

              <Div
                border={'3px solid'}
                borderColor={'brand.50'}
                borderRadius={'5px'}
                w={'100%'}
                p={4}
                ps={10}
              >
                {form.data.guardians.map((guardian: GuardianProp, index) => (
                  <GuardianForm
                    index={index}
                    key={index}
                    guardian={guardian}
                    form={form}
                  />
                ))}
              </Div>

              <HStack align={'stretch'}>
                <Spacer />
                <BrandButton
                  leftIcon={<Icon as={PlusIcon} />}
                  title="Add New Guardian"
                  type={'button'}
                  onClick={() => {
                    form.setValue('guardians', [
                      ...form.data.guardians,
                      emptyGuardian,
                    ]);
                  }}
                />
              </HStack>
              <Divider my={2} />
              <HStack align={'stretch'}>
                <FormButton isLoading={form.processing} title="Submit Form" />
              </HStack>
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
                      name="photo_form"
                      hidden
                      accept={'image/jpeg,image/png,image/jpg'}
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

function GuardianForm({
  index,
  guardian,
  form,
}: {
  index: number;
  guardian: GuardianProp;
  form: any;
}) {
  const { toastError } = useMyToast();
  return (
    <VStack mt={10}>
      <HStack width="full" justify="flex-end" align={'stretch'}>
        <BrandButton
          title="Remove this Guardian Record"
          type={'button'}
          onClick={() => {
            const guardians = form.data.guardians ?? [];
            if (guardians.length === 1) {
              toastError('You must have at least one guardian');
              return;
            }
            guardians.splice(index, 1);
            form.setValue('guardians', guardians);
          }}
        />
      </HStack>

      <FormControlBox
        form={form as any}
        title="First Name"
        formKey="first_name"
      >
        <Input
          type="text"
          onChange={(e) => {
            const guardians = form.data.guardians;
            const g = guardians[index];
            g.first_name = e.currentTarget.value;
            form.setValue('guardians', guardians);
          }}
          value={guardian.first_name}
          required
        />
      </FormControlBox>
      <FormControlBox form={form as any} title="Last Name" formKey="last_name">
        <Input
          type="text"
          onChange={(e) => {
            const guardians = form.data.guardians;
            const g = guardians[index];
            g.last_name = e.currentTarget.value;
            form.setValue('guardians', guardians);
          }}
          value={guardian.last_name}
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
          onChange={(e) => {
            const guardians = form.data.guardians;
            const g = guardians[index];
            g.other_names = e.currentTarget.value;
            form.setValue('guardians', guardians);
          }}
          value={guardian.other_names}
        />
      </FormControlBox>
      <FormControlBox form={form as any} title="Phone" formKey="phone">
        <Input
          type="phone"
          onChange={(e) => {
            const guardians = form.data.guardians;
            const g = guardians[index];
            g.phone = e.currentTarget.value;
            form.setValue('guardians', guardians);
          }}
          value={guardian.phone}
        />
      </FormControlBox>
      <FormControlBox form={form as any} title="Email" formKey="email">
        <Input
          type="email"
          onChange={(e) => {
            const guardians = form.data.guardians;
            const g = guardians[index];
            g.email = e.currentTarget.value;
            form.setValue('guardians', guardians);
          }}
          value={guardian.email}
          required
        />
      </FormControlBox>

      <FormControlBox
        form={form as any}
        title="Relationship"
        formKey="relationship"
      >
        <EnumSelect
          enumData={GuardianRelationship}
          selectValue={guardian.relationship}
          onChange={(e: any) => {
            //const guardians = form.data.guardians;
            //const g = guardians[index];
            guardian.relationship = e.value;
            form.setValue('guardians', form.data.guardians);
          }}
          required
        />
      </FormControlBox>
    </VStack>
  );
}
