import useIsAdmin from '@/hooks/use-is-admin';
import { WebForm } from '@/hooks/use-web-form';
import { Gender, InstitutionUserType } from '@/types/types';
import {
  FormControl,
  FormErrorMessage,
  FormLabel,
  Input,
} from '@chakra-ui/react';
import React from 'react';
import EnumSelect from './dropdown-select/enum-select';

interface Props {
  webForm: WebForm<{
    first_name: string;
    last_name: string;
    other_names: string;
    email: string;
    phone: string;
    role: string;
    gender: string;
    // classification_id?: string;
    // guardian_phone?: string;
  }>;
}

export default function UserInputForm({ webForm }: Props) {
  const isAdmin = useIsAdmin();
  return (
    <>
      <FormControl isRequired isInvalid={!!webForm.errors.first_name}>
        <FormLabel>First Name</FormLabel>
        <Input
          type={'text'}
          onChange={(e) =>
            webForm.setValue('first_name', e.currentTarget.value)
          }
          value={webForm.data.first_name}
          required
        />
        <FormErrorMessage>{webForm.errors.first_name}</FormErrorMessage>
      </FormControl>
      <FormControl isRequired isInvalid={!!webForm.errors.last_name}>
        <FormLabel>Last Name</FormLabel>
        <Input
          type={'text'}
          onChange={(e) => webForm.setValue('last_name', e.currentTarget.value)}
          value={webForm.data.last_name}
          required
        />
        <FormErrorMessage>{webForm.errors.last_name}</FormErrorMessage>
      </FormControl>
      <FormControl isInvalid={!!webForm.errors.other_names}>
        <FormLabel>Other Names</FormLabel>
        <Input
          type={'text'}
          onChange={(e) =>
            webForm.setValue('other_names', e.currentTarget.value)
          }
          value={webForm.data.other_names}
        />
        <FormErrorMessage>{webForm.errors.other_names}</FormErrorMessage>
      </FormControl>

      <FormControl isRequired isInvalid={!!webForm.errors.email}>
        <FormLabel>Email</FormLabel>
        <Input
          type={'text'}
          onChange={(e) => webForm.setValue('email', e.currentTarget.value)}
          value={webForm.data.email}
        />
        <FormErrorMessage>{webForm.errors.email}</FormErrorMessage>
      </FormControl>

      <FormControl isInvalid={!!webForm.errors.phone}>
        <FormLabel>Phone</FormLabel>
        <Input
          type={'text'}
          onChange={(e) => webForm.setValue('phone', e.currentTarget.value)}
          value={webForm.data.phone}
        />
        <FormErrorMessage>{webForm.errors.phone}</FormErrorMessage>
      </FormControl>

      <FormControl isInvalid={!!webForm.errors.gender}>
        <FormLabel>Gender</FormLabel>
        <EnumSelect
          enumData={Gender}
          onChange={(e: any) => webForm.setValue('gender', e.value)}
          value={webForm.data.gender}
        />
        <FormErrorMessage>{webForm.errors.phone}</FormErrorMessage>
      </FormControl>

      {isAdmin && (
        <>
          <FormControl isRequired isInvalid={!!webForm.errors.role}>
            <FormLabel>Role</FormLabel>
            <EnumSelect
              enumData={InstitutionUserType}
              onChange={(e: any) => webForm.setValue('role', e.value)}
              selectValue={webForm.data.role}
              required
            />
            <FormErrorMessage>{webForm.errors.role}</FormErrorMessage>
          </FormControl>
        </>
      )}
      {/* {(webForm.data.role === UserRoleType.Student ||
        webForm.data.role === UserRoleType.Alumni) && (
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
          <InputForm
            form={webForm as any}
            formKey="guardian_phone"
            title="Guardian Phone"
          />
        </>
      )} */}
    </>
  );
}
