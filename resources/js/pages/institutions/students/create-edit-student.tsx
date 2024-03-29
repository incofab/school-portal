import React from 'react';
import { AxiosInstance } from 'axios';
import {
  Divider,
  FormControl,
  FormErrorMessage,
  FormLabel,
  Text,
  VStack,
} from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import useWebForm from '@/hooks/use-web-form';
import { preventNativeSubmit } from '@/util/util';
import { Inertia } from '@inertiajs/inertia';
import UserInputForm from '@/components/user-input-form';
import { InstitutionUser, Student, User } from '@/types/models';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import CenteredBox from '@/components/centered-box';
import { FormButton } from '@/components/buttons';
import useMyToast from '@/hooks/use-my-toast';
import { Div } from '@/components/semantic';
import FormControlBox from '@/components/forms/form-control-box';
import ClassificationSelect from '@/components/selectors/classification-select';
import InputForm from '@/components/forms/input-form';
import { InstitutionUserType } from '@/types/types';
import useInstitutionRoute from '@/hooks/use-institution-route';
import EnumSelect from '@/components/dropdown-select/enum-select';

interface Props {
  student?: Student & {
    user: User & {
      institution_user: InstitutionUser;
    };
  };
}

export default function CreateOrUpdateStudent({ student }: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const webForm = useWebForm({
    first_name: student?.user!.first_name ?? '',
    last_name: student?.user!.last_name ?? '',
    other_names: student?.user!.other_names ?? '',
    email: student?.user!.email ?? '',
    phone: student?.user!.phone ?? '',
    gender: student?.user!.gender ?? '',
    role: student?.user!.institution_user.role ?? InstitutionUserType.Student,
    guardian_phone: student?.guardian_phone ?? '',
    classification_id: student?.classification_id + '',
  });

  const forEdit = student !== undefined;

  const submit = async () => {
    const res = await webForm.submit((data, web: AxiosInstance) =>
      student
        ? web.put(instRoute('students.update', [student]), data)
        : web.post(instRoute('students.store'), {
            ...data,
            password: 'password',
            password_confirmation: 'password',
          })
    );

    if (!handleResponseToast(res)) return;

    Inertia.visit(instRoute('students.index'));
  };

  return (
    <DashboardLayout>
      <CenteredBox>
        <Slab w={'full'}>
          <SlabHeading
            title={`${student ? 'Update' : 'Create'} Student Record`}
          />
          <SlabBody>
            <VStack
              spacing={4}
              as={'form'}
              onSubmit={preventNativeSubmit(submit)}
            >
              <UserInputForm webForm={webForm as any} forEdit={forEdit} />

              {(webForm.data.role === InstitutionUserType.Student ||
                webForm.data.role === InstitutionUserType.Alumni) && (
                <>
                  <Div width={'full'}>
                    <Text
                      fontWeight={'semibold'}
                      fontSize={'md'}
                      mt={3}
                      textAlign={'center'}
                    >
                      Student Data
                    </Text>
                    <Divider />
                  </Div>
                  {!forEdit && (
                    <>
                      <FormControl isRequired isInvalid={!!webForm.errors.role}>
                        <FormLabel>Role</FormLabel>
                        <EnumSelect
                          enumData={InstitutionUserType}
                          allowedEnum={[
                            InstitutionUserType.Student,
                            InstitutionUserType.Alumni,
                          ]}
                          onChange={(e: any) =>
                            webForm.setValue('role', e.value)
                          }
                          selectValue={webForm.data.role}
                          required
                        />
                        <FormErrorMessage>
                          {webForm.errors.role}
                        </FormErrorMessage>
                      </FormControl>
                      <FormControlBox
                        isRequired
                        formKey="classification_id"
                        title="Class"
                        form={webForm}
                      >
                        <ClassificationSelect
                          selectValue={webForm.data.classification_id}
                          onChange={(e: any) =>
                            webForm.setValue('classification_id', e.value)
                          }
                        />
                      </FormControlBox>
                    </>
                  )}
                  <InputForm
                    form={webForm as any}
                    formKey="guardian_phone"
                    title="Guardian Phone"
                  />
                </>
              )}
              <FormControl>
                <FormButton isLoading={webForm.processing} />
              </FormControl>
            </VStack>
          </SlabBody>
        </Slab>
      </CenteredBox>
    </DashboardLayout>
  );
}
