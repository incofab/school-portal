import useIsAdmin from '@/hooks/use-is-admin';
import { WebForm } from '@/hooks/use-web-form';
import { Student } from '@/types/models';
import { AdmissionYear, ProgrammeType, UserRoleType } from '@/types/types';
import {
  Checkbox,
  Divider,
  FormControl,
  FormErrorMessage,
  FormLabel,
  Input,
  Select,
  Text,
} from '@chakra-ui/react';
import startCase from 'lodash/startCase';
import React from 'react';
import DepartmentSelect from './department-select';
import ReactSelect from './react-select';
import { Div } from './semantic';

interface Props {
  webForm: WebForm<{
    first_name: string;
    last_name: string;
    other_names: string;
    email: string;
    phone: string;
    role: string;
    is_welfare: boolean;
    reg_no?: string;
    department_id?: string;
    admission_year?: AdmissionYear;
    programme?: ProgrammeType;
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
          required
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
      {isAdmin && (
        <>
          <FormControl isRequired isInvalid={!!webForm.errors.role}>
            <FormLabel>Role</FormLabel>
            <Select
              onChange={(e) => webForm.setValue('role', e.currentTarget.value)}
              value={webForm.data.role}
              required
            >
              <option></option>
              {Object.entries(UserRoleType).map(([key, value]) => (
                <option key={key} value={value}>
                  {startCase(value.replaceAll('-', ' '))}
                </option>
              ))}
            </Select>
            <FormErrorMessage>{webForm.errors.role}</FormErrorMessage>
          </FormControl>
          <FormControl isRequired isInvalid={!!webForm.errors.is_welfare}>
            <FormLabel>Welfare</FormLabel>
            <Checkbox
              isChecked={webForm.data.is_welfare}
              onChange={(e) =>
                webForm.setValue('is_welfare', e.currentTarget.checked)
              }
              isRequired={false}
            >
              Is Welfare Officer?
            </Checkbox>
            <FormErrorMessage>{webForm.errors.is_welfare}</FormErrorMessage>
          </FormControl>
        </>
      )}
      {(webForm.data.role === UserRoleType.Student ||
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
          <FormControl isRequired isInvalid={!!webForm.errors.reg_no}>
            <FormLabel>Reg. Number</FormLabel>
            <Input
              type="text"
              onChange={(e) =>
                webForm.setValue('reg_no', e.currentTarget.value.toUpperCase())
              }
              value={webForm.data.reg_no}
              placeholder="Eg. 22/B.TH/SW/A/277"
              required
            />
            <FormErrorMessage>{webForm.errors.reg_no}</FormErrorMessage>
          </FormControl>

          <FormControl isRequired isInvalid={!!webForm.errors.department_id}>
            <FormLabel>Department</FormLabel>
            <DepartmentSelect
              selectValue={webForm.data.department_id}
              onChange={(e: any) => webForm.setValue('department_id', e.value)}
            />
            <FormErrorMessage>{webForm.errors.department_id}</FormErrorMessage>
          </FormControl>

          <FormControl isRequired isInvalid={!!webForm.errors.programme}>
            <FormLabel>Programme</FormLabel>
            <ReactSelect
              selectValue={webForm.data.programme}
              data={{
                main: Object.entries(ProgrammeType),
                label: '0',
                value: '1',
              }}
              isLoading={webForm.processing}
              onChange={(e: any) => webForm.setValue('programme', e.value)}
            />
            <FormErrorMessage>{webForm.errors.programme}</FormErrorMessage>
          </FormControl>

          <FormControl isRequired isInvalid={!!webForm.errors.admission_year}>
            <FormLabel>Admission Year</FormLabel>
            <ReactSelect
              selectValue={webForm.data.admission_year}
              data={{
                main: Object.entries(AdmissionYear),
                label: '1',
                value: '1',
              }}
              isLoading={webForm.processing}
              onChange={(e: any) => webForm.setValue('admission_year', e.value)}
            />
            <FormErrorMessage>{webForm.errors.admission_year}</FormErrorMessage>
          </FormControl>
        </>
      )}
    </>
  );
}
