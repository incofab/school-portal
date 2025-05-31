import React from 'react';
import { Exam, ExamCourseable } from '@/types/models';
import {
  HStack,
  IconButton,
  Icon,
  Divider,
  VStack,
  Spacer,
} from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import { Inertia } from '@inertiajs/inertia';
import ServerPaginatedTable from '@/components/server-paginated-table';
import { PaginationResponse } from '@/types/types';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { ServerPaginatedTableHeader } from '@/components/server-paginated-table';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { TrashIcon } from '@heroicons/react/24/solid';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import DestructivePopover from '@/components/destructive-popover';
import { LabelText } from '@/components/result-helper-components';
import tokenUserUtil from '@/util/token-user-util';
import { BrandButton, LinkButton } from '@/components/buttons';
import useIsStaff from '@/hooks/use-is-staff';
import { PageTitle } from '@/components/page-header';

interface Props {
  exam: Exam;
  examCourseables: PaginationResponse<ExamCourseable>;
}

export default function ListExamCourseables({ exam, examCourseables }: Props) {
  const { instRoute } = useInstitutionRoute();
  const deleteForm = useWebForm({});
  const reEvaluateForm = useWebForm({});
  const { handleResponseToast } = useMyToast();
  const isStaff = useIsStaff();

  async function deleteItem(obj: ExamCourseable) {
    const res = await deleteForm.submit((data, web) =>
      web.delete(instRoute('exam-courseables.destroy', [obj.id]))
    );
    if (!handleResponseToast(res)) return;
    Inertia.reload({ only: ['examCourseables'] });
  }

  async function reEvaluate() {
    if (!window.confirm('Are you sure you want to re-evaluate this exam?')) {
      return;
    }
    const res = await reEvaluateForm.submit((data, web) =>
      web.post(instRoute('end-exam', [exam.id]) + `?re_evaluate=true`)
    );
    if (!handleResponseToast(res)) return;
    Inertia.reload({ only: ['examCourseables'] });
  }

  const headers: ServerPaginatedTableHeader<ExamCourseable>[] = [
    {
      label: 'Title',
      value: 'title',
      render: (row) =>
        `${row.courseable?.course?.title} - ${row.courseable?.session}`,
    },
    {
      label: 'Score',
      value: 'score',
      render: (row) => row.score + '',
    },
    {
      label: 'Num Of Questions',
      value: 'num_of_questions',
      render: (row) => row.num_of_questions + '',
    },
    ...(isStaff
      ? [
          {
            label: 'Action',
            render: (row: ExamCourseable) => (
              <HStack>
                <LinkButton
                  href={instRoute('exam-courseables.show', [
                    row.exam_id,
                    row.id,
                  ])}
                  variant={'link'}
                  title="Question Details"
                />
                <DestructivePopover
                  label={'Delete this subjects'}
                  onConfirm={() => deleteItem(row)}
                  isLoading={deleteForm.processing}
                >
                  <IconButton
                    aria-label={'Delete subject'}
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

  const details = [
    { label: 'Event', value: exam.event?.title },
    {
      label: 'User',
      value: tokenUserUtil(exam.examable).getName() ?? exam.external_reference,
    },
    { label: 'Exam No', value: exam.exam_no },
    { label: 'Score', value: exam.score },
    { label: 'Num of Questions', value: exam.num_of_questions },
    { label: 'Status', value: exam.status },
  ];

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading>
          <HStack>
            <PageTitle>Exam Subjects</PageTitle>
            <Spacer />
            <BrandButton
              onClick={reEvaluate}
              isLoading={reEvaluateForm.processing}
              title={'Re-Evaluate'}
            />
          </HStack>
        </SlabHeading>
        <SlabBody>
          <VStack align={'stretch'} spacing={2}>
            {details.map((item) => (
              <LabelText
                key={item.label}
                label={item.label}
                text={item.value}
                labelProps={{ width: '150px' }}
              />
            ))}
          </VStack>
          <Divider my={1} />
          <ServerPaginatedTable
            scroll={true}
            headers={headers}
            data={examCourseables.data}
            keyExtractor={(row) => row.id}
            paginator={examCourseables}
            hideSearchField={true}
          />
        </SlabBody>
      </Slab>
    </DashboardLayout>
  );
}
