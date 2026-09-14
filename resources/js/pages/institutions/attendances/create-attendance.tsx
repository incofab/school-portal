import React, { useEffect, useMemo, useState } from 'react';
import {
  Badge,
  Box,
  Button,
  Checkbox,
  FormControl,
  HStack,
  Radio,
  RadioGroup,
  SimpleGrid,
  Stack,
  Table,
  Tbody,
  Td,
  Text,
  Textarea,
  Th,
  Thead,
  Tab,
  TabList,
  TabPanel,
  TabPanels,
  Tabs,
  Tr,
  VStack,
  Icon,
  useColorModeValue,
} from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import useWebForm from '@/hooks/use-web-form';
import { generateUniqueString, preventNativeSubmit } from '@/util/util';
import { Inertia } from '@inertiajs/inertia';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { FormButton } from '@/components/buttons';
import CenteredBox from '@/components/centered-box';
import InstitutionUserSelect from '@/components/selectors/institution-user-select';
import FormControlBox from '@/components/forms/form-control-box';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { SingleValue } from 'react-select';
import {
  Attendance,
  InstitutionUserType,
  Nullable,
  SelectOptionType,
} from '@/types/types';
import useSharedProps from '@/hooks/use-shared-props';
import ClassificationSelect from '@/components/selectors/classification-select';
import { InstitutionUser } from '@/types/models';
import { UserGroupIcon, UserIcon } from '@heroicons/react/24/outline';

interface Props {
  staff: AttendanceUser[];
}

interface AttendanceUser extends InstitutionUser {
  attendance_status?: {
    checked_in: boolean;
    checked_out: boolean;
  };
}

type RegisterMode = 'students' | 'staff';

export default function MarkAttendance({ staff }: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const { currentInstitution } = useSharedProps();
  const [attendanceMode, setAttendanceMode] = useState<'single' | 'batch'>(
    'single'
  );
  const [mode, setMode] = useState<RegisterMode>('students');
  const [classification, setClassification] =
    useState<Nullable<SingleValue<SelectOptionType<number>>>>(null);
  const [students, setStudents] = useState<AttendanceUser[]>([]);
  const [selectedIds, setSelectedIds] = useState<number[]>([]);
  const [initialSelectedIds, setInitialSelectedIds] = useState<number[]>([]);
  const [refreshIndex, setRefreshIndex] = useState(0);

  const webForm = useWebForm({
    institution_user_id: null as Nullable<
      SingleValue<SelectOptionType<number>>
    >,
    type: '',
    remark: '',
  });

  const batchForm = useWebForm({
    type: Attendance.In as string,
    remark: '',
  });

  const studentLoadForm = useWebForm({});

  const visiblePeople = useMemo(
    () => (mode === 'staff' ? staff : students),
    [mode, staff, students]
  );

  useEffect(() => {
    if (mode !== 'students') {
      return;
    }

    if (!classification?.value) {
      setStudents([]);
      return;
    }

    studentLoadForm
      .submit((data, web) =>
        web.get(instRoute('attendances.students', []), {
          params: {
            classification_id: classification.value,
          },
        })
      )
      .then((res) => {
        setStudents(res.ok ? res.data.result : []);
      });
  }, [classification?.value, mode, refreshIndex]);

  useEffect(() => {
    const alreadyMarkedIds = visiblePeople
      .filter((person) =>
        batchForm.data.type === Attendance.In
          ? person.attendance_status?.checked_in
          : person.attendance_status?.checked_out
      )
      .map((person) => person.id);

    setSelectedIds(alreadyMarkedIds);
    setInitialSelectedIds(alreadyMarkedIds);
  }, [batchForm.data.type, visiblePeople]);

  const allVisibleSelected =
    visiblePeople.length > 0 &&
    visiblePeople.every((person) => selectedIds.includes(person.id));
  const changedSelectedIds = selectedIds.filter(
    (id) => !initialSelectedIds.includes(id)
  );
  const changedUnselectedIds = initialSelectedIds.filter(
    (id) => !selectedIds.includes(id)
  );
  const hasBatchChanges =
    changedSelectedIds.length > 0 || changedUnselectedIds.length > 0;

  const submit = async () => {
    const reference = generateUniqueString(currentInstitution.id);
    const res = await webForm.submit((data, web) =>
      web.post(instRoute('attendances.store', []), {
        ...data,
        institution_user_id: data.institution_user_id?.value,
        reference: reference,
      })
    );
    if (!handleResponseToast(res)) return;
    Inertia.visit(instRoute('attendances.create'));
  };

  const submitBatch = async () => {
    const res = await batchForm.submit((data, web) =>
      web.post(instRoute('attendances.bulk-store', []), {
        ...data,
        institution_user_ids: changedSelectedIds,
        unmark_institution_user_ids: changedUnselectedIds,
      })
    );
    if (!handleResponseToast(res)) return;
    Inertia.visit(instRoute('attendances.create'));
  };

  const toggleSelected = (id: number, checked: boolean) => {
    setSelectedIds((current) =>
      checked ? [...new Set([...current, id])] : current.filter((x) => x !== id)
    );
  };

  const toggleAllVisible = (checked: boolean) => {
    const visibleIds = visiblePeople.map((person) => person.id);
    setSelectedIds((current) =>
      checked
        ? [...new Set([...current, ...visibleIds])]
        : current.filter((id) => !visibleIds.includes(id))
    );
  };

  const switchMode = (nextMode: RegisterMode) => {
    setMode(nextMode);
    setSelectedIds([]);
  };

  return (
    <DashboardLayout>
      <CenteredBox maxW="6xl">
        <Stack spacing={5}>
          <Tabs
            index={attendanceMode === 'single' ? 0 : 1}
            onChange={(index) =>
              setAttendanceMode(index === 0 ? 'single' : 'batch')
            }
            variant="unstyled"
            colorScheme="brand"
            isLazy
            aria-label="Attendance marking mode"
          >
            <Box
              bg={useColorModeValue('gray.50', 'gray.700')}
              borderWidth="1px"
              borderColor={useColorModeValue('gray.200', 'gray.600')}
              borderRadius="xl"
              p={{ base: 1, sm: 2 }}
            >
              <TabList gap={{ base: 1, sm: 2 }}>
                <AttendanceModeTab
                  icon={UserIcon}
                  title="Single attendance"
                  description="Record one person"
                />
                <AttendanceModeTab
                  icon={UserGroupIcon}
                  title="Batch attendance"
                  description="Record multiple people"
                />
              </TabList>
            </Box>

            <TabPanels mt={5}>
              <TabPanel p={0}>
                <Slab>
                  <SlabHeading title={'Mark Attendance'} />
                  <SlabBody>
                    <VStack
                      spacing={6}
                      as={'form'}
                      onSubmit={preventNativeSubmit(submit)}
                    >
                      <FormControlBox
                        title="Staff / Student"
                        form={webForm as any}
                        formKey="institution_user_id"
                      >
                        <InstitutionUserSelect
                          value={webForm.data.institution_user_id}
                          isClearable={true}
                          rolesIn={[
                            InstitutionUserType.Admin,
                            InstitutionUserType.Accountant,
                            InstitutionUserType.Teacher,
                            InstitutionUserType.Student,
                          ]}
                          onChange={(e) =>
                            webForm.setValue('institution_user_id', e)
                          }
                          isMulti={false}
                          required
                        />
                      </FormControlBox>

                      <FormControlBox
                        form={webForm as any}
                        title="Attendance Type"
                        formKey="type"
                      >
                        <RadioGroup
                          value={webForm.data.type}
                          onChange={(value: string) =>
                            webForm.setValue('type', value)
                          }
                        >
                          <VStack align={'start'}>
                            <Radio value={'in'}>Sign In</Radio>
                            <Radio value={'out'}>Sign Out</Radio>
                          </VStack>
                        </RadioGroup>
                      </FormControlBox>

                      <FormControlBox
                        form={webForm as any}
                        title="Remark [optional]"
                        formKey="remark"
                      >
                        <Textarea
                          onChange={(e) =>
                            webForm.setValue('remark', e.currentTarget.value)
                          }
                        />
                      </FormControlBox>

                      <FormControl>
                        <FormButton isLoading={webForm.processing} />
                      </FormControl>
                    </VStack>
                  </SlabBody>
                </Slab>
              </TabPanel>
              <TabPanel p={0}>
                <Slab>
                  <SlabHeading title={'Batch Attendance'} />
                  <SlabBody>
                    <Stack spacing={5}>
                      <SimpleGrid columns={{ base: 1, md: 3 }} spacing={4}>
                        <FormControlBox
                          form={batchForm as any}
                          title="Register"
                          formKey="type"
                        >
                          <RadioGroup
                            value={batchForm.data.type}
                            onChange={(value: string) =>
                              batchForm.setValue('type', value)
                            }
                          >
                            <HStack spacing={5}>
                              <Radio value={Attendance.In}>Check In</Radio>
                              <Radio value={Attendance.Out}>Check Out</Radio>
                            </HStack>
                          </RadioGroup>
                        </FormControlBox>

                        <FormControlBox
                          form={batchForm as any}
                          title="People"
                          formKey="mode"
                        >
                          <HStack>
                            <Button
                              size="sm"
                              variant={
                                mode === 'students' ? 'solid' : 'outline'
                              }
                              onClick={() => switchMode('students')}
                            >
                              Students
                            </Button>
                            <Button
                              size="sm"
                              variant={mode === 'staff' ? 'solid' : 'outline'}
                              onClick={() => switchMode('staff')}
                            >
                              Staff
                            </Button>
                          </HStack>
                        </FormControlBox>

                        {mode === 'students' && (
                          <FormControlBox
                            form={batchForm as any}
                            title="Class"
                            formKey="classification"
                          >
                            <ClassificationSelect
                              selectValue={classification}
                              onChange={(value: any) => {
                                setClassification(value);
                                setStudents([]);
                                setSelectedIds([]);
                              }}
                              isClearable
                              isMulti={false}
                            />
                          </FormControlBox>
                        )}
                      </SimpleGrid>

                      <FormControlBox
                        form={batchForm as any}
                        title="Remark [optional]"
                        formKey="remark"
                      >
                        <Textarea
                          value={batchForm.data.remark}
                          onChange={(e) =>
                            batchForm.setValue('remark', e.currentTarget.value)
                          }
                        />
                      </FormControlBox>

                      <Stack
                        direction={{ base: 'column', sm: 'row' }}
                        justify={{ base: 'stretch', sm: 'space-between' }}
                        align={{ base: 'stretch', sm: 'center' }}
                        spacing={3}
                      >
                        <Text color="gray.600" fontSize="sm">
                          {visiblePeople.length} loaded, {selectedIds.length}{' '}
                          selected,{' '}
                          {changedSelectedIds.length +
                            changedUnselectedIds.length}{' '}
                          changed
                        </Text>
                        <HStack justify={{ base: 'flex-end', sm: 'initial' }}>
                          {mode === 'students' && (
                            <Button
                              size="sm"
                              variant="outline"
                              isLoading={studentLoadForm.processing}
                              isDisabled={!classification}
                              onClick={() => {
                                setRefreshIndex((current) => current + 1);
                              }}
                            >
                              Refresh List
                            </Button>
                          )}
                          <FormButton
                            type="button"
                            title={
                              batchForm.data.type === Attendance.In
                                ? 'Check In Selected'
                                : 'Check Out Selected'
                            }
                            isLoading={batchForm.processing}
                            isDisabled={!hasBatchChanges}
                            onClick={submitBatch}
                          />
                        </HStack>
                      </Stack>

                      <Box overflowX="auto">
                        <Table size="sm">
                          <Thead>
                            <Tr>
                              <Th w="52px">
                                <Checkbox
                                  isChecked={allVisibleSelected}
                                  isDisabled={visiblePeople.length === 0}
                                  onChange={(e) =>
                                    toggleAllVisible(e.currentTarget.checked)
                                  }
                                />
                              </Th>
                              <Th>Name</Th>
                              <Th>Type</Th>
                              <Th>Class</Th>
                              <Th>Today</Th>
                            </Tr>
                          </Thead>
                          <Tbody>
                            {visiblePeople.map((person) => (
                              <Tr key={person.id}>
                                <Td>
                                  <Checkbox
                                    isChecked={selectedIds.includes(person.id)}
                                    onChange={(e) =>
                                      toggleSelected(
                                        person.id,
                                        e.currentTarget.checked
                                      )
                                    }
                                  />
                                </Td>
                                <Td>
                                  <Text fontWeight="medium">
                                    {person.user?.full_name}
                                  </Text>
                                </Td>
                                <Td>
                                  <Badge textTransform="capitalize">
                                    {person.type}
                                  </Badge>
                                </Td>
                                <Td>
                                  {person.student?.classification?.title ?? '-'}
                                </Td>
                                <Td>
                                  <HStack spacing={2}>
                                    {person.attendance_status?.checked_in && (
                                      <Badge colorScheme="green">
                                        Checked in
                                      </Badge>
                                    )}
                                    {person.attendance_status?.checked_out && (
                                      <Badge colorScheme="blue">
                                        Checked out
                                      </Badge>
                                    )}
                                    {!person.attendance_status?.checked_in &&
                                      !person.attendance_status
                                        ?.checked_out && (
                                        <Text color="gray.500">Not marked</Text>
                                      )}
                                  </HStack>
                                </Td>
                              </Tr>
                            ))}
                            {visiblePeople.length === 0 && (
                              <Tr>
                                <Td colSpan={5}>
                                  <Text color="gray.500" py={4}>
                                    {mode === 'students'
                                      ? 'Select a class to load students.'
                                      : 'No staff found.'}
                                  </Text>
                                </Td>
                              </Tr>
                            )}
                          </Tbody>
                        </Table>
                      </Box>
                    </Stack>
                  </SlabBody>
                </Slab>
              </TabPanel>
            </TabPanels>
          </Tabs>
        </Stack>
      </CenteredBox>
    </DashboardLayout>
  );
}

function AttendanceModeTab({
  icon,
  title,
  description,
}: {
  icon: React.ElementType;
  title: string;
  description: string;
}) {
  return (
    <Tab
      flex={1}
      minW={0}
      justifyContent="flex-start"
      px={{ base: 3, sm: 5 }}
      py={{ base: 3, sm: 4 }}
      borderRadius="lg"
      color={useColorModeValue('gray.600', 'gray.300')}
      _selected={{
        bg: useColorModeValue('white', 'gray.600'),
        color: useColorModeValue('brand.700', 'white'),
        boxShadow: 'sm',
      }}
      _focusVisible={{
        boxShadow: 'outline',
      }}
    >
      <HStack spacing={{ base: 2, sm: 3 }} minW={0} textAlign="left">
        <Box
          flexShrink={0}
          rounded="full"
          p={{ base: 2, sm: 2.5 }}
          bg={useColorModeValue('brand.100', 'gray.500')}
          color={useColorModeValue('brand.600', 'white')}
        >
          <Icon as={icon} boxSize={{ base: 4, sm: 5 }} />
        </Box>
        <VStack align="start" spacing={0} minW={0}>
          <Text
            fontWeight="semibold"
            fontSize={{ base: 'sm', sm: 'md' }}
            noOfLines={1}
          >
            {title}
          </Text>
          <Text
            display={{ base: 'none', sm: 'block' }}
            fontSize="xs"
            color="gray.500"
            noOfLines={1}
          >
            {description}
          </Text>
        </VStack>
      </HStack>
    </Tab>
  );
}
