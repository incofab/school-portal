import React from 'react';
import {
  Checkbox,
  Divider,
  FormControl,
  Spacer,
  VStack,
} from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import useWebForm from '@/hooks/use-web-form';
import { preventNativeSubmit } from '@/util/util';
import { Inertia } from '@inertiajs/inertia';
import { Student } from '@/types/models';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import CenteredBox from '@/components/centered-box';
import { FormButton } from '@/components/buttons';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import FormControlBox from '@/components/forms/form-control-box';
import ClassificationSelect from '@/components/selectors/classification-select';

interface Props {
  students: Student[];
}

export default function ChangeMultipleStudentClass({ students }: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const webForm = useWebForm({
    students: [] as number[],
    destination_class: '',
    move_to_alumni: false,
  });

  const submit = async () => {
    const res = await webForm.submit((data, web) => {
      return web.post(instRoute('change-multi-student-class.store'), data);
    });
    if (!handleResponseToast(res)) {
      return;
    }
    Inertia.visit(instRoute('classifications.index'));
  };

  return (
    <DashboardLayout>
      <CenteredBox>
        <Slab>
          <SlabHeading title={`Change Students' Class`} />
          <SlabBody>
            <VStack
              spacing={4}
              as={'form'}
              onSubmit={preventNativeSubmit(submit)}
              align={'stretch'}
            >
              {!webForm.data.move_to_alumni && (
                <FormControlBox
                  title="Destination Class"
                  form={webForm as any}
                  formKey="destination_class"
                >
                  <ClassificationSelect
                    value={webForm.data.destination_class}
                    isMulti={false}
                    isClearable={true}
                    onChange={(e: any) =>
                      webForm.setValue('destination_class', e?.value)
                    }
                    required
                  />
                </FormControlBox>
              )}
              <FormControl>
                <Checkbox
                  isChecked={webForm.data.move_to_alumni}
                  onChange={(e) =>
                    webForm.setData({
                      ...webForm.data,
                      move_to_alumni: e.currentTarget.checked,
                      destination_class: '',
                    })
                  }
                  size={'md'}
                  colorScheme="brand"
                >
                  Move selected students to alumni
                </Checkbox>
              </FormControl>
              <Divider />
              <Spacer height={2} />
              {students.map((item) => (
                <Checkbox
                  key={item.id}
                  isChecked={webForm.data.students.includes(item.id)}
                  onChange={(e) =>
                    webForm.setValue(
                      'students',
                      e.currentTarget.checked
                        ? [...webForm.data.students, item.id]
                        : webForm.data.students.filter(
                            (studentId) => studentId !== item.id
                          )
                    )
                  }
                >
                  {item.user?.full_name} - {item.classification?.title}
                </Checkbox>
              ))}

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
