import React from 'react';
import { PinPrint } from '@/types/models';
import DashboardLayout from '@/layout/dashboard-layout';
import { Inertia } from '@inertiajs/inertia';
import ServerPaginatedTable from '@/components/server-paginated-table';
import { PaginationResponse } from '@/types/types';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { BrandButton, LinkButton } from '@/components/buttons';
import { ServerPaginatedTableHeader } from '@/components/server-paginated-table';
import useInstitutionRoute from '@/hooks/use-institution-route';
import useModalToggle from '@/hooks/use-modal-toggle';
import PinPrintModal from '@/components/modals/pin-print-modal';
import DateTimeDisplay from '@/components/date-time-display';
import { Button, HStack } from '@chakra-ui/react';

interface Props {
  pinPrints: PaginationResponse<PinPrint>;
}

export default function ListPrintedPins({ pinPrints }: Props) {
  const { instRoute } = useInstitutionRoute();
  const pinPrintModalToggle = useModalToggle();

  const headers: ServerPaginatedTableHeader<PinPrint>[] = [
    {
      label: 'Printed By',
      value: 'user.full_name',
    },
    {
      label: 'Num of Pins',
      value: 'num_of_pins',
    },
    {
      label: 'Comment',
      value: 'comment',
    },
    {
      label: 'Printed On',
      value: 'created_at',
      render: (row) => <DateTimeDisplay dateTime={row.created_at} />,
    },
    {
      label: 'Display Pins',
      render: (row) => (
        <HStack spacing={2}>
          <LinkButton
            variant={'link'}
            title="Display Pins"
            href={instRoute('pin-prints.show', [row.id])}
          />
          <Button
            as={'a'}
            variant={'link'}
            href={instRoute('pin-prints.download', [row.id])}
            colorScheme="brand"
            fontWeight={'normal'}
          >
            Download
          </Button>
        </HStack>
      ),
    },
  ];

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading
          title="List Printed Pins"
          rightElement={
            <BrandButton
              onClick={pinPrintModalToggle.open}
              title={'Generate Pins'}
            />
          }
        />
        <SlabBody>
          <ServerPaginatedTable
            scroll={true}
            headers={headers}
            data={pinPrints.data}
            keyExtractor={(row) => row.id}
            paginator={pinPrints}
          />
        </SlabBody>
        <PinPrintModal
          {...pinPrintModalToggle.props}
          onSuccess={(pinPrint) =>
            Inertia.visit(instRoute('pin-prints.show', [pinPrint.id]))
          }
        />
      </Slab>
    </DashboardLayout>
  );
}
