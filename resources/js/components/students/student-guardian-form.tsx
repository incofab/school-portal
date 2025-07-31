import { WebForm } from '@/hooks/use-web-form';
import { Student, User } from '@/types/models';
import { Div } from '../semantic';
import {
  FormControl,
  FormLabel,
  HStack,
  Input,
  Select,
  Text,
} from '@chakra-ui/react';
import { Gender, GuardianRelationship } from '@/types/types';

interface FormRecord {
  [student_id: number]: User & {
    relationship: string;
  };
}

export function StudentGuardianForm({
  student,
  webForm,
}: {
  student: Student;
  webForm: WebForm<{ guardians: FormRecord }, Record<'guardians', string>>;
}) {
  const guardian = webForm.data.guardians[student.id] ?? {};
  if (student.guardian) {
    return (
      <Div
        width={'full'}
        border={'1px solid'}
        p={5}
        my={2}
        borderColor={'blackAlpha.200'}
      >
        <Div>
          <b>Student:</b> {student.user?.full_name}
        </Div>
        <Div mt={2}>
          <b>Guardian:</b> {student.guardian?.full_name}
        </Div>
      </Div>
    );
  }
  return (
    <Div border={'1px solid'} p={5} my={2} borderColor={'blackAlpha.200'}>
      <Text as={'p'} fontWeight={'semibold'} mb={3}>
        Student: {student.user?.full_name}
      </Text>
      <HStack spacing={2}>
        <FormControl>
          <FormLabel>First Name</FormLabel>
          <Input
            type="text"
            value={guardian.first_name}
            onChange={(e) => {
              guardian.first_name = e.currentTarget.value;
              webForm.setValue('guardians', {
                ...webForm.data.guardians,
                [student.id]: guardian,
              });
            }}
          />
          {/* <FormErrorMessage>{form.errors[formKey]}</FormErrorMessage> */}
        </FormControl>
        <FormControl>
          <FormLabel>Last Name</FormLabel>
          <Input
            type="text"
            value={guardian.last_name}
            onChange={(e) => {
              guardian.last_name = e.currentTarget.value;
              webForm.setValue('guardians', {
                ...webForm.data.guardians,
                [student.id]: guardian,
              });
            }}
          />
        </FormControl>
        <FormControl>
          <FormLabel>Other Names</FormLabel>
          <Input
            type="text"
            value={guardian.other_names}
            onChange={(e) => {
              guardian.other_names = e.currentTarget.value;
              webForm.setValue('guardians', {
                ...webForm.data.guardians,
                [student.id]: guardian,
              });
            }}
          />
        </FormControl>
      </HStack>
      <HStack spacing={2} my={2}>
        <FormControl>
          <FormLabel>Phone</FormLabel>
          <Input
            type="text"
            value={guardian.phone}
            onChange={(e) => {
              guardian.phone = e.currentTarget.value;
              webForm.setValue('guardians', {
                ...webForm.data.guardians,
                [student.id]: guardian,
              });
            }}
          />
          {/* <FormErrorMessage>{form.errors[formKey]}</FormErrorMessage> */}
        </FormControl>
        <FormControl>
          <FormLabel>Email</FormLabel>
          <Input
            type="email"
            value={guardian.email}
            onChange={(e) => {
              guardian.email = e.currentTarget.value;
              webForm.setValue('guardians', {
                ...webForm.data.guardians,
                [student.id]: guardian,
              });
            }}
          />
        </FormControl>
        <FormControl>
          <FormLabel>Gender</FormLabel>
          <Select
            size={'sm'}
            value={guardian.gender}
            onChange={(e) => {
              guardian.gender = e.currentTarget.value;
              webForm.setValue('guardians', {
                ...webForm.data.guardians,
                [student.id]: guardian,
              });
            }}
          >
            <option value={''}>Select Gender</option>
            {Object.entries(Gender).map(([key, value]) => (
              <option key={value} value={value}>
                {key}
              </option>
            ))}
          </Select>
        </FormControl>
      </HStack>
      <HStack>
        <FormControl>
          <FormLabel>Relationship</FormLabel>
          <Select
            size={'sm'}
            value={guardian.relationship}
            onChange={(e) => {
              guardian.relationship = e.currentTarget.value;
              webForm.setValue('guardians', {
                ...webForm.data.guardians,
                [student.id]: guardian,
              });
            }}
          >
            <option value={''}>Select Relationship</option>
            {Object.entries(GuardianRelationship).map(([key, value]) => (
              <option key={value} value={value}>
                {key}
              </option>
            ))}
          </Select>
        </FormControl>
      </HStack>
    </Div>
  );
}
