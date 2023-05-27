import React from 'react';
import { Link, useForm } from '@inertiajs/inertia-react';
import {
  Box,
  Center,
  Input,
  FormControl,
  FormLabel,
  Button,
  Show,
  HStack,
  FormErrorMessage,
  VStack,
} from '@chakra-ui/react';
import '../../../css/app.css';
import { preventNativeSubmit } from '@/util/util';
import route from '@/util/route';
import DepartmentSelect from '@/components/department-select';
import ReactSelect from '@/components/react-select';
import { AdmissionYear, ProgrammeType } from '@/types/types';

export default function RegisterStudent() {
  const form = useForm({
    first_name: '',
    last_name: '',
    other_names: '',
    phone: '',
    email: '',
    reg_no: '',
    department_id: '',
    programme: '',
    admission_year: '',
    password: '',
    password_confirmation: '',
  });

  async function handleSubmit() {
    form.post(route('students.register.store'));
  }

  return (
    <Box className="pageContainer flex">
      <Show above="md">
        <Box className="authPage-Left"></Box>
      </Show>

      <Box className="authPage-Right">
        <Box className="formContainer" mt={2} mb={2}>
          <Box className="subTitle" mb={8}>
            Student Registration
          </Box>
          <VStack
            as={'form'}
            onSubmit={preventNativeSubmit(handleSubmit)}
            spacing={4}
          >
            <HStack>
              <FormControl isRequired isInvalid={!!form.errors.first_name}>
                <FormLabel>First Name</FormLabel>
                <Input
                  type="text"
                  onChange={(e) =>
                    form.setData('first_name', e.currentTarget.value)
                  }
                  value={form.data.first_name}
                  required
                />
                <FormErrorMessage>{form.errors.first_name}</FormErrorMessage>
              </FormControl>
              <FormControl isRequired isInvalid={!!form.errors.last_name}>
                <FormLabel>Last Name/Surname</FormLabel>
                <Input
                  type="text"
                  onChange={(e) =>
                    form.setData('last_name', e.currentTarget.value)
                  }
                  value={form.data.last_name}
                  required
                />
                <FormErrorMessage>{form.errors.last_name}</FormErrorMessage>
              </FormControl>
            </HStack>

            <FormControl isInvalid={!!form.errors.other_names}>
              <FormLabel>Other Names</FormLabel>
              <Input
                type="text"
                onChange={(e) =>
                  form.setData('other_names', e.currentTarget.value)
                }
                value={form.data.other_names}
              />
              <FormErrorMessage>{form.errors.other_names}</FormErrorMessage>
            </FormControl>

            <HStack>
              <FormControl isInvalid={!!form.errors.phone}>
                <FormLabel>Phone Number</FormLabel>
                <Input
                  type="phone"
                  onChange={(e) => form.setData('phone', e.currentTarget.value)}
                  value={form.data.phone}
                />
                <FormErrorMessage>{form.errors.phone}</FormErrorMessage>
              </FormControl>
              <FormControl isRequired isInvalid={!!form.errors.email}>
                <FormLabel>Email</FormLabel>
                <Input
                  type="email"
                  onChange={(e) => form.setData('email', e.currentTarget.value)}
                  value={form.data.email}
                  required
                />
                <FormErrorMessage>{form.errors.email}</FormErrorMessage>
              </FormControl>
            </HStack>

            <FormControl isRequired isInvalid={!!form.errors.reg_no}>
              <FormLabel>Reg. Number</FormLabel>
              <Input
                type="text"
                onChange={(e) =>
                  form.setData('reg_no', e.currentTarget.value.toUpperCase())
                }
                value={form.data.reg_no}
                placeholder="Eg. 22/B.TH/SW/A/277"
                required
              />
              <FormErrorMessage>{form.errors.reg_no}</FormErrorMessage>
            </FormControl>

            <FormControl isRequired isInvalid={!!form.errors.department_id}>
              <FormLabel>Department</FormLabel>
              <DepartmentSelect
                selectValue={form.data.department_id}
                onChange={(e: any) => form.setData('department_id', e.value)}
              />
              <FormErrorMessage>{form.errors.department_id}</FormErrorMessage>
            </FormControl>

            <FormControl isRequired isInvalid={!!form.errors.programme}>
              <FormLabel>Programme</FormLabel>
              <ReactSelect
                selectValue={form.data.programme}
                data={{
                  main: Object.entries(ProgrammeType),
                  label: '0',
                  value: '1',
                }}
                isLoading={form.processing}
                onChange={(e: any) => form.setData('programme', e.value)}
              />
              <FormErrorMessage>{form.errors.programme}</FormErrorMessage>
            </FormControl>

            <FormControl isRequired isInvalid={!!form.errors.admission_year}>
              <FormLabel>Admission Year</FormLabel>
              <ReactSelect
                selectValue={form.data.admission_year}
                data={{
                  main: Object.entries(AdmissionYear),
                  label: '1',
                  value: '1',
                }}
                isLoading={form.processing}
                onChange={(e: any) => form.setData('admission_year', e.value)}
              />
              <FormErrorMessage>{form.errors.admission_year}</FormErrorMessage>
            </FormControl>

            <HStack>
              <FormControl isRequired isInvalid={!!form.errors.password}>
                <FormLabel>New Password</FormLabel>
                <Input
                  type="password"
                  onChange={(e) =>
                    form.setData('password', e.currentTarget.value)
                  }
                  value={form.data.password}
                  required
                />
                <FormErrorMessage>{form.errors.password}</FormErrorMessage>
              </FormControl>
              <FormControl
                isRequired
                isInvalid={!!form.errors.password_confirmation}
              >
                <FormLabel>Confirm Password:</FormLabel>
                <Input
                  type="password"
                  onChange={(e) =>
                    form.setData('password_confirmation', e.currentTarget.value)
                  }
                  value={form.data.password_confirmation}
                  required
                />
                <FormErrorMessage>
                  {form.errors.password_confirmation}
                </FormErrorMessage>
              </FormControl>
            </HStack>

            <FormControl>
              <Center>
                <Button
                  mt={10}
                  mb={5}
                  colorScheme="brand"
                  type="submit"
                  style={{ width: '100%', maxWidth: '200px' }}
                  isLoading={form.processing}
                  loadingText="Submitting"
                >
                  Submit
                </Button>
              </Center>
            </FormControl>
          </VStack>

          <Box ml={5}>
            <ul>
              <li>
                <Link
                  href={route('students.register.create')}
                  className="authPgLink"
                >
                  Already Registered? - Login
                </Link>
              </li>
              <li>
                <Link href={route('forgot-password')} className="authPgLink">
                  Forgot Your Password? - Reset
                </Link>
              </li>
            </ul>
          </Box>
        </Box>
      </Box>
    </Box>
  );
}
