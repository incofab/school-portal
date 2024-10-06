import React from 'react';
import {
  Divider,
  FormControl,
  FormLabel,
  Input,
  Text,
  VStack,
} from '@chakra-ui/react';
import useWebForm from '@/hooks/use-web-form';
import { preventNativeSubmit } from '@/util/util';
import { Inertia } from '@inertiajs/inertia';
import { InstitutionGroup } from '@/types/models';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import CenteredBox from '@/components/centered-box';
import { FormButton } from '@/components/buttons';
import useMyToast from '@/hooks/use-my-toast';
import route from '@/util/route';
import ManagerDashboardLayout from '@/layout/managers/manager-dashboard-layout';
import { Div } from '@/components/semantic';
import FormControlBox from '@/components/forms/form-control-box';
import EnumSelect from '@/components/dropdown-select/enum-select';
import { Gender } from '@/types/types';

interface Props {
  institutionGroup?: InstitutionGroup;
}

export default function CreateInstitutionGroup({ institutionGroup }: Props) {
  const { handleResponseToast } = useMyToast();
  const form = useWebForm({
    first_name: '',
    last_name: '',
    other_names: '',
    phone: '',
    email: '',
    gender: '',
    password: '',
    password_confirmation: '',

    institution_group: {
      name: '',
    },
  });

  const submit = async () => {
    const res = await form.submit((data, web) => {
      return web.post(route('managers.institution-groups.store'), data);
    });
    if (!handleResponseToast(res)) {
      return;
    }
    Inertia.visit(route('managers.institution-groups.index'));
  };

  return (
    <ManagerDashboardLayout>
      <CenteredBox>
        <Slab>
          <SlabHeading
            title={`${institutionGroup ? 'Update' : 'Create'} Group`}
          />
          <SlabBody>
            <Div as={'form'} onSubmit={preventNativeSubmit(submit)} p={6}>
              <FormControl>
                <FormLabel>Institution Group Name</FormLabel>
                <Input
                  type="text"
                  onChange={(e) =>
                    form.setValue('institution_group', {
                      ...form.data.institution_group,
                      name: e.currentTarget.value,
                    })
                  }
                  value={form.data.institution_group.name}
                  required
                />
              </FormControl>
              <br />
              <Text fontWeight={'semibold'} textAlign={'center'}>
                Admin User Information
              </Text>
              <Divider mt={1} mb={2} />

              <VStack spacing={4} align={'stretch'}>
                <FormControlBox
                  form={form}
                  title="First Name"
                  formKey="first_name"
                >
                  <Input
                    type="text"
                    onChange={(e) =>
                      form.setValue('first_name', e.currentTarget.value)
                    }
                    value={form.data.first_name}
                    required
                  />
                </FormControlBox>
                <FormControlBox
                  form={form}
                  title="Last Name"
                  formKey="last_name"
                >
                  <Input
                    type="text"
                    onChange={(e) =>
                      form.setValue('last_name', e.currentTarget.value)
                    }
                    value={form.data.last_name}
                    required
                  />
                </FormControlBox>
                <FormControlBox
                  form={form}
                  title="Other Names"
                  formKey="other_names"
                >
                  <Input
                    type="text"
                    onChange={(e) =>
                      form.setValue('other_names', e.currentTarget.value)
                    }
                    value={form.data.other_names}
                  />
                </FormControlBox>
                <FormControlBox form={form} title="Phone" formKey="phone">
                  <Input
                    type="tel"
                    onChange={(e) =>
                      form.setValue('phone', e.currentTarget.value)
                    }
                    value={form.data.phone}
                  />
                </FormControlBox>
                <FormControlBox form={form} title="Email" formKey="email">
                  <Input
                    type="email"
                    onChange={(e) =>
                      form.setValue('email', e.currentTarget.value)
                    }
                    value={form.data.email}
                    required
                  />
                </FormControlBox>
                <FormControlBox form={form} title="Gender" formKey="gender">
                  <EnumSelect
                    enumData={Gender}
                    onChange={(e: any) => form.setValue('gender', e.value)}
                    required
                  />
                </FormControlBox>
                <FormControlBox form={form} title="Password" formKey="password">
                  <Input
                    type="password"
                    onChange={(e) =>
                      form.setValue('password', e.currentTarget.value)
                    }
                    value={form.data.password}
                    required
                  />
                </FormControlBox>
                <FormControlBox
                  form={form}
                  title="Confirm Password"
                  formKey="password_confirmation"
                >
                  <Input
                    type="password"
                    onChange={(e) =>
                      form.setValue(
                        'password_confirmation',
                        e.currentTarget.value
                      )
                    }
                    value={form.data.password_confirmation}
                    required
                  />
                </FormControlBox>
              </VStack>
              <FormControl mt={2}>
                <FormButton isLoading={form.processing} />
              </FormControl>
            </Div>
          </SlabBody>
        </Slab>
      </CenteredBox>
    </ManagerDashboardLayout>
  );
}
