import React, { ChangeEvent, useState } from 'react';
import {
  Avatar,
  Box,
  Flex,
  FormControl,
  FormLabel,
  HStack,
  Input,
  SimpleGrid,
  Spinner,
  Switch,
  Text,
  VStack,
} from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import useWebForm from '@/hooks/use-web-form';
import { Inertia } from '@inertiajs/inertia';
import { InstitutionSetting } from '@/types/models';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import CenteredBox from '@/components/centered-box';
import { BrandButton } from '@/components/buttons';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import EnumSelect from '@/components/dropdown-select/enum-select';
import {
  FileDropperType,
  MAX_FILE_SIZE_BYTES,
  bytesToMb,
} from '@/components/file-dropper/common';
import {
  AttendanceNotificationType,
  InstitutionSettingType,
  NotificationChannelsType,
  SelectOptionType,
  TermType,
  UserFullNameFormat,
} from '@/types/types';
import AcademicSessionSelect from '@/components/selectors/academic-session-select';
import useSharedProps from '@/hooks/use-shared-props';
import { resizeImage } from '@/util/util';
import ResultSettings, {
  getResultSettingsData,
  ResultSettingsData,
} from './result-settings';
import PaymentKeysSettings from './payment-keys-settings';
import DataSelect from '@/components/dropdown-select/data-select';
import {
  SettingsSection,
  default as SettingsGroup,
} from '@/components/settings/settings-group';

interface Props {
  settings: { [key: string]: InstitutionSetting };
}

interface SettingUpdate {
  key: InstitutionSettingType;
  value: unknown;
  type?: string;
}

const fullNameFormatOptions: SelectOptionType<string>[] = [
  { label: 'No institution override', value: '' },
  {
    label: 'First name / Other names / Surname',
    value: UserFullNameFormat.FirstOtherLast,
  },
  {
    label: 'First name / Surname / Other names',
    value: UserFullNameFormat.FirstLastOther,
  },
  {
    label: 'Surname / First name / Other names',
    value: UserFullNameFormat.LastFirstOther,
  },
  {
    label: 'Surname / Other names / First name',
    value: UserFullNameFormat.LastOtherFirst,
  },
];

const attendanceNotificationOptions: SelectOptionType<string>[] = [
  { label: 'No notification', value: AttendanceNotificationType.None },
  {
    label: 'Send notification on check-in',
    value: AttendanceNotificationType.CheckIn,
  },
  {
    label: 'Send notification on check-in and check-out',
    value: AttendanceNotificationType.CheckInAndOut,
  },
  {
    label: 'Send summary notification only at check-out',
    value: AttendanceNotificationType.CheckOut,
  },
];

const preferredMessageOptions: SelectOptionType<string>[] = [
  { label: 'Email', value: NotificationChannelsType.Email },
  { label: 'SMS', value: NotificationChannelsType.Sms },
  { label: 'WhatsApp', value: NotificationChannelsType.Whatsapp },
];

export default function CreateOrUpdateInstitutionSettings({ settings }: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const [activeGroup, setActiveGroup] = useState('');
  const { currentTerm, currentAcademicSessionId, currentUser, resultSetting } =
    useSharedProps();
  const [resultSettings, setResultSettings] = useState<ResultSettingsData>(() =>
    getResultSettingsData(resultSetting)
  );

  const webForm = useWebForm({
    [InstitutionSettingType.CurrentTerm]:
      settings[InstitutionSettingType.CurrentTerm]?.value ?? currentTerm,
    [InstitutionSettingType.CurrentAcademicSession]:
      settings[InstitutionSettingType.CurrentAcademicSession]?.value ??
      currentAcademicSessionId,
    [InstitutionSettingType.UsesMidTermResult]: Boolean(
      parseInt(settings[InstitutionSettingType.UsesMidTermResult]?.value)
    ),
    [InstitutionSettingType.CurrentlyOnMidTerm]:
      settings[InstitutionSettingType.CurrentlyOnMidTerm]?.value,
    [InstitutionSettingType.ResultActivationRequired]: Boolean(
      parseInt(
        settings[InstitutionSettingType.ResultActivationRequired]?.value ?? 1
      )
    ),
    [InstitutionSettingType.PinUsageCount]: parseInt(
      settings[InstitutionSettingType.PinUsageCount]?.value ?? 1
    ),
    [InstitutionSettingType.LockTermSession]: Boolean(
      parseInt(settings[InstitutionSettingType.LockTermSession]?.value ?? 1)
    ),
    [InstitutionSettingType.UserFullNameFormat]:
      settings[InstitutionSettingType.UserFullNameFormat]?.value ?? '',
    [InstitutionSettingType.AttendanceNotification]:
      settings[InstitutionSettingType.AttendanceNotification]?.value ??
      AttendanceNotificationType.None,
    [InstitutionSettingType.PreferredMessageOption]:
      settings[InstitutionSettingType.PreferredMessageOption]?.value ??
      NotificationChannelsType.Sms,
  } as { [key: string]: any });

  const fullNamePreview = formatFullNamePreview(
    {
      first_name: currentUser?.first_name || 'First name',
      other_names: currentUser?.other_names || 'Other names',
      last_name: currentUser?.last_name || 'Last name',
    },
    webForm.data[InstitutionSettingType.UserFullNameFormat]
  );

  const submitSettings = async (group: string, updates: SettingUpdate[]) => {
    setActiveGroup(group);
    const res = await webForm.submit((_, web) =>
      web.post(instRoute('settings.store-multiple'), {
        settings: updates,
      })
    );

    if (!handleResponseToast(res)) return;
    Inertia.reload({ only: ['settings'] });
  };

  const submitSingleSetting = (key: InstitutionSettingType, value?: unknown) =>
    submitSettings(key, [
      {
        key,
        value: value ?? webForm.data[key],
      },
    ]);

  const submitResultsAndPinAccess = () =>
    submitSettings('results-and-pin-access', [
      {
        key: InstitutionSettingType.UsesMidTermResult,
        value: webForm.data[InstitutionSettingType.UsesMidTermResult],
      },
      {
        key: InstitutionSettingType.ResultActivationRequired,
        value: webForm.data[InstitutionSettingType.ResultActivationRequired],
      },
      {
        key: InstitutionSettingType.PinUsageCount,
        value: webForm.data[InstitutionSettingType.PinUsageCount],
      },
      {
        key: InstitutionSettingType.Result,
        value: resultSettings,
        type: 'array',
      },
    ]);

  return (
    <DashboardLayout>
      <CenteredBox maxWidth="960px" py={{ base: 3, md: 6 }}>
        <Slab>
          <SlabHeading
            title="Institution settings"
            fontSize={{ base: 'xl', md: '2xl' }}
          />
          <SlabBody>
            <Text color="gray.500" mb={5}>
              Manage academic context, results, identity display, and other
              institution preferences from one place.
            </Text>

            <VStack align="stretch" spacing={4}>
              <SettingsGroup
                title="Academic context"
                description="Choose the current term and academic session used across the institution."
                action={
                  <BrandButton
                    title="Save academic context"
                    onClick={() =>
                      submitSettings('academic-context', [
                        {
                          key: InstitutionSettingType.CurrentTerm,
                          value:
                            webForm.data[InstitutionSettingType.CurrentTerm],
                        },
                        {
                          key: InstitutionSettingType.CurrentAcademicSession,
                          value:
                            webForm.data[
                              InstitutionSettingType.CurrentAcademicSession
                            ],
                        },
                      ])
                    }
                    isLoading={
                      activeGroup === 'academic-context' && webForm.processing
                    }
                    size="md"
                  />
                }
              >
                <SimpleGrid columns={{ base: 1, md: 2 }} spacing={4}>
                  <FormControl>
                    <FormLabel>Current term</FormLabel>
                    <EnumSelect
                      enumData={TermType}
                      selectValue={
                        webForm.data[InstitutionSettingType.CurrentTerm]
                      }
                      onChange={(e: any) =>
                        webForm.setValue(
                          InstitutionSettingType.CurrentTerm,
                          e.value
                        )
                      }
                    />
                  </FormControl>
                  <FormControl>
                    <FormLabel>Academic session</FormLabel>
                    <AcademicSessionSelect
                      selectValue={
                        webForm.data[
                          InstitutionSettingType.CurrentAcademicSession
                        ]
                      }
                      onChange={(e: any) =>
                        webForm.setValue(
                          InstitutionSettingType.CurrentAcademicSession,
                          e.value
                        )
                      }
                    />
                  </FormControl>
                </SimpleGrid>

                <Box borderWidth="1px" borderRadius="lg" p={4}>
                  <HStack align="start" spacing={3}>
                    <Switch
                      id="lock-term-session"
                      isChecked={
                        webForm.data[InstitutionSettingType.LockTermSession] ===
                        true
                      }
                      onChange={(e) => {
                        const isChecked = e.target.checked;
                        const message =
                          'Disabling this will allow change of term and session when filling out results';
                        if (!isChecked && !window.confirm(message)) return;

                        webForm.setValue(
                          InstitutionSettingType.LockTermSession,
                          isChecked
                        );
                        submitSingleSetting(
                          InstitutionSettingType.LockTermSession,
                          isChecked
                        );
                      }}
                      colorScheme="brand"
                      isDisabled={
                        activeGroup ===
                          InstitutionSettingType.LockTermSession &&
                        webForm.processing
                      }
                      mt={1}
                    />
                    <Box>
                      <FormLabel htmlFor="lock-term-session" mb={1}>
                        Lock term and session changes
                      </FormLabel>
                      <Text fontSize="sm" color="gray.500">
                        This setting updates immediately. Turn it off only when
                        staff need to change the academic context while entering
                        results.
                      </Text>
                    </Box>
                  </HStack>
                </Box>
              </SettingsGroup>

              <SettingsGroup
                title="Results and PIN access"
                description="Manage result availability, presentation, and PIN usage from one place."
              >
                <SettingsSection
                  title="Result access"
                  description="Control mid-term result usage and whether published results require activation pins."
                  first
                >
                  <SimpleGrid columns={{ base: 1, md: 2 }} spacing={4}>
                    <FormControl>
                      <FormLabel>Use mid-term results</FormLabel>
                      <EnumSelect
                        enumData={{ Yes: 'Yes', No: 'No' }}
                        selectValue={
                          webForm.data[InstitutionSettingType.UsesMidTermResult]
                            ? 'Yes'
                            : 'No'
                        }
                        onChange={(e: any) =>
                          webForm.setValue(
                            InstitutionSettingType.UsesMidTermResult,
                            e.value === 'Yes'
                          )
                        }
                      />
                    </FormControl>
                    <FormControl>
                      <FormLabel>Result activation required</FormLabel>
                      <HStack align="start" spacing={3} pt={2}>
                        <Switch
                          id="result-activation-required"
                          isChecked={
                            webForm.data[
                              InstitutionSettingType.ResultActivationRequired
                            ] === true
                          }
                          onChange={(e) => {
                            const isChecked = e.target.checked;
                            const message =
                              'If you disable this, students will be able to check their results without activation pins.';
                            if (!isChecked && !window.confirm(message)) return;
                            webForm.setValue(
                              InstitutionSettingType.ResultActivationRequired,
                              isChecked
                            );
                          }}
                          colorScheme="brand"
                        />
                        <Box>
                          <FormLabel
                            htmlFor="result-activation-required"
                            mb={1}
                          >
                            Require activation pins
                          </FormLabel>
                          <Text fontSize="sm" color="gray.500">
                            Applies when students check results after
                            publishing.
                          </Text>
                        </Box>
                      </HStack>
                    </FormControl>
                  </SimpleGrid>
                </SettingsSection>

                <SettingsSection
                  title="PIN usage"
                  description="Set how often a generated result PIN can be used."
                >
                  <FormControl maxW={{ base: 'full', md: '320px' }}>
                    <FormLabel>PIN validity</FormLabel>
                    <DataSelect
                      data={{
                        main: [
                          { label: 'Once', value: 1 },
                          { label: 'For the session', value: 3 },
                        ],
                        label: 'label',
                        value: 'value',
                      }}
                      selectValue={
                        webForm.data[InstitutionSettingType.PinUsageCount]
                      }
                      onChange={(e: any) =>
                        webForm.setValue(
                          InstitutionSettingType.PinUsageCount,
                          e.value
                        )
                      }
                    />
                  </FormControl>
                </SettingsSection>

                <ResultSettings
                  embedded
                  data={resultSettings}
                  onChange={(key, value) =>
                    setResultSettings((current) => ({
                      ...current,
                      [key]: value,
                    }))
                  }
                  showActions={false}
                />

                <Flex
                  justify={{ base: 'stretch', sm: 'flex-end' }}
                  borderTopWidth="1px"
                  borderColor="gray.200"
                  pt={5}
                >
                  <BrandButton
                    title="Save results and PIN access"
                    onClick={submitResultsAndPinAccess}
                    isLoading={
                      activeGroup === 'results-and-pin-access' &&
                      webForm.processing
                    }
                    w={{ base: 'full', sm: 'auto' }}
                    size="md"
                  />
                </Flex>
              </SettingsGroup>

              <SettingsGroup
                title="Name display"
                description="Choose how names appear on institution-scoped pages and responses."
                action={
                  <BrandButton
                    title="Save name display"
                    onClick={() =>
                      submitSingleSetting(
                        InstitutionSettingType.UserFullNameFormat
                      )
                    }
                    isLoading={
                      activeGroup ===
                        InstitutionSettingType.UserFullNameFormat &&
                      webForm.processing
                    }
                    size="md"
                  />
                }
              >
                <FormControl>
                  <FormLabel>User full name display order</FormLabel>
                  <DataSelect
                    data={{
                      main: fullNameFormatOptions,
                      label: 'label',
                      value: 'value',
                    }}
                    selectValue={
                      webForm.data[InstitutionSettingType.UserFullNameFormat]
                    }
                    onChange={(e: any) =>
                      webForm.setValue(
                        InstitutionSettingType.UserFullNameFormat,
                        e?.value ?? ''
                      )
                    }
                  />
                  <Text mt={2} fontSize="sm" color="gray.500">
                    Preview: <strong>{fullNamePreview}</strong>
                  </Text>
                </FormControl>
              </SettingsGroup>

              <SettingsGroup
                title="Attendance notifications"
                description="Choose when guardians receive attendance updates and which channel the institution prefers. Notifications are disabled by default."
                action={
                  <BrandButton
                    title="Save attendance notifications"
                    onClick={() =>
                      submitSettings('attendance-notifications', [
                        {
                          key: InstitutionSettingType.AttendanceNotification,
                          value:
                            webForm.data[
                              InstitutionSettingType.AttendanceNotification
                            ],
                        },
                        {
                          key: InstitutionSettingType.PreferredMessageOption,
                          value:
                            webForm.data[
                              InstitutionSettingType.PreferredMessageOption
                            ],
                        },
                      ])
                    }
                    isLoading={
                      activeGroup === 'attendance-notifications' &&
                      webForm.processing
                    }
                    size="md"
                  />
                }
              >
                <SimpleGrid columns={{ base: 1, md: 2 }} spacing={4}>
                  <FormControl>
                    <FormLabel>Attendance notification timing</FormLabel>
                    <DataSelect
                      data={{
                        main: attendanceNotificationOptions,
                        label: 'label',
                        value: 'value',
                      }}
                      selectValue={
                        webForm.data[
                          InstitutionSettingType.AttendanceNotification
                        ]
                      }
                      onChange={(e: any) =>
                        webForm.setValue(
                          InstitutionSettingType.AttendanceNotification,
                          e?.value ?? AttendanceNotificationType.None
                        )
                      }
                    />
                    <Text mt={2} fontSize="sm" color="gray.500">
                      Check-out notifications include the child&apos;s arrival
                      and departure times.
                    </Text>
                  </FormControl>
                  <FormControl>
                    <FormLabel>Preferred message option</FormLabel>
                    <DataSelect
                      data={{
                        main: preferredMessageOptions,
                        label: 'label',
                        value: 'value',
                      }}
                      selectValue={
                        webForm.data[
                          InstitutionSettingType.PreferredMessageOption
                        ]
                      }
                      onChange={(e: any) =>
                        webForm.setValue(
                          InstitutionSettingType.PreferredMessageOption,
                          e?.value ?? NotificationChannelsType.Sms
                        )
                      }
                    />
                    <Text mt={2} fontSize="sm" color="gray.500">
                      The selected channel uses the guardian contact saved for
                      each child.
                    </Text>
                  </FormControl>
                </SimpleGrid>
              </SettingsGroup>

              <PaymentKeysSettings />
              <UpdateStamp settings={settings} />
            </VStack>
          </SlabBody>
        </Slab>
      </CenteredBox>
    </DashboardLayout>
  );
}

function formatFullNamePreview(
  values: {
    first_name: string;
    other_names: string;
    last_name: string;
  },
  format?: string
) {
  const parts = (() => {
    switch (format) {
      case UserFullNameFormat.FirstLastOther:
        return [values.first_name, values.last_name, values.other_names];
      case UserFullNameFormat.LastFirstOther:
        return [values.last_name, values.first_name, values.other_names];
      case UserFullNameFormat.LastOtherFirst:
        return [values.last_name, values.other_names, values.first_name];
      case UserFullNameFormat.OtherFirstLast:
        return [values.other_names, values.first_name, values.last_name];
      case UserFullNameFormat.OtherLastFirst:
        return [values.other_names, values.last_name, values.first_name];
      case UserFullNameFormat.FirstOtherLast:
      default:
        return [values.first_name, values.other_names, values.last_name];
    }
  })();

  return parts.filter(Boolean).join(' ');
}

function UpdateStamp({ settings }: Props) {
  const { instRoute } = useInstitutionRoute();
  const { handleResponseToast } = useMyToast();
  const webForm = useWebForm({
    [InstitutionSettingType.Stamp]:
      settings[InstitutionSettingType.Stamp]?.value,
  } as { [key: string]: any });
  const extensions = FileDropperType.Image.extensionLabels;

  async function uploadImage(e: ChangeEvent<HTMLInputElement>) {
    e.preventDefault();
    const { files } = e.target;
    if (!files) return;

    const file: File = files[0];
    const imageBlob = await resizeImage(file, 300, 300);
    const res = await webForm.submit(async (_, web) => {
      const formData = new FormData();
      formData.append('photo', imageBlob as Blob);
      formData.append('key', InstitutionSettingType.Stamp);
      return web.post(instRoute('settings.store'), formData);
    });

    if (!handleResponseToast(res)) return;
    webForm.setValue('photo', res.data.url);
    Inertia.reload();
  }

  return (
    <SettingsGroup
      title="School stamp"
      description="Upload the stamp used on institution documents."
    >
      <Flex
        direction={{ base: 'column', sm: 'row' }}
        align={{ base: 'center', sm: 'start' }}
        gap={5}
      >
        <Flex
          align="center"
          justify="center"
          w="200px"
          h="200px"
          flexShrink={0}
          borderWidth={1}
          borderColor="gray.200"
        >
          {webForm.processing ? (
            <Spinner size="xl" color="brand.500" />
          ) : (
            <Avatar
              size="2xl"
              src={webForm.data[InstitutionSettingType.Stamp]}
            />
          )}
        </Flex>
        <Box textAlign={{ base: 'center', sm: 'left' }}>
          <FormLabel
            htmlFor="school-stamp"
            color="brand.500"
            cursor="pointer"
            mb={1}
          >
            Change school stamp
          </FormLabel>
          <Input
            type="file"
            id="school-stamp"
            hidden
            accept="image/jpeg,image/png,image/jpg"
            onChange={uploadImage}
          />
          <Text fontSize="sm" color="blackAlpha.700">
            Allowed extensions: {extensions.join(', ')}
          </Text>
          <Text fontSize="sm" color="blackAlpha.700">
            Maximum size: {Math.floor(bytesToMb(MAX_FILE_SIZE_BYTES))} MB
          </Text>
        </Box>
      </Flex>
    </SettingsGroup>
  );
}
