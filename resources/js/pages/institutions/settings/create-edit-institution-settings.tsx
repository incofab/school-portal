import React, { ChangeEvent, useState } from 'react';
import {
  Avatar,
  Divider,
  FormControl,
  FormLabel,
  HStack,
  Input,
  Spacer,
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
import { InstitutionSettingType, TermType } from '@/types/types';
import AcademicSessionSelect from '@/components/selectors/academic-session-select';
import useSharedProps from '@/hooks/use-shared-props';
import { Div } from '@/components/semantic';
import {
  FileDropperType,
  MAX_FILE_SIZE_BYTES,
  bytesToMb,
} from '@/components/file-dropper/common';
import { resizeImage } from '@/util/util';
import ResultSettings from './result-settings';
import PaymentKeysSettings from './payment-keys-settings';
import DataSelect from '@/components/dropdown-select/data-select';

interface Props {
  settings: { [key: string]: InstitutionSetting };
}

export default function CreateOrUpdateInstitutionSettings({ settings }: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const [activeSetting, setActiveSetting] = useState<string>('');
  const { currentTerm, currentAcademicSessionId } = useSharedProps();

  const webForm = useWebForm({
    [InstitutionSettingType.CurrentTerm]:
      settings[InstitutionSettingType.CurrentTerm]?.value ?? currentTerm,
    [InstitutionSettingType.CurrentAcademicSession]:
      settings[InstitutionSettingType.CurrentAcademicSession]?.value ??
      currentAcademicSessionId,
    // [InstitutionSettingType.ResultTemplate]:
    //   settings[InstitutionSettingType.ResultTemplate]?.value ?? '',
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
    [InstitutionSettingType.PinUsageCount]: Boolean(
      parseInt(settings[InstitutionSettingType.PinUsageCount]?.value ?? 1)
    ),
    [InstitutionSettingType.LockTermSession]: Boolean(
      parseInt(settings[InstitutionSettingType.LockTermSession]?.value ?? 1)
    ),
  } as { [key: string]: any });

  const submit = async (activeSetting: InstitutionSettingType, value?: any) => {
    setActiveSetting(activeSetting);
    const res = await webForm.submit((data, web) => {
      return web.post(instRoute('settings.store'), {
        key: activeSetting,
        value: value ?? data[activeSetting],
      });
    });
    if (!handleResponseToast(res)) return;
    Inertia.reload({ only: ['settings'] });
  };

  return (
    <DashboardLayout>
      <CenteredBox>
        <Slab>
          <SlabHeading title={`Set Your Settings`} />
          <SlabBody>
            <VStack align={'stretch'}>
              <Text>Current Term</Text>
              <HStack align={'stretch'} spacing={2}>
                <FormControl>
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
                <BrandButton
                  title="Update"
                  onClick={() => submit(InstitutionSettingType.CurrentTerm)}
                  isLoading={
                    activeSetting === InstitutionSettingType.CurrentTerm &&
                    webForm.processing
                  }
                  size={'md'}
                />
              </HStack>
              {/* <Spacer height={5} /> */}
              <Divider />
              <Text>Academic Session</Text>
              <HStack align={'stretch'} spacing={2}>
                <FormControl>
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
                <BrandButton
                  title="Update"
                  onClick={() =>
                    submit(InstitutionSettingType.CurrentAcademicSession)
                  }
                  isLoading={
                    activeSetting ===
                      InstitutionSettingType.CurrentAcademicSession &&
                    webForm.processing
                  }
                  size={'md'}
                />
              </HStack>
              <Divider />
              <Text>Uses Mid Term Results</Text>
              <HStack align={'stretch'} spacing={2}>
                <FormControl>
                  <EnumSelect
                    enumData={{ Yes: 'Yes', No: 'No' }}
                    selectValue={
                      webForm.data[InstitutionSettingType.UsesMidTermResult] ===
                      true
                        ? 'Yes'
                        : 'No'
                    }
                    onChange={(e: any) =>
                      webForm.setValue(
                        InstitutionSettingType.UsesMidTermResult,
                        e.value === 'Yes' ? true : false
                      )
                    }
                  />
                </FormControl>
                <BrandButton
                  title="Update"
                  onClick={() =>
                    submit(InstitutionSettingType.UsesMidTermResult)
                  }
                  isLoading={
                    activeSetting ===
                      InstitutionSettingType.UsesMidTermResult &&
                    webForm.processing
                  }
                  size={'md'}
                />
              </HStack>
              <Divider my={3} />
              <FormLabel border={'1px solid #999999AA'} p={2} borderRadius={5}>
                <Switch
                  isChecked={
                    webForm.data[InstitutionSettingType.LockTermSession] ===
                    true
                  }
                  onChange={(e) => {
                    const isChecked = e.target.checked;
                    let message =
                      'Disabling this will allow change of term and session when filling out results';
                    if (!isChecked && !window.confirm(message)) {
                      return;
                    }
                    webForm.setValue(
                      InstitutionSettingType.LockTermSession,
                      isChecked
                    );
                    submit(InstitutionSettingType.LockTermSession, isChecked);
                  }}
                  colorScheme={'brand'}
                  disabled={
                    activeSetting === InstitutionSettingType.LockTermSession &&
                    webForm.processing
                  }
                  pr={3}
                />
                <span>Lock Term and Session Change</span>
                <Div fontSize={11} mt={2} color={'red'}>
                  <i>
                    Be careful, turning this off means you can change term and
                    session when filling out results
                  </i>
                </Div>
              </FormLabel>
              <Divider my={3} />
              <Text>Pin Usage Count</Text>
              <HStack spacing={2}>
                <FormControl>
                  <DataSelect
                    data={{
                      main: [
                        { label: 'Once', value: 1 },
                        { label: 'Session', value: 3 },
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
                <BrandButton
                  title="Update"
                  onClick={() => submit(InstitutionSettingType.PinUsageCount)}
                  isLoading={
                    activeSetting === InstitutionSettingType.PinUsageCount &&
                    webForm.processing
                  }
                  size={'md'}
                />
              </HStack>
              <Spacer height={3} />
              <FormLabel border={'1px solid #999999AA'} p={2} borderRadius={5}>
                <Switch
                  isChecked={
                    webForm.data[
                      InstitutionSettingType.ResultActivationRequired
                    ] === true
                  }
                  onChange={(e) => {
                    const isChecked = e.target.checked;
                    let message =
                      'If you diable this, Students will be able to check their results without requiring activation pins.';
                    if (!isChecked && !window.confirm(message)) {
                      return;
                    }
                    webForm.setValue(
                      InstitutionSettingType.ResultActivationRequired,
                      isChecked
                    );
                    submit(
                      InstitutionSettingType.ResultActivationRequired,
                      isChecked
                    );
                  }}
                  colorScheme={'brand'}
                  disabled={
                    activeSetting ===
                      InstitutionSettingType.ResultActivationRequired &&
                    webForm.processing
                  }
                  pr={3}
                />
                <span>Result Activation Required</span>
                <Div fontSize={11} mt={2} color={'red'}>
                  <i>
                    Be careful, turning this off means your students won't need
                    Pins to check result after publishing
                  </i>
                </Div>
              </FormLabel>
              {/* 
              {webForm.data[InstitutionSettingType.UsesMidTermResult] && (
                <>
                  <Divider />
                  <Text>Currently on Mid Term</Text>
                  <HStack align={'stretch'} spacing={2}>
                    <FormControl>
                      <EnumSelect
                        enumData={{ Yes: 'Yes', No: 'No' }}
                        selectValue={
                          webForm.data[
                            InstitutionSettingType.CurrentlyOnMidTerm
                          ] === true
                            ? 'Yes'
                            : 'No'
                        }
                        onChange={(e: any) =>
                          webForm.setValue(
                            InstitutionSettingType.CurrentlyOnMidTerm,
                            e.value === 'Yes' ? true : false
                          )
                        }
                      />
                    </FormControl>
                    <BrandButton
                      title="Update"
                      onClick={() =>
                        submit(InstitutionSettingType.CurrentlyOnMidTerm)
                      }
                      isLoading={
                        activeSetting ===
                          InstitutionSettingType.CurrentlyOnMidTerm &&
                        webForm.processing
                      }
                      size={'md'}
                    />
                  </HStack>
                </>
              )}
              */}
              <ResultSettings />
              <PaymentKeysSettings />
              <UpdateStamp settings={settings} />
              <Spacer height={5} />
            </VStack>
          </SlabBody>
        </Slab>
      </CenteredBox>
    </DashboardLayout>
  );
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
    if (!files) {
      return;
    }
    const file: File = files[0];
    const imageBlob = await resizeImage(file, 300, 300);

    const res = await webForm.submit(async (data, web) => {
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
    <Div
      mt={{ lg: 4 }}
      display={'flex'}
      alignItems={'center'}
      flexDirection={{ base: 'column' }}
    >
      <Div
        display={'flex'}
        alignItems={'center'}
        justifyContent={'center'}
        w={200}
        h={200}
        borderWidth={1}
        borderColor={'gray.200'}
      >
        <Avatar size={'2xl'} src={webForm.data[InstitutionSettingType.Stamp]} />
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
          Change School Stamp
        </FormLabel>
        <Text fontSize={'sm'} color={'blackAlpha.700'}>
          Allowed extensions {extensions.join(', ')}
        </Text>
        <Text fontSize={'sm'} color={'blackAlpha.700'}>
          Maximum size {Math.floor(bytesToMb(MAX_FILE_SIZE_BYTES))}
          MB
        </Text>
      </Div>
    </Div>
  );
}
