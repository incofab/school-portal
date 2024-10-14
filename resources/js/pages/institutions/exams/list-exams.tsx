import React from 'react';
import { Exam } from '@/types/models';
import { HStack, IconButton, Icon } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import { Inertia } from '@inertiajs/inertia';
import ServerPaginatedTable from '@/components/server-paginated-table';
import { PaginationResponse } from '@/types/types';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { LinkButton } from '@/components/buttons';
import { ServerPaginatedTableHeader } from '@/components/server-paginated-table';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { TrashIcon } from '@heroicons/react/24/solid';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import DestructivePopover from '@/components/destructive-popover';
import useIsAdmin from '@/hooks/use-is-admin';
import tokenUserUtil from '@/util/token-user-util';

interface Props {
  exams: PaginationResponse<Exam>;
  event: Event;
}

export default function ListExams({ exams, event }: Props) {
  const { instRoute } = useInstitutionRoute();
  const deleteForm = useWebForm({});
  const { handleResponseToast } = useMyToast();
  const isAdmin = useIsAdmin();

  async function deleteItem(obj: Exam) {
    const res = await deleteForm.submit((data, web) =>
      web.delete(instRoute('exams.destroy', [obj.id]))
    );
    handleResponseToast(res);
    Inertia.reload({ only: ['exams'] });
  }

  const headers: ServerPaginatedTableHeader<Exam>[] = [
    {
      label: 'Title',
      render: (row) =>
        String(tokenUserUtil(row.examable).getName() ?? row.external_reference),
    },
    {
      label: 'Num of Subjects',
      value: 'exam_courseables_count',
    },
    {
      label: 'Exam No',
      value: 'exam_no',
    },
    {
      label: 'Score',
      value: 'score',
    },
    {
      label: 'Status',
      value: 'status',
    },
    ...(isAdmin
      ? [
          {
            label: 'Action',
            render: (row: Exam) => (
              <HStack>
                <LinkButton
                  href={instRoute('exam-courseables.index', [row.id])}
                  variant={'link'}
                  title="Detail"
                />
                {/* <IconButton
                  aria-label={'Edit Exam'}
                  icon={<Icon as={PencilIcon} />}
                  as={InertiaLink}
                  href={instRoute('exams.edit', [row.id])}
                  variant={'ghost'}
                  colorScheme={'brand'}
                /> */}
                <DestructivePopover
                  label={'Delete this exam'}
                  onConfirm={() => deleteItem(row)}
                  isLoading={deleteForm.processing}
                >
                  <IconButton
                    aria-label={'Delete exam'}
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
          title="List Exams"
          // rightElement={
          // <LinkButton
          //   href={instRoute('exams.create', [event])}
          //   title={'New'}
          // />
          // }
        />
        <SlabBody>
          <ServerPaginatedTable
            scroll={true}
            headers={headers}
            data={exams.data}
            keyExtractor={(row) => row.id}
            paginator={exams}
            hideSearchField={true}
          />
        </SlabBody>
      </Slab>
    </DashboardLayout>
  );
}
