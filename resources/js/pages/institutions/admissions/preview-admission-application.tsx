import { Div } from '@/components/semantic';
import {
  Avatar,
  BoxProps,
  Divider,
  Flex,
  HStack,
  Text,
  useColorModeValue,
  VStack,
} from '@chakra-ui/react';
import React from 'react';
import { AdmissionApplication, Institution } from '@/types/models';
import { PageTitle } from '@/components/page-header';
import { AdmissionStatusType } from '@/types/types';

interface Props {
  institution: Institution;
  admissionApplication: AdmissionApplication;
}

export default function PreviewAdmissionApplication({
  institution,
  admissionApplication,
}: Props) {
  const admissionForm = admissionApplication.admission_form;
  return (
    <Div bg={useColorModeValue('white', 'gray.900')} minH={'100vh'}>
      <Div pt={5} px={5} mt={3}>
        <HStack align={'stretch'} spacing={5} justifyContent={'center'}>
          <Avatar
            src={institution.photo}
            aria-label={institution.name + ' Logo'}
          />
          <Div textAlign={'center'} color={'brand.700'}>
            <Text fontWeight={'bold'} fontSize={'3xl'}>
              {institution.name}
            </Text>
            <Text fontSize={'2xl'}>{institution.caption}</Text>
          </Div>
        </HStack>
      </Div>
      <Div mx={'auto'} rounded={'md'} my={3} w={'900px'}>
        <PageTitle
          px={4}
          fontWeight={'semibold'}
          fontSize={'20px'}
          textAlign={'center'}
        >
          {admissionForm && (
            <Div fontWeight={'bold'} fontSize={'24px'}>{`${
              admissionForm!.title
            } ${
              admissionForm.academic_session
                ? ''
                : `${admissionForm.term} ${
                    admissionForm.academic_session!.title
                  }`
            }`}</Div>
          )}
          <Div mt={2}>
            Application Number:{' '}
            <Text as={'span'}>{admissionApplication.application_no}</Text>
          </Div>
          <Div mt={2}>
            Status:{' '}
            <Text
              as={'span'}
              color={
                admissionApplication.admission_status ==
                AdmissionStatusType.Admitted
                  ? 'green'
                  : 'red'
              }
            >
              {admissionApplication.admission_status}
            </Text>
          </Div>
        </PageTitle>
        <Divider my={4} />
        <Div px={3}>
          <Flex gap={4}>
            <Div flex={1}>
              <VStack align={'stretch'} spacing={4}>
                <Flex gap={3}>
                  <Item
                    flex={1}
                    label={'Firstname'}
                    text={admissionApplication.first_name}
                  />
                  <Item
                    flex={1}
                    label={'Lastname'}
                    text={admissionApplication.last_name}
                  />
                  <Item
                    flex={1}
                    label={'Other Names'}
                    text={admissionApplication.other_names}
                  />
                </Flex>
                <Flex gap={3}>
                  <Item
                    flex={1}
                    label={'Gender'}
                    text={admissionApplication.gender}
                  />
                  <Item
                    flex={1}
                    label={'Date of Birth'}
                    text={admissionApplication.dob}
                  />
                  <Item
                    flex={1}
                    label={'Phone'}
                    text={admissionApplication.phone}
                  />
                </Flex>
              </VStack>
            </Div>
            <Avatar
              w={'100px'}
              h={'100px'}
              src={admissionApplication.photo_url}
              aria-label={admissionApplication.name}
            />
          </Flex>
          <br />
          <VStack align={'stretch'} spacing={4}>
            <Flex gap={3}>
              <Item
                flex={1}
                label={'Nationality'}
                text={admissionApplication.nationality}
              />
              <Item
                flex={1}
                label={'LGA'}
                text={`${admissionApplication.lga} ${admissionApplication.state}`}
              />
              <Item
                flex={1}
                label={'Religion'}
                text={admissionApplication.religion}
              />
            </Flex>
            <Flex gap={3}>
              <Item
                flex={1}
                label={'Intended Class'}
                text={admissionApplication.intended_class_of_admission}
              />
              <Item
                flex={1}
                label={'Previous School Attended'}
                text={admissionApplication.previous_school_attended}
              />
              <Item
                flex={1}
                label={'Address'}
                text={admissionApplication.address}
              />
            </Flex>
            <Text mt={2} fontWeight={'bold'}>
              Guardians
            </Text>
            <hr />
            {admissionApplication.application_guardians!.map((guardian) => (
              <VStack px={3} align={'stretch'} spacing={3}>
                <Flex gap={3}>
                  <Item
                    flex={1}
                    label={'Firstname'}
                    text={guardian.first_name}
                  />
                  <Item flex={1} label={'Lastname'} text={guardian.last_name} />
                  <Item
                    flex={1}
                    label={'Other names'}
                    text={guardian.other_names}
                  />
                </Flex>
                <Flex gap={3} mt={2}>
                  <Item flex={1} label={'Phone'} text={guardian.phone} />
                  <Item flex={1} label={'Email'} text={guardian.email} />
                  <Item
                    flex={1}
                    label={'Relationship'}
                    text={guardian.relationship}
                  />
                </Flex>
                <hr />
              </VStack>
            ))}
          </VStack>
        </Div>
      </Div>
    </Div>
  );
}

function Item({
  label,
  text,
  ...props
}: {
  label: String;
  text: string | number;
} & BoxProps) {
  return (
    <Div {...props}>
      <Text as={'p'} fontWeight={'bold'}>
        {label}:
      </Text>
      <Text as={'p'}>{text}</Text>
    </Div>
  );
}
