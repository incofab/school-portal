import React from 'react';
import {
  FormControl,
  Radio,
  RadioGroup,
  Textarea,
  VStack,
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
import { Nullable, SelectOptionType, InstitutionUserType } from '@/types/types';
import useSharedProps from '@/hooks/use-shared-props';

export default function MarkAttendance() {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const { currentInstitution } = useSharedProps();

  const webForm = useWebForm({
    institution_user_id: null as Nullable<
      SingleValue<SelectOptionType<number>>
    >,
    type: '',
    remark: '',
  });

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

  return (
    <DashboardLayout>
      <CenteredBox>
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
                  onChange={(e) => webForm.setValue('institution_user_id', e)}
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
                  onChange={(value: string) => webForm.setValue('type', value)}
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
      </CenteredBox>
    </DashboardLayout>
  );
}
