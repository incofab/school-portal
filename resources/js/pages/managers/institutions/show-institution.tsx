import React from 'react';
import { Institution } from '@/types/models';
import { Avatar, Grid, GridItem } from '@chakra-ui/react';
import { SelectOptionType } from '@/types/types';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import ManagerDashboardLayout from '@/layout/managers/manager-dashboard-layout';
import Dt from '@/components/dt';
import { Div } from '@/components/semantic';

interface Props {
  institution: Institution;
}

export default function ListInstitutions({ institution }: Props) {
  const profileData: SelectOptionType<React.ReactNode>[] = [
    { label: 'Name', value: institution.name },
    { label: 'Sub Title', value: institution.subtitle },
    { label: 'Caption', value: institution.caption },
    { label: 'Email', value: institution.email },
    { label: 'Phone', value: institution.phone },
    { label: 'Website', value: institution.website },
    { label: 'Address', value: institution.address },
  ];

  return (
    <ManagerDashboardLayout>
      <Slab>
        <SlabHeading title="Institutions" />
        <SlabBody>
          <Grid templateColumns={{ lg: 'repeat(3, 1fr)' }} gap={4}>
            <GridItem colSpan={{ lg: 2 }}>
              <Dt contentData={profileData} spacing={4} labelWidth={'150px'} />
            </GridItem>
            <GridItem colSpan={{ lg: 1 }}>
              <Div
                display={'flex'}
                alignItems={'center'}
                justifyContent={'center'}
                w={200}
                h={200}
                borderWidth={1}
                borderColor={'gray.200'}
              >
                <Avatar size={'2xl'} src={institution.photo} />
              </Div>
            </GridItem>
          </Grid>
        </SlabBody>
      </Slab>
    </ManagerDashboardLayout>
  );
}
