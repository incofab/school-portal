import { Div } from '@/components/semantic';
import { Avatar, HStack, Text, Icon, VStack } from '@chakra-ui/react';
import { ListItem, OrderedList } from '@chakra-ui/react';
import React from 'react';
import { InstitutionUser, Student, User } from '@/types/models';
import useSharedProps from '@/hooks/use-shared-props';
import ImagePaths from '@/util/images';
import { CloudArrowDownIcon } from '@heroicons/react/24/solid';
import { BrandButton } from '@/components/buttons';
import { Preview, print } from 'react-html2pdf';

interface Props {
  student?: Student & {
    user: User & {
      institution_user: InstitutionUser;
    };
  };
}

export default function Profile({ student }: Props) {
  const { currentInstitution } = useSharedProps();

  return (
    <>
      <Preview id={'jsx-template'}>
        <Div minHeight={'700px'} id="content-to-export">
          <Div mx={'auto'} width={'720px'} px={3}>
            <VStack align={'stretch'}>
              <HStack background={'#FAFAFA'} p={2}>
                <Avatar
                  size={'2xl'}
                  name="Institution logo"
                  src={
                    currentInstitution.photo ?? ImagePaths.default_school_logo
                  }
                />
                <VStack spacing={1} align={'stretch'} width={'full'}>
                  <Text
                    fontSize={'3xl'}
                    fontWeight={'bold'}
                    textAlign={'center'}
                  >
                    {currentInstitution.name}
                  </Text>
                  <Text
                    textAlign={'center'}
                    fontSize={'18px'}
                    whiteSpace={'nowrap'}
                  >
                    {currentInstitution.address}
                    <br /> {currentInstitution.email}
                  </Text>
                  <Text
                    fontWeight={'semibold'}
                    textAlign={'center'}
                    fontSize={'18px'}
                  >
                    Acceptance of Provisional Admission
                  </Text>
                </VStack>
                <Avatar
                  size={'2xl'}
                  name="Student logo"
                  src={student?.user!.photo}
                />
              </HStack>

              <VStack
                spacing={2}
                align={'stretch'}
                width={'full'}
                fontSize={'17px'}
                py={7}
              >
                <Text fontWeight={'bold'}>
                  {student?.user!.gender === 'male' ? 'Master ' : 'Miss '}
                  {student?.user!.last_name} {student?.user!.first_name}
                </Text>
                <Text
                  textAlign={'center'}
                  fontWeight={'bold'}
                  fontSize={'20px'}
                  py={4}
                >
                  OFFER OF PROVISIONAL ADMISSION
                </Text>
                <Text>
                  I am pleased to inform you that following your good
                  performance, you have been offered provisional admission into{' '}
                  {student?.classification!.title} in {currentInstitution.name}{' '}
                  .
                </Text>
                <Text>
                  If you accept the offer, you are required to pay acceptance
                  fee as well as other charges and present the following during
                  registration:
                  <OrderedList>
                    <ListItem>
                      Two recent passport size photographs with names written
                      boldly on the reverse side
                    </ListItem>
                    <ListItem>Testimonial</ListItem>
                    <ListItem>Birth Certificate</ListItem>
                    <ListItem>Acceptance Form</ListItem>
                    <ListItem>Your school fees receipt</ListItem>
                  </OrderedList>
                </Text>
                <Text>
                  Please accept my congratulations on behalf of the UPWA Group
                  of Schools on your admission.
                </Text>
                <Text>
                  Yours Faithfully,
                  <br />
                  Principal.
                </Text>
              </VStack>
            </VStack>
          </Div>
        </Div>
      </Preview>

      <Div mx={'auto'} width={'250px'} px={3}>
        <BrandButton
          leftIcon={<Icon as={CloudArrowDownIcon} />}
          onClick={() => print('a', 'jsx-template')}
          title="Download"
          width={'100%'}
        />
      </Div>
    </>
  );
}

// Profile.layout = (page: any) => <DashboardLayout children={page} />;
