import React, { useState } from 'react';
import { Classification, Student } from '@/types/models';
import { Div } from '@/components/semantic';
import {
  Badge,
  Box,
  Button,
  Container,
  FormControl,
  FormLabel,
  HStack,
  SimpleGrid,
  Spinner,
  Text,
  useColorModeValue,
  VStack,
} from '@chakra-ui/react';
import useSharedProps from '@/hooks/use-shared-props';
import ClassificationSelect from '@/components/selectors/classification-select';
import { SelectOptionType } from '@/types/types';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { Inertia } from '@inertiajs/inertia';
import StudentIdCard from '@/components/id-cards/student-id-card';

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
    <Div textAlign={'center'} bg="white">
      <VStack align={'stretch'} spacing={6} bg="white" minH={'100vh'}>
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
            <StudentIdCard
              key={student.id}
              institution={currentInstitution}
              student={student}
            />
          ))}
        </Div>
      </VStack>
    </Div>
  );
}
