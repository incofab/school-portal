import React from 'react';
import { Fee } from '@/types/models';
import {
  HStack,
  IconButton,
  Icon,
  Text,
  Popover,
  Tooltip,
} from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import { Inertia } from '@inertiajs/inertia';
import ServerPaginatedTable from '@/components/server-paginated-table';
import { PaginationResponse } from '@/types/types';
import { ClockIcon, PencilIcon } from '@heroicons/react/24/outline';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { BrandButton, LinkButton } from '@/components/buttons';
import { ServerPaginatedTableHeader } from '@/components/server-paginated-table';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { InertiaLink } from '@inertiajs/inertia-react';
import { TrashIcon } from '@heroicons/react/24/solid';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import DestructivePopover from '@/components/destructive-popover';
import useIsAdmin from '@/hooks/use-is-admin';
import { formatAsCurrency } from '@/util/util';
import feeableUtil from '@/util/feeable-util';
import { Div } from '@/components/semantic';
import { LabelText } from '@/components/result-helper-components';
import useModalToggle, {
  useModalValueToggle,
} from '@/hooks/use-modal-toggle';
import CreateFeeReminderModal from '@/components/modals/create-fee-reminder-modal';
import DuplicateFeesModal from '@/components/modals/duplicate-fees-modal';

interface Props {
  fees: PaginationResponse<Fee>;
}

export default function ListFees({ fees }: Props) {
  const { instRoute } = useInstitutionRoute();
  const deleteForm = useWebForm({});
  const { handleResponseToast } = useMyToast();
  const reminderToggle = useModalValueToggle<Fee>();
  const duplicateFeesToggle = useModalToggle();
  const isAdmin = useIsAdmin();

  async function deleteItem(obj: Fee) {
    const res = await deleteForm.submit((data, web) =>
      web.delete(instRoute('fees.destroy', [obj.id]))
    );
    handleResponseToast(res);
    Inertia.reload({ only: ['fees'] });
  }

  const headers: ServerPaginatedTableHeader<Fee>[] = [
    {
      label: 'Title',
      value: 'title',
    },
    {
      label: 'Amount',
      value: 'amount',
    },
    {
      label: 'Items',
      render: (row) => (
        <Div>
          {row.fee_items?.map((item) => (
            <LabelText
              key={item.title}
              label={item.title}
              text={formatAsCurrency(item.amount)}
            />
          ))}
        </Div>
      ),
    },
    {
      label: 'Sectors',
      render: (row) =>
        row.fee_categories
          ?.map((item) =>
            feeableUtil(item.feeable, item.feeable_type).getName()
          )
          .join(', '),
    },
    {
      label: 'Interval',
      value: 'payment_interval',
    },
    ...(isAdmin
      ? [
          {
            label: 'Action',
            render: (row: Fee) => (
              <HStack>
                <Tooltip label="Send Reminder" title={'Send Reminder'}>
                  <IconButton
                    aria-label={'Send Reminder'}
                    icon={<Icon as={ClockIcon} />}
                    variant={'ghost'}
                    colorScheme={'brand'}
                    onClick={() => reminderToggle.open(row)}
                  />
                </Tooltip>
                <IconButton
                  aria-label={'Edit Fee'}
                  icon={<Icon as={PencilIcon} />}
                  as={InertiaLink}
                  href={instRoute('fees.edit', [row.id])}
                  variant={'ghost'}
                  colorScheme={'brand'}
                />
                <DestructivePopover
                  label={'Delete this fee'}
                  onConfirm={() => deleteItem(row)}
                  isLoading={deleteForm.processing}
                >
                  <IconButton
                    aria-label={'Delete fee'}
                    icon={<Icon as={TrashIcon} />}
                    variant={'ghost'}
                    colorScheme={'red'}
                  />
                </DestructivePopover>
              </HStack>
            ),
          },
        ]
      : []),
  ];

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading
          title="List Fees"
          rightElement={
            <HStack spacing={2}>
              {isAdmin && (
                <BrandButton
                  title={'Duplicate Fees'}
                  variant={'outline'}
                  onClick={duplicateFeesToggle.open}
                />
              )}
              <LinkButton href={instRoute('fees.create')} title={'New'} />
            </HStack>
          }
        />
        <SlabBody>
          <ServerPaginatedTable
            scroll={true}
            headers={headers}
            data={fees.data}
            keyExtractor={(row) => row.id}
            paginator={fees}
          />
        </SlabBody>
      </Slab>
      {reminderToggle.state && (
        <CreateFeeReminderModal
          selectedFee={reminderToggle.state}
          {...reminderToggle.props}
          fees={fees.data}
          onSuccess={() => {}}
        />
      )}
      <DuplicateFeesModal
        {...duplicateFeesToggle.props}
        onSuccess={() => Inertia.reload({ only: ['fees'] })}
      />
    </DashboardLayout>
  );
}
