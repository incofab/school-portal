import React from 'react';
import { Checkbox, Divider, FormControl, VStack } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import useWebForm from '@/hooks/use-web-form';
import { preventNativeSubmit } from '@/util/util';
import { Inertia } from '@inertiajs/inertia';
import { Association, User } from '@/types/models';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import CenteredBox from '@/components/centered-box';
import { FormButton } from '@/components/buttons';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import FormControlBox from '@/components/forms/form-control-box';
import AssociationSelect from '@/components/selectors/association-select';

interface Props {
  users: User[];
  associations: Association[];
}

export default function CreateUserAssociation({ users, associations }: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();

  const webForm = useWebForm({
    institution_user_ids: [] as number[],
    association_id: '',
  });

  const submit = async () => {
    const res = await webForm.submit((data, web) => {
      return web.post(instRoute('user-associations.store'), data);
    });

    if (!handleResponseToast(res)) {
      return;
    }

    Inertia.visit(
      instRoute(
        'user-associations.index',
        webForm.data.association_id ? [webForm.data.association_id] : undefined
      )
    );
  };

  return (
    <DashboardLayout>
      <CenteredBox>
        <Slab>
          <SlabHeading title={`Assign users to Divisions`} />
          <SlabBody>
            <VStack
              spacing={4}
              as={'form'}
              onSubmit={preventNativeSubmit(submit)}
              align={'stretch'}
            >
              <FormControlBox
                form={webForm as any}
                formKey="association_id"
                title="Division"
              >
                <AssociationSelect
                  selectValue={webForm.data.association_id}
                  isMulti={false}
                  isClearable={true}
                  onChange={(e: any) =>
                    webForm.setValue('association_id', e?.value)
                  }
                  required
                  associations={associations}
                />
              </FormControlBox>
              <Divider my={1} />
              <Checkbox
                isChecked={
                  webForm.data.institution_user_ids.length === users.length
                }
                colorScheme={'brand'}
                onChange={(e) =>
                  webForm.setValue(
                    'institution_user_ids',
                    e.currentTarget.checked
                      ? users.map((user) => user.institution_user!.id)
                      : []
                  )
                }
              >
                Select All
              </Checkbox>
              {users.map((user) => (
                <Checkbox
                  colorScheme={'brand'}
                  key={user.institution_user!.id}
                  value={user.institution_user!.id}
                  isChecked={webForm.data.institution_user_ids.includes(
                    user.institution_user!.id
                  )}
                  onChange={(e) =>
                    webForm.setValue(
                      'institution_user_ids',
                      e.currentTarget.checked
                        ? [
                            ...webForm.data.institution_user_ids,
                            user.institution_user!.id,
                          ]
                        : webForm.data.institution_user_ids.filter(
                            (id) => id !== user.institution_user!.id
                          )
                    )
                  }
                >
                  {user.full_name}
                </Checkbox>
              ))}
              <Divider my={2} />
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
