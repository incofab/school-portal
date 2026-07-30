import {
  Avatar,
  Badge,
  Box,
  Button,
  FormControl,
  FormErrorMessage,
  FormLabel,
  Grid,
  GridItem,
  HStack,
  Icon,
  IconButton,
  Input,
  SimpleGrid,
  Spinner,
  Text,
  Tooltip,
  VStack,
  useToast,
} from '@chakra-ui/react';
import { Inertia } from '@inertiajs/inertia';
import { InertiaLink } from '@inertiajs/inertia-react';
import {
  ArrowLeftIcon,
  HomeIcon,
  PencilSquareIcon,
} from '@heroicons/react/24/outline';
import React, { ChangeEvent, useState } from 'react';
import {
  FileDropperType,
  MAX_FILE_SIZE_BYTES,
  bytesToMb,
} from '@/components/file-dropper/common';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import Dt from '@/components/dt';
import { Div } from '@/components/semantic';
import EnumSelect from '@/components/dropdown-select/enum-select';
import FormControlBox from '@/components/forms/form-control-box';
import CenteredLayout from '@/components/centered-layout';
import useWebForm, { useWeb } from '@/hooks/use-web-form';
import route from '@/util/route';
import { preventNativeSubmit, resizeImage } from '@/util/util';
import { InstitutionUser, User } from '@/types/models';
import { Gender, SelectOptionType } from '@/types/types';
import startCase from 'lodash/startCase';

interface Props {
  user: User;
  institutionUser?: InstitutionUser;
}

const editableFields = [
  'first_name',
  'last_name',
  'other_names',
  'email',
  'phone',
  'gender',
] as const;

export default function Profile({ user, institutionUser }: Props) {
  const [isEditing, setIsEditing] = useState(false);
  const toast = useToast();
  const web = useWeb();
  const extensions = FileDropperType.Image.extensionLabels;
  const form = useWebForm({
    first_name: user.first_name ?? '',
    last_name: user.last_name ?? '',
    other_names: user.other_names ?? '',
    email: user.email ?? '',
    phone: user.phone ?? '',
    gender: user.gender ?? '',
    photo: user.photo_url ?? user.photo ?? '',
  });

  const roles = [
    ...(user.roles ?? []).map((item) => startCase(item.name)),
    ...(user.partner_user
      ? [`Partner ${startCase(user.partner_user.role)}`]
      : []),
    ...(institutionUser ? [startCase(institutionUser.role)] : []),
  ];
  const institutions = user.institution_user
    ? [user.institution_user]
    : user.institution_users ?? [];
  const student = institutionUser?.student ?? user.institution_user?.student;

  const profileData: SelectOptionType<React.ReactNode>[] = [
    { label: 'First name', value: user.first_name },
    { label: 'Last name', value: user.last_name },
    { label: 'Other names', value: user.other_names || 'N/A' },
    { label: 'Email', value: user.email },
    { label: 'Phone', value: user.phone || 'N/A' },
    { label: 'Gender', value: startCase(user.gender || 'N/A') },
    { label: 'Username', value: user.username || 'N/A' },
  ];

  async function submitForm() {
    const res = await form.submit((data, web) =>
      web.put(route('users.profile.update'), pickEditableData(data))
    );

    if (!res.ok) {
      return void toast({
        title: res.message ?? 'Profile update failed',
        status: 'error',
      });
    }

    toast({
      title: res.message ?? 'Profile updated successfully',
      status: 'success',
    });
    setIsEditing(false);
    Inertia.reload({ only: ['user', 'shared__currentUser'] });
  }

  async function uploadImage(e: ChangeEvent<HTMLInputElement>) {
    e.preventDefault();
    const file = e.target.files?.[0];
    if (!file) return;

    const imageBlob = await resizeImage(file, 300, 300);
    const res = await form.submit(async () => {
      const formData = new FormData();
      formData.append('photo', imageBlob as Blob);
      return web.post(route('users.profile.upload-photo'), formData);
    });

    if (!res.ok) {
      return void toast({
        title: res.message ?? 'Profile photo upload failed',
        status: 'error',
      });
    }

    form.setValue('photo', res.data.url);
    toast({ title: 'Profile photo updated', status: 'success' });
    Inertia.reload({ only: ['user', 'shared__currentUser'] });
  }

  function cancelEdit() {
    form.reset();
    setIsEditing(false);
  }

  return (
    <Slab>
      <SlabHeading
        title="Your Profile"
        rightElement={
          <HStack spacing={2}>
            <Tooltip label="Go back">
              <IconButton
                aria-label="Go back"
                icon={<Icon as={ArrowLeftIcon} />}
                variant="outline"
                onClick={() => window.history.back()}
              />
            </Tooltip>
            <Tooltip label="Dashboard">
              <IconButton
                as={InertiaLink}
                href={route('user.dashboard')}
                aria-label="Dashboard"
                icon={<Icon as={HomeIcon} />}
                variant="outline"
                colorScheme="brand"
              />
            </Tooltip>
            <Tooltip label={isEditing ? 'Close editor' : 'Edit profile'}>
              <IconButton
                aria-label={isEditing ? 'Close editor' : 'Edit profile'}
                icon={<Icon as={PencilSquareIcon} />}
                colorScheme="brand"
                variant={isEditing ? 'solid' : 'outline'}
                onClick={() => (isEditing ? cancelEdit() : setIsEditing(true))}
              />
            </Tooltip>
          </HStack>
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
                p={5}
              >
                <HStack spacing={4} align="start" flexWrap="wrap">
                  <Avatar size="xl" src={form.data.photo || user.photo_url} />
                  <Box>
                    <Text fontSize="xl" fontWeight="bold">
                      {user.full_name}
                    </Text>
                    <HStack spacing={2} mt={2} flexWrap="wrap">
                      {roles.length > 0 ? (
                        roles.map((role) => (
                          <Badge key={role} colorScheme="brand">
                            {role}
                          </Badge>
                        ))
                      ) : (
                        <Badge>Account User</Badge>
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
                p={5}
              >
                {isEditing ? (
                  <VStack
                    as="form"
                    align="stretch"
                    spacing={4}
                    onSubmit={preventNativeSubmit(submitForm)}
                  >
                    <Text fontWeight="semibold">Edit Basic Information</Text>
                    <SimpleGrid columns={{ base: 1, md: 2 }} spacing={4}>
                      <FormControlBox
                        form={form}
                        title="First Name"
                        formKey="first_name"
                        isRequired
                      >
                        <Input
                          value={form.data.first_name}
                          onChange={(e) =>
                            form.setValue('first_name', e.currentTarget.value)
                          }
                        />
                      </FormControlBox>
                      <FormControlBox
                        form={form}
                        title="Last Name"
                        formKey="last_name"
                        isRequired
                      >
                        <Input
                          value={form.data.last_name}
                          onChange={(e) =>
                            form.setValue('last_name', e.currentTarget.value)
                          }
                        />
                      </FormControlBox>
                      <FormControlBox
                        form={form}
                        title="Other Names"
                        formKey="other_names"
                      >
                        <Input
                          value={form.data.other_names}
                          onChange={(e) =>
                            form.setValue('other_names', e.currentTarget.value)
                          }
                        />
                      </FormControlBox>
                      <FormControlBox
                        form={form}
                        title="Email"
                        formKey="email"
                        isRequired
                      >
                        <Input
                          type="email"
                          value={form.data.email}
                          onChange={(e) =>
                            form.setValue('email', e.currentTarget.value)
                          }
                        />
                      </FormControlBox>
                      <FormControlBox form={form} title="Phone" formKey="phone">
                        <Input
                          value={form.data.phone}
                          onChange={(e) =>
                            form.setValue('phone', e.currentTarget.value)
                          }
                        />
                      </FormControlBox>
                      <FormControlBox
                        form={form}
                        title="Gender"
                        formKey="gender"
                      >
                        <EnumSelect
                          enumData={Gender}
                          selectValue={form.data.gender}
                          onChange={(e: any) =>
                            form.setValue('gender', e?.value ?? '')
                          }
                        />
                      </FormControlBox>
                    </SimpleGrid>
                    <HStack spacing={3}>
                      <Button
                        type="submit"
                        colorScheme="brand"
                        isLoading={form.processing}
                      >
                        Save Changes
                      </Button>
                      <Button variant="outline" onClick={cancelEdit}>
                        Cancel
                      </Button>
                    </HStack>
                  </VStack>
                ) : (
                  <VStack align="stretch" spacing={4}>
                    <Box>
                      <Text fontWeight="semibold">Profile Details</Text>
                      <Text fontSize="sm" color="gray.600">
                        Personal and account information attached to your login.
                      </Text>
                    </Box>
                    <Dt
                      contentData={profileData}
                      spacing={4}
                      labelWidth="150px"
                    />
                  </VStack>
                )}
              </Box>
            </VStack>
          </GridItem>

          <GridItem>
            <VStack align="stretch" spacing={4}>
              <FormControl isInvalid={!!form.errors.photo}>
                <Div
                  display="flex"
                  alignItems="center"
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
                    display="flex"
                    alignItems="center"
                    justifyContent="center"
                    w={200}
                    h={200}
                    borderWidth={1}
                    borderColor="gray.200"
                    borderRadius="8px"
                    bg="gray.50"
                  >
                    {form.processing ? (
                      <Spinner size="xl" color="brand.500" />
                    ) : (
                      <Avatar
                        size="2xl"
                        src={form.data.photo || user.photo_url}
                      />
                    )}
                  </Div>
                  <Div mt={4} textAlign="center">
                    <FormLabel
                      htmlFor="photo"
                      textColor="brand.500"
                      display="inline-block"
                      cursor="pointer"
                      m={0}
                      p={0}
                    >
                      <Input
                        type="file"
                        id="photo"
                        hidden
                        accept="image/jpeg,image/png,image/jpg,image/webp"
                        onChange={(e) => uploadImage(e)}
                      />
                      Change profile photo
                    </FormLabel>
                    <Text fontSize="sm" color="blackAlpha.700">
                      Allowed extensions {extensions.join(', ')}
                    </Text>
                    <Text fontSize="sm" color="blackAlpha.700">
                      Maximum size {Math.floor(bytesToMb(MAX_FILE_SIZE_BYTES))}{' '}
                      MB
                    </Text>
                    <FormErrorMessage>{form.errors.photo}</FormErrorMessage>
                  </Div>
                </Div>
              </FormControl>

              <Box
                borderWidth="1px"
                borderColor="gray.200"
                borderRadius="8px"
                p={5}
              >
                <VStack align="stretch" spacing={3}>
                  <Text fontWeight="semibold">Access Summary</Text>
                  {institutions.length > 0 && (
                    <Box>
                      <Text fontSize="sm" color="gray.600" mb={2}>
                        Institution Access
                      </Text>
                      <VStack align="stretch" spacing={2}>
                        {institutions.map((item) => (
                          <Box
                            key={item.id}
                            borderWidth="1px"
                            borderRadius="8px"
                            p={3}
                          >
                            <Text fontWeight="semibold">
                              {item.institution?.name ?? 'Institution'}
                            </Text>
                            <Text fontSize="sm" color="gray.600">
                              {startCase(item.role)}
                            </Text>
                          </Box>
                        ))}
                      </VStack>
                    </Box>
                  )}
                  {student && (
                    <Box>
                      <Text fontSize="sm" color="gray.600">
                        Student Class
                      </Text>
                      <Text fontWeight="semibold">
                        {student.classification?.title ?? 'N/A'}
                      </Text>
                    </Box>
                  )}
                  {user.partner_user?.partner && (
                    <Box>
                      <Text fontSize="sm" color="gray.600">
                        Partner Account
                      </Text>
                      <Text fontWeight="semibold">
                        {user.partner_user.partner.name}
                      </Text>
                    </Box>
                  )}
                  <Button
                    as={InertiaLink}
                    href={route('users.password.edit')}
                    variant="outline"
                    colorScheme="brand"
                    size="sm"
                  >
                    Change Password
                  </Button>
                </VStack>
              </Box>
            </VStack>
          </GridItem>
        </Grid>
      </SlabBody>
    </Slab>
  );
}

function pickEditableData(data: Record<string, string>) {
  return editableFields.reduce((payload, key) => {
    payload[key] = data[key];
    return payload;
  }, {} as Record<(typeof editableFields)[number], string>);
}

Profile.layout = (page: any) => (
  <CenteredLayout boxProps={{ maxW: '6xl' }}>{page}</CenteredLayout>
);
