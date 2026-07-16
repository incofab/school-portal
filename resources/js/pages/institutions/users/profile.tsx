import { Div } from '@/components/semantic';
import {
  Box,
  FormControl,
  FormErrorMessage,
  FormLabel,
  Input,
  Grid,
  GridItem,
  Text,
  Avatar,
  HStack,
  Button,
  IconButton,
  Tooltip,
  Icon,
  Spinner,
  VStack,
  SimpleGrid,
  Badge,
} from '@chakra-ui/react';
import React, { ChangeEvent } from 'react';
import {
  bytesToMb,
  MAX_FILE_SIZE_BYTES,
  FileDropperType,
} from '@/components/file-dropper/common';
import { resizeImage } from '@/util/util';
import { Inertia } from '@inertiajs/inertia';
import useSharedProps from '@/hooks/use-shared-props';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import useWebForm, { useWeb } from '@/hooks/use-web-form';
import useInstitutionRoute from '@/hooks/use-institution-route';
import useMyToast from '@/hooks/use-my-toast';
import { InstitutionUser, Student, User } from '@/types/models';
import DashboardLayout from '@/layout/dashboard-layout';
import Dt from '@/components/dt';
import { Nullable, SelectOptionType } from '@/types/types';
import { BrandButton } from '@/components/buttons';
import useIsAdmin from '@/hooks/use-is-admin';
import DestructivePopover from '@/components/destructive-popover';
import useModalToggle, { useModalValueToggle } from '@/hooks/use-modal-toggle';
import ChangeRoleModal from '@/components/modals/change-role-modal';
import startCase from 'lodash/startCase';
import ChangeStudentClassModal from '@/components/modals/change-student-class-modal';
import { PencilSquareIcon } from '@heroicons/react/24/outline';
import DownloadResultRecordingSheetModal from '@/components/modals/download-result-recording-sheet-modal';
import useIsStaff from '@/hooks/use-is-staff';
import { InertiaLink } from '@inertiajs/inertia-react';
import SuspensionToggleButton from '@/domain/institutions/user-profile/suspension-toggle-button';

interface Props {
  user: User;
  institutionUser: InstitutionUser;
}

export default function Profile({ user, institutionUser }: Props) {
  const { currentUser, currentAcademicSession, currentTerm } = useSharedProps();
  const { instRoute } = useInstitutionRoute();
  const { handleResponseToast } = useMyToast();
  const downloadRecordingSheetModalToggle = useModalToggle();
  const changeClassModalToggle = useModalValueToggle<Nullable<Student>>();
  const form = useWebForm({
    photo: user.photo,
  });
  const web = useWeb();
  const isAdmin = useIsAdmin();
  const isStaff = useIsStaff();
  const changeRoleModalToggle = useModalToggle();
  const extensions = FileDropperType.Image.extensionLabels;

  async function resetPassword(onClose: () => void) {
    const res = await form.submit((data, web) => {
      return web.post(instRoute('users.reset-password', [user]), data);
    });

    if (!handleResponseToast(res)) return;
    onClose();
  }

  async function uploadImage(e: ChangeEvent<HTMLInputElement>) {
    e.preventDefault();
    const { files } = e.target;
    if (!files) {
      return;
    }
    const file: File = files[0];
    const imageBlob = await resizeImage(file, 300, 300);

    const res = await form.submit(async () => {
      const formData = new FormData();
      formData.append('photo', imageBlob as Blob);
      return web.post(instRoute('users.upload-photo', [user]), formData);
    });
    if (!handleResponseToast(res)) return;
    form.setValue('photo', res.data.url);
    Inertia.reload({ only: ['user'] });
  }

  async function generateResultPin(student: Student) {
    if (
      !window.confirm(
        `Generate result checker pins for the ${currentAcademicSession.title} Session and ${currentTerm} Term`
      )
    ) {
      return;
    }
    const res = await form.submit((data, web) =>
      web.post(instRoute('pins.students.store', [student]), data)
    );

    if (!handleResponseToast(res)) return;

    Inertia.visit(
      instRoute('pins.classification.student-pin-tiles', [
        student.classification_id,
      ])
    );
  }

  const student = institutionUser.student;
  if (student) {
    student.user = user;
  }
  const profileData: SelectOptionType<React.ReactNode>[] = [
    { label: 'First name', value: user.first_name },
    { label: 'Last name', value: user.last_name },
    { label: 'Other names', value: user.other_names },
    { label: 'Email', value: user.email },
    { label: 'Phone', value: user.phone },
    { label: 'User Type', value: startCase(institutionUser.role) },
    { label: 'Gender', value: user.gender },
    ...(student
      ? [
          { label: 'Student Id', value: student.code },
          { label: 'Guardian Phone', value: student.guardian_phone },
          {
            label: 'Class',
            value: (
              <HStack spacing={3}>
                <Text>{student.classification?.title}</Text>
                {isStaff && (
                  <Tooltip label={'Change class'} placement={'auto-start'}>
                    <IconButton
                      aria-label="Change class"
                      onClick={() => changeClassModalToggle.open(student)}
                      icon={<Icon as={PencilSquareIcon} />}
                      colorScheme="brand"
                      size={'sm'}
                    />
                  </Tooltip>
                )}
              </HStack>
            ),
          },
        ]
      : []),
  ];

  return (
    <Box>
      <Slab>
        <SlabHeading
          title={
            user.id === currentUser.id
              ? 'Your Profile'
              : `${user.full_name}'s Profile`
          }
        />
        <SlabBody>
          <Grid templateColumns={{ lg: 'minmax(0, 1fr) 320px' }} gap={5}>
            <GridItem>
              <VStack align="stretch" spacing={5}>
                <Box
                  borderWidth="1px"
                  borderColor="gray.200"
                  borderRadius="8px"
                  p={{ base: 4, md: 5 }}
                >
                  <HStack spacing={4} align="start" flexWrap="wrap">
                    <Avatar size="lg" src={form.data.photo || user.photo} />
                    <Box>
                      <Text fontSize="xl" fontWeight="bold">
                        {user.full_name}
                      </Text>
                      <HStack spacing={2} mt={1} flexWrap="wrap">
                        <Badge colorScheme="brand">
                          {startCase(institutionUser.role)}
                        </Badge>
                        {student && (
                          <Badge colorScheme="purple">
                            {student.classification?.title ?? 'Student'}
                          </Badge>
                        )}
                      </HStack>
                      <Text fontSize="sm" color="gray.600" mt={2}>
                        {user.email || user.phone || 'No contact detail'}
                      </Text>
                    </Box>
                  </HStack>
                </Box>

                <Box
                  borderWidth="1px"
                  borderColor="gray.200"
                  borderRadius="8px"
                  p={{ base: 4, md: 5 }}
                >
                  <VStack align="stretch" spacing={4}>
                    <Box>
                      <Text fontWeight="semibold">Profile Details</Text>
                      <Text fontSize="sm" color="gray.600">
                        Personal, account, and school-assignment information.
                      </Text>
                    </Box>
                    <Dt
                      contentData={profileData}
                      spacing={4}
                      labelWidth={'150px'}
                    />
                    <SuspensionToggleButton
                      institutionUser={institutionUser}
                      showLabel={true}
                    />
                  </VStack>
                </Box>
              </VStack>
            </GridItem>

            <GridItem>
              <VStack align="stretch" spacing={4}>
                <Box
                  borderWidth="1px"
                  borderColor="gray.200"
                  borderRadius="8px"
                  p={5}
                >
                  <VStack align="stretch" spacing={3}>
                    <Text fontWeight="semibold">Quick Actions</Text>
                    <SimpleGrid columns={1} spacing={2}>
                      {isStaff && (
                        <BrandButton
                          title="Download Result Recording Sheet"
                          onClick={downloadRecordingSheetModalToggle.open}
                        />
                      )}
                      {currentUser.id !== user.id && isAdmin && (
                        <>
                          <BrandButton
                            title="Change Role"
                            onClick={changeRoleModalToggle.open}
                          />
                          <DestructivePopover
                            label={`Reset user's password to default?`}
                            onConfirm={(onClose) => resetPassword(onClose)}
                            isLoading={form.processing}
                            positiveButtonLabel="Reset"
                          >
                            <Button
                              colorScheme="brand"
                              variant="outline"
                              size="sm"
                              w="100%"
                            >
                              Reset Password
                            </Button>
                          </DestructivePopover>
                        </>
                      )}
                      {isStaff && student && (
                        <>
                          <Button
                            as={InertiaLink}
                            href={instRoute('students.transcript', [student])}
                            variant="outline"
                            colorScheme="brand"
                            size="sm"
                          >
                            Transcript
                          </Button>
                          <BrandButton
                            title="Generate Result Pin"
                            onClick={() => generateResultPin(student)}
                          />
                        </>
                      )}
                    </SimpleGrid>
                  </VStack>
                </Box>

                <FormControl isInvalid={!!form.errors.photo}>
                  <Div
                    display={'flex'}
                    alignItems={'center'}
                    flexDirection={{ base: 'column' }}
                    borderWidth="1px"
                    borderColor="gray.200"
                    borderRadius="8px"
                    p={5}
                  >
                    <Text fontWeight="semibold" mb={4}>
                      Profile Photo
                    </Text>
                    <Div
                      display={'flex'}
                      alignItems={'center'}
                      justifyContent={'center'}
                      w={200}
                      h={200}
                      borderWidth={1}
                      borderColor={'gray.200'}
                      borderRadius="8px"
                      bg="gray.50"
                    >
                      {form.processing ? (
                        <Spinner size="xl" color="brand.500" />
                      ) : (
                        <Avatar
                          size={'2xl'}
                          src={form.data.photo || user.photo}
                        />
                      )}
                    </Div>
                    <Div mt={4} textAlign={'center'}>
                      <FormLabel
                        htmlFor="photo"
                        textColor={'brand.500'}
                        display={'inline-block'}
                        cursor={'pointer'}
                        m={0}
                        p={0}
                      >
                        <Input
                          type={'file'}
                          id="photo"
                          hidden
                          accept={'image/jpeg,image/png,image/jpg'}
                          onChange={(e) => uploadImage(e)}
                        />
                        Change profile photo
                      </FormLabel>
                      <Text fontSize={'sm'} color={'blackAlpha.700'}>
                        Allowed extensions {extensions.join(', ')}
                      </Text>
                      <Text fontSize={'sm'} color={'blackAlpha.700'}>
                        Maximum size{' '}
                        {Math.floor(bytesToMb(MAX_FILE_SIZE_BYTES))}
                        MB
                      </Text>
                      <FormErrorMessage>{form.errors.photo}</FormErrorMessage>
                    </Div>
                  </Div>
                </FormControl>
              </VStack>
            </GridItem>
          </Grid>
          <ChangeRoleModal
            institutionUser={institutionUser}
            {...changeRoleModalToggle.props}
            onSuccess={() => Inertia.reload({ only: ['institutionUser'] })}
          />
          {changeClassModalToggle.state && (
            <ChangeStudentClassModal
              student={changeClassModalToggle.state}
              {...changeClassModalToggle.props}
              onSuccess={() => Inertia.reload({ only: ['institutionUser'] })}
            />
          )}
        </SlabBody>
      </Slab>
      <DownloadResultRecordingSheetModal
        {...downloadRecordingSheetModalToggle.props}
        onSuccess={() => {}}
      />
    </Box>
  );
}

Profile.layout = (page: any) => <DashboardLayout children={page} />;
