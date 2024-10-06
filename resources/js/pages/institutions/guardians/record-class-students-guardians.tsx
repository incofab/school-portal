import React from 'react';
import {
  FormControl,
  FormLabel,
  HStack,
  Input,
  Select,
  Text,
  VStack,
} from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import useWebForm, { WebForm } from '@/hooks/use-web-form';
import { preventNativeSubmit } from '@/util/util';
import { Inertia } from '@inertiajs/inertia';
import { Classification, Student, User } from '@/types/models';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import CenteredBox from '@/components/centered-box';
import { FormButton } from '@/components/buttons';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { Gender, GuardianRelationship } from '@/types/types';
import { Div } from '@/components/semantic';

interface Props {
  students: Student[];
  classification: Classification;
}

interface FormRecord {
  [student_id: number]: User & {
    relationship: string;
  };
}

export default function RecordClassStudentsGuardians({
  students,
  classification,
}: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const webForm = useWebForm({
    guardians: {} as FormRecord,
  });

  const submit = async () => {
    const res = await webForm.submit((data, web) =>
      web.post(
        instRoute('guardians.classifications.store', [classification]),
        data
      )
    );
    if (!handleResponseToast(res)) return;
    Inertia.visit(instRoute('guardians.index'));
  };

  return (
    <DashboardLayout>
      <Div>
        <CenteredBox maxW={'700px'}>
          <Slab>
            <SlabHeading
              title={`Record Guardians for ${classification.title} students`}
            />
            <SlabBody>
              <VStack
                spacing={4}
                as={'form'}
                onSubmit={preventNativeSubmit(submit)}
              >
                {students.map((student) =>
                  studentGuardianForm(student, webForm)
                )}
                <FormControl>
                  <FormButton isLoading={webForm.processing} />
                </FormControl>
              </VStack>
            </SlabBody>
          </Slab>
        </CenteredBox>
      </Div>
    </DashboardLayout>
  );
}

function studentGuardianForm(
  student: Student,
  webForm: WebForm<
    {
      guardians: FormRecord;
    },
    Record<'guardians', string>
  >
) {
  const guardian = webForm.data.guardians[student.id] ?? {};
  if (student.guardian) {
    return (
      <Div width={'full'}>
        <Text as={'p'}>
          <b>Student:</b> {student.user?.full_name}
        </Text>
        <Text as={'p'}>
          <b>Guardian:</b> {student.guardian?.full_name}
        </Text>
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
