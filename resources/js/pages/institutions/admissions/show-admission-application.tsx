import { Div } from '@/components/semantic';
import { FormControl, Grid, GridItem, Avatar, HStack } from '@chakra-ui/react';
import React from 'react';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import useWebForm from '@/hooks/use-web-form';
import useInstitutionRoute from '@/hooks/use-institution-route';
import useMyToast from '@/hooks/use-my-toast';
import { AdmissionApplication } from '@/types/models';
import DashboardLayout from '@/layout/dashboard-layout';
import useModalToggle from '@/hooks/use-modal-toggle';
import Dt from '@/components/dt';
import { SelectOptionType } from '@/types/types';
import { BrandButton } from '@/components/buttons';
import useIsAdmin from '@/hooks/use-is-admin';
import AdmitStudentModal from '@/components/modals/admit-student-modal';

interface Props {
  admissionApplication: AdmissionApplication;
}

export default function Profile({ admissionApplication }: Props) {
  const { instRoute } = useInstitutionRoute();
  const { handleResponseToast } = useMyToast();
  const admitStudentModalToggle = useModalToggle();

  const form = useWebForm({
    admission_status: admissionApplication.admission_status,
  });

  const isAdmin = useIsAdmin();

  const updateStatus = async (status: string) => {
    form.setValue('admission_status', status);

    const res = await form.submit((data, web) => {
      return web.post(
        instRoute('admissions.update-status', [admissionApplication]),
        { ...data, admission_status: status }
      );
    });
    if (!handleResponseToast(res)) return;
  };

  const profileData: SelectOptionType<React.ReactNode>[] = [
    { label: 'First Name', value: admissionApplication.first_name },
    { label: 'Last Name', value: admissionApplication.last_name },
    { label: 'Other Names', value: admissionApplication.other_names },
    { label: 'Gender', value: admissionApplication.gender },
    { label: 'Date of Birth', value: admissionApplication.dob },
    { label: 'Religion', value: admissionApplication.religion },
    { label: 'Local Govt. Area', value: admissionApplication.lga },
    { label: 'State', value: admissionApplication.state },
    { label: 'Nationality', value: admissionApplication.nationality },
    {
      label: 'Intended Class',
      value: admissionApplication.intended_class_of_admission,
    },
    {
      label: 'Previous School',
      value: admissionApplication.previous_school_attended,
    },
    { label: "Father's Name", value: admissionApplication.fathers_name },
    {
      label: "Father's Occupation",
      value: admissionApplication.fathers_occupation,
    },
    { label: "Father's Phone", value: admissionApplication.fathers_phone },
    { label: "Father's Email", value: admissionApplication.fathers_email },
    {
      label: "Father's Residential Address",
      value: admissionApplication.fathers_residential_address,
    },
    {
      label: "Father's Office Address",
      value: admissionApplication.fathers_office_address,
    },
    { label: "Mother's Name", value: admissionApplication.mothers_name },
    {
      label: "Mother's Occupation",
      value: admissionApplication.mothers_occupation,
    },
    { label: "Mother's Phone", value: admissionApplication.mothers_phone },
    { label: "Mother's Email", value: admissionApplication.mothers_email },
    {
      label: "Mother's Residential Address",
      value: admissionApplication.mothers_residential_address,
    },
    {
      label: "Mother's Office Address",
      value: admissionApplication.mothers_office_address,
    },
    { label: 'Reference', value: admissionApplication.reference },
  ];

  return (
    <Div>
      {isAdmin && admissionApplication.admission_status === 'pending' ? ( 
        <HStack align={'stretch'} my={2}>
          <BrandButton
            title="Admit Student"
            onClick={admitStudentModalToggle.open}
          />
          <BrandButton
            title="Deny Admission"
            onClick={() => updateStatus('declined')}
          />
        </HStack>
      ) : (
        ''
      )}
      <Slab>
        <SlabHeading
          title={`${admissionApplication.last_name} ${admissionApplication.first_name}'s Application`}
        />
        <SlabBody>
          <Grid templateColumns={{ lg: 'repeat(3, 1fr)' }} gap={4}>
            <GridItem colSpan={{ lg: 2 }}>
              <Dt contentData={profileData} spacing={4} labelWidth={'150px'} />
            </GridItem>
            <GridItem colSpan={{ lg: 1 }}>
              <FormControl>
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
                    <Avatar size={'2xl'} src={admissionApplication.photo} />
                  </Div>
                </Div>
              </FormControl>
            </GridItem>
          </Grid>
        </SlabBody>
      </Slab>
      <AdmitStudentModal
        {...admitStudentModalToggle.props}
        onSuccess={() => {}}
        admissionApplication={admissionApplication}
      />
    </Div>
  );
}

Profile.layout = (page: any) => <DashboardLayout children={page} />;