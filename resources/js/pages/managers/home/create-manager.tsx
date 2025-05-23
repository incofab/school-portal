import React, { useEffect } from 'react';
import { FormControl, Input, VStack } from '@chakra-ui/react';
import useWebForm from '@/hooks/use-web-form';
import { preventNativeSubmit } from '@/util/util';
import { Inertia } from '@inertiajs/inertia';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import CenteredBox from '@/components/centered-box';
import { FormButton } from '@/components/buttons';
import useMyToast from '@/hooks/use-my-toast';
import route from '@/util/route';
import ManagerDashboardLayout from '@/layout/managers/manager-dashboard-layout';
import { Div } from '@/components/semantic';
import FormControlBox from '@/components/forms/form-control-box';
import EnumSelect from '@/components/dropdown-select/enum-select';
import { Gender, ManagerRole } from '@/types/types';

interface Props {} 

export default function CreateManager({}: Props) {
  const { handleResponseToast } = useMyToast();
  const form = useWebForm({
    first_name: '',
    last_name: '',
    other_names: '',
    username: '',
    phone: '',
    email: '',
    gender: '',
    password: '',
    password_confirmation: '',
    role: '',
    commission: '', // New field for commission
    referral_email: '', // New field for referral user email
    referral_commission: '', // New field for referral commission 
  });

  // Watch role change to show/hide extra fields
  const isPartner = form.data.role === ManagerRole.Partner; 

  const submit = async () => {
    const res = await form.submit((data, web) => {
      return web.post(route('managers.store'), data);
    });
    if (!handleResponseToast(res)) {
      return;
    }
    Inertia.visit(route('managers.index'));
  };

  return (
    <ManagerDashboardLayout>
      <CenteredBox>
        <Slab>
          <SlabHeading title={`Create Manager`} />
          <SlabBody>
            <Div as={'form'} onSubmit={preventNativeSubmit(submit)} p={6}>
              <VStack spacing={4} align={'stretch'}>
                {/* Existing form fields */}
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
                <FormControlBox form={form} title="Username" formKey="username">
                  <Input
                    type="text"
                    onChange={(e) =>
                      form.setValue('username', e.currentTarget.value)
                    }
                    value={form.data.username}
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
                <FormControlBox form={form} title="Manager Role" formKey="role">
                  <EnumSelect
                    enumData={ManagerRole}
                    onChange={(e: any) => form.setValue('role', e.value)}
                    required
                  />
                </FormControlBox>

                {/* Extra fields for 'Partner' role */}
                {isPartner && (
                  <>
                    <FormControlBox
                      form={form}
                      title="Commission"
                      formKey="commission"
                    >
                      <Input
                        type="number"
                        onChange={(e) =>
                          form.setValue('commission', e.currentTarget.value)
                        }
                        value={form.data.commission}
                      />
                    </FormControlBox>
                    <FormControlBox
                      form={form}
                      title="Referral User Email"
                      formKey="referral_email"
                    >
                      <Input
                        type="email"
                        onChange={(e) =>
                          form.setValue('referral_email', e.currentTarget.value)
                        }
                        value={form.data.referral_email}
                      />
                    </FormControlBox>
                    <FormControlBox
                      form={form}
                      title="Referral Commission"
                      formKey="referral_commission"
                    >
                      <Input
                        type="number"
                        onChange={(e) =>
                          form.setValue(
                            'referral_commission',
                            e.currentTarget.value
                          )
                        }
                        value={form.data.referral_commission}
                      />
                    </FormControlBox>
                  </>
                )}
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
