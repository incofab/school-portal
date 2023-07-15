import React, { useState } from 'react';
import {
  Divider,
  FormControl,
  HStack,
  Spacer,
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
  InstitutionSettingType,
  ResultTemplate,
  TermType,
} from '@/types/types';
import AcademicSessionSelect from '@/components/selectors/academic-session-select';
import useSharedProps from '@/hooks/use-shared-props';

interface Props {
  settings: { [key: string]: InstitutionSetting };
}

export default function CreateOrUpdateInstitutionSettings({ settings }: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const [activeSetting, setActiveSetting] = useState<string>('');
  const { currentTerm, currentAcademicSession } = useSharedProps();

  const webForm = useWebForm({
    [InstitutionSettingType.CurrentTerm]:
      settings[InstitutionSettingType.CurrentTerm]?.value ?? currentTerm,
    [InstitutionSettingType.CurrentAcademicSession]:
      settings[InstitutionSettingType.CurrentAcademicSession]?.value ??
      currentAcademicSession,
    [InstitutionSettingType.ResultTemplate]:
      settings[InstitutionSettingType.ResultTemplate]?.value ?? '',
    [InstitutionSettingType.UsesMidTermResult]: Boolean(
      parseInt(settings[InstitutionSettingType.UsesMidTermResult]?.value)
    ),
  });

  const submit = async (activeSetting: InstitutionSettingType) => {
    setActiveSetting(activeSetting);
    const res = await webForm.submit((data, web) => {
      return web.post(instRoute('settings.store'), {
        key: activeSetting,
        value: data[activeSetting],
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
              <Text>Result Template</Text>
              <HStack align={'stretch'} spacing={2}>
                <FormControl>
                  <EnumSelect
                    enumData={ResultTemplate}
                    selectValue={
                      webForm.data[InstitutionSettingType.ResultTemplate] ??
                      ResultTemplate.Template1
                    }
                    onChange={(e: any) =>
                      webForm.setValue(
                        InstitutionSettingType.ResultTemplate,
                        e.value
                      )
                    }
                  />
                </FormControl>
                <BrandButton
                  title="Update"
                  onClick={() => submit(InstitutionSettingType.ResultTemplate)}
                  isLoading={
                    activeSetting === InstitutionSettingType.ResultTemplate &&
                    webForm.processing
                  }
                  size={'md'}
                />
              </HStack>
              <Spacer height={5} />
            </VStack>
          </SlabBody>
        </Slab>
      </CenteredBox>
    </DashboardLayout>
  );
}
