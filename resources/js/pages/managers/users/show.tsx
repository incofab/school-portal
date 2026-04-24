import {
  Avatar,
  Badge,
  Button,
  Grid,
  GridItem,
  HStack,
  Stack,
  Text,
  useColorModeValue,
} from '@chakra-ui/react';
import React from 'react';
import { InstitutionUser, User } from '@/types/models';
import ManagerDashboardLayout from '@/layout/managers/manager-dashboard-layout';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import Dt from '@/components/dt';
import { SelectOptionType } from '@/types/types';
import { LinkButton } from '@/components/buttons';
import DestructivePopover from '@/components/destructive-popover';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import { Div } from '@/components/semantic';
import startCase from 'lodash/startCase';
import useSharedProps from '@/hooks/use-shared-props';
import route from '@/util/route';

interface UserWithInstitutions extends User {
  institution_users?: InstitutionUser[];
}

interface Props {
  userModel: UserWithInstitutions;
}

export default function ShowUser({ userModel }: Props) {
  const { currentUser } = useSharedProps();
  const { handleResponseToast } = useMyToast();
  const form = useWebForm({});

  async function resetPassword(onClose: () => void) {
    const res = await form.submit((data, web) => {
      return web.post(route('managers.users.reset-password', [userModel.id]), data);
    });

    if (!handleResponseToast(res)) return;
    onClose();
  }

  const profileData: SelectOptionType<React.ReactNode>[] = [
    { label: 'First name', value: userModel.first_name },
    { label: 'Last name', value: userModel.last_name },
    { label: 'Other names', value: userModel.other_names || 'N/A' },
    { label: 'Email', value: userModel.email },
    { label: 'Phone', value: userModel.phone || 'N/A' },
    { label: 'Gender', value: startCase(userModel.gender) || 'N/A' },
    {
        label: 'Manager Roles',
        value: (
          <HStack spacing={2}>
            {userModel.roles && userModel.roles.length > 0 ? (
              userModel.roles.map((role) => (
                <Badge key={role.id} colorScheme="purple">
                  {role.name}
                </Badge>
              ))
            ) : (
              <Text fontSize="sm" color="gray.500">None</Text>
            )}
          </HStack>
        ),
      },
  ];

  return (
    <ManagerDashboardLayout>
      <Slab>
        <SlabHeading title={`${userModel.full_name}'s Profile`} />
        <SlabBody>
          <Grid templateColumns={{ lg: 'repeat(3, 1fr)' }} gap={8}>
            <GridItem colSpan={{ lg: 2 }}>
              <Stack spacing={6}>
                <Div>
                   <Text fontWeight="bold" fontSize="lg" mb={3}>Basic Information</Text>
                   <Dt contentData={profileData} spacing={4} labelWidth={'150px'} />
                </Div>

                <Div>
                  <Text fontWeight="bold" fontSize="lg" mb={3}>Institutions & Roles</Text>
                  {userModel.institution_users && userModel.institution_users.length > 0 ? (
                    <Stack spacing={4}>
                      {userModel.institution_users.map((iu) => (
                        <Div
                          key={iu.id}
                          p={4}
                          borderWidth="1px"
                          borderRadius="md"
                          bg={useColorModeValue('white', 'gray.800')}
                        >
                          <Grid templateColumns="repeat(2, 1fr)" gap={2}>
                            <GridItem>
                              <Text fontWeight="semibold">{iu.institution?.name}</Text>
                              <Text fontSize="xs" color="gray.500">{iu.institution?.uuid}</Text>
                            </GridItem>
                            <GridItem textAlign="right">
                                <Badge colorScheme="brand" mr={2}>{startCase(iu.role)}</Badge>
                                <Badge colorScheme={iu.status === 'suspended' ? 'red' : 'green'}>
                                    {iu.status}
                                </Badge>
                            </GridItem>
                            {iu.student && (
                                <GridItem colSpan={2} mt={2} pt={2} borderTopWidth="1px" borderStyle="dashed">
                                     <Text fontSize="sm">
                                        <Text as="span" fontWeight="semibold">Student ID:</Text> {iu.student.code}
                                     </Text>
                                     <Text fontSize="sm">
                                        <Text as="span" fontWeight="semibold">Class:</Text> {iu.student.classification?.title || 'N/A'}
                                     </Text>
                                </GridItem>
                            )}
                          </Grid>
                        </Div>
                      ))}
                    </Stack>
                  ) : (
                    <Text color="gray.500">This user is not associated with any institution.</Text>
                  )}
                </Div>
              </Stack>
            </GridItem>

            <GridItem colSpan={{ lg: 1 }}>
              <Stack spacing={6} align="center">
                <Avatar size="2xl" src={userModel.photo_url || userModel.photo} name={userModel.full_name} />

                <Stack spacing={3} w="full">
                   {currentUser.id !== userModel.id && (
                     <DestructivePopover
                        label={`Reset ${userModel.full_name}'s password to default?`}
                        onConfirm={(onClose) => resetPassword(onClose)}
                        isLoading={form.processing}
                        positiveButtonLabel="Reset"
                      >
                        <Button colorScheme="brand" variant={'solid'} w="full">
                          Reset Password
                        </Button>
                      </DestructivePopover>
                   )}

                   <LinkButton
                    href={route('users.impersonate', [userModel.id])}
                    colorScheme={'red'}
                    title="Impersonate User"
                    w="full"
                   />
                </Stack>
              </Stack>
            </GridItem>
          </Grid>
        </SlabBody>
      </Slab>
    </ManagerDashboardLayout>
  );
}
