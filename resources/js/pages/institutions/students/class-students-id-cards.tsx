import React, { useState } from 'react';
import { Classification, Student } from '@/types/models';
import { Div } from '@/components/semantic';
import {
  Avatar,
  Badge,
  Box,
  Button,
  Container,
  FormControl,
  FormLabel,
  HStack,
  Image,
  SimpleGrid,
  Spinner,
  Text,
  VStack,
  useColorModeValue,
} from '@chakra-ui/react';
import useSharedProps from '@/hooks/use-shared-props';
import ImagePaths from '@/util/images';
// import QRCode from 'react-qr-code';
import { QRCodeSVG } from 'qrcode.react';
import ClassificationSelect from '@/components/selectors/classification-select';
import { SelectOptionType } from '@/types/types';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { Inertia } from '@inertiajs/inertia';

interface Props {
  classification?: Classification;
  classifications: Classification[];
  students: Student[];
}

export default function ClassStudentTiles({
  classification,
  classifications,
  students,
}: Props) {
  const { currentInstitution } = useSharedProps();
  const { instRoute } = useInstitutionRoute();
  const [isChangingClass, setIsChangingClass] = useState(false);

  function selectClass(option: SelectOptionType<number> | null) {
    if (!option?.value || option.value === classification?.id) {
      return;
    }

    setIsChangingClass(true);
    Inertia.visit(instRoute('students.idcards', [option.value]), {
      onFinish: () => setIsChangingClass(false),
    });
  }

  return (
    <Div textAlign={'center'} bg={useColorModeValue('white', 'gray.900')}>
      <VStack
        align={'stretch'}
        spacing={6}
        bg={useColorModeValue('white', 'gray.900')}
        minH={'100vh'}
      >
        <Container
          maxW={'5xl'}
          className="hidden-on-print"
          pt={{ base: 4, md: 6 }}
        >
          <Box
            borderWidth={'1px'}
            borderRadius={'lg'}
            p={{ base: 4, md: 5 }}
            bg={useColorModeValue('white', 'gray.800')}
            shadow={'sm'}
          >
            <SimpleGrid columns={{ base: 1, md: 3 }} spacing={4}>
              <Box>
                <HStack
                  spacing={3}
                  align={'center'}
                  justify={{ base: 'center', md: 'flex-start' }}
                  flexWrap={'wrap'}
                >
                  <Text fontSize={'lg'} fontWeight={'semibold'}>
                    Student ID Cards
                  </Text>
                  {classification && (
                    <Badge
                      colorScheme={'brand'}
                      borderRadius={'full'}
                      px={3}
                      py={1}
                      textTransform={'none'}
                    >
                      {classification.title}
                    </Badge>
                  )}
                </HStack>
                <Text color={'gray.600'} fontSize={'sm'} mt={1}>
                  Select a class to preview and print student ID cards.
                </Text>
              </Box>

              <FormControl gridColumn={{ base: 'auto', md: 'span 1' }}>
                <FormLabel>Select Class</FormLabel>
                <ClassificationSelect
                  classifications={classifications}
                  selectValue={classification?.id}
                  onChange={(option) =>
                    selectClass(option as SelectOptionType<number> | null)
                  }
                  placeholder={'Choose class'}
                  isClearable={false}
                  isDisabled={isChangingClass}
                />
                {isChangingClass && (
                  <HStack mt={2} spacing={2} color={'gray.600'}>
                    <Spinner size={'sm'} />
                    <Text fontSize={'sm'}>Loading class ID cards...</Text>
                  </HStack>
                )}
              </FormControl>

              <HStack
                justify={{ base: 'flex-start', md: 'flex-end' }}
                align={'end'}
              >
                <Button
                  colorScheme={'brand'}
                  onClick={() => window.print()}
                  isDisabled={!classification || students.length === 0}
                >
                  Print ID Cards
                </Button>
              </HStack>
            </SimpleGrid>
          </Box>
        </Container>

        {!classification ? (
          <Box
            className="hidden-on-print"
            textAlign={'center'}
            py={12}
            color={'gray.600'}
          >
            No class is available for student ID cards.
          </Box>
        ) : students.length === 0 ? (
          <Box
            className="hidden-on-print"
            textAlign={'center'}
            py={12}
            color={'gray.600'}
          >
            No students were found in {classification.title}.
          </Box>
        ) : null}

        <Div textAlign={'center'}>
          {students.map((student) => (
            <Div
              display={'inline-block'}
              width={'340px'}
              mx={2}
              my={2}
              border={'1px solid #000'}
              p={2}
              key={student.id}
              borderRadius={'md'}
            >
              <HStack align={'stretch'} mb={6}>
                <Avatar
                  size="md"
                  src={
                    currentInstitution.photo ?? ImagePaths.default_school_logo
                  }
                />
                <Div
                  verticalAlign={'center'}
                  pl={1}
                  overflow={'hidden'}
                  textAlign={'center'}
                >
                  <Text
                    whiteSpace={'nowrap'}
                    fontSize={'lg'}
                    fontWeight={'bold'}
                  >
                    {currentInstitution.name}
                  </Text>
                  <Text whiteSpace={'nowrap'} fontSize={'xs'}>
                    {currentInstitution.address}
                  </Text>
                  <Text whiteSpace={'nowrap'} fontSize={'xs'}>
                    {currentInstitution.phone +
                      ' / ' +
                      currentInstitution.email}
                  </Text>
                </Div>
              </HStack>

              <HStack align={'stretch'}>
                <Image
                  rounded="md"
                  src={student.user?.photo ?? ImagePaths.default_school_logo}
                  h="75px"
                  w="75px"
                />

                <Div textAlign={'left'} width={'190px'} fontSize={'sm'}>
                  <Div mb={1}>
                    <Text>
                      ID No.:
                      <Text as={'span'} fontWeight={'bold'}>
                        {' ' + student.code}
                      </Text>
                    </Text>
                  </Div>

                  <Div>
                    <Text>
                      Name:
                      <Text as={'span'} fontWeight={'bold'}>
                        {' ' + student.user?.full_name}
                      </Text>
                    </Text>
                  </Div>
                </Div>

                <Box
                  width={'73px'}
                  height={'73px'}
                  display={'flex'}
                  justifyContent={'center'}
                  alignItems={'center'}
                >
                  <QRCodeSVG value={student.institution_user_id + ''} />
                </Box>
              </HStack>
            </Div>
          ))}
        </Div>
      </VStack>
    </Div>
  );
}
