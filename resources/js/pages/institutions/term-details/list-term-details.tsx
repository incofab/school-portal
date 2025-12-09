import ServerPaginatedTable, {
  ServerPaginatedTableHeader,
} from '@/components/server-paginated-table';
import { TermDetail } from '@/types/models';
import { PaginationResponse } from '@/types/types';
import {
  Divider,
  Icon,
  IconButton,
  Input,
  Text,
  VStack,
} from '@chakra-ui/react';
import startCase from 'lodash/startCase';
import React from 'react';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import DashboardLayout from '@/layout/dashboard-layout';
import useInstitutionRoute from '@/hooks/use-institution-route';
import useMyToast from '@/hooks/use-my-toast';
import useWebForm from '@/hooks/use-web-form';
import InputForm from '@/components/forms/input-form';
import FormControlBox from '@/components/forms/form-control-box';
import { BrandButton } from '@/components/buttons';
import { Div } from '@/components/semantic';
import { dateFormat, ucFirst } from '@/util/util';
import { Inertia } from '@inertiajs/inertia';
import DateTimeDisplay from '@/components/date-time-display';
import { format } from 'date-fns';
import { InertiaLink } from '@inertiajs/inertia-react';
import { PencilIcon } from '@heroicons/react/24/outline';

interface Props {
  termDetail: TermDetail;
  termDetails: PaginationResponse<TermDetail>;
}

export default function ListTermDetails({ termDetail, termDetails }: Props) {
  const { instRoute } = useInstitutionRoute();
  const headers: ServerPaginatedTableHeader<TermDetail>[] = [
    {
      label: 'Academic Session',
      value: 'academic_session.title',
    },
    {
      label: 'Term',
      value: 'term',
      render: (row) => (
        <Text>
          {startCase(row.term)} {row.for_mid_term ? 'Mid-' : ''}Term
        </Text>
      ),
    },
    {
      label: 'Start Date',
      value: 'start_date',
      render: (row) =>
        row.start_date ? (
          <DateTimeDisplay
            dateTime={row.start_date}
            dateTimeformat={dateFormat}
          />
        ) : null,
    },
    {
      label: 'End Date',
      value: 'end_date',
      render: (row) =>
        row.end_date ? (
          <DateTimeDisplay
            dateTime={row.end_date}
            dateTimeformat={dateFormat}
          />
        ) : null,
    },
    {
      label: 'School Held',
      value: 'expected_attendance_count',
    },
    {
      label: 'Next Term Resumption',
      value: 'next_term_resumption_date',
      render: (row) =>
        row.next_term_resumption_date ? (
          <DateTimeDisplay
            dateTime={row.next_term_resumption_date}
            dateTimeformat={dateFormat}
          />
        ) : null,
    },
    {
      label: 'Action',
      render: (row) => (
        <IconButton
          as={InertiaLink}
          aria-label={'Edit Term Detail'}
          icon={<Icon as={PencilIcon} />}
          href={instRoute('term-details.index', [row.id])}
          variant={'ghost'}
          colorScheme={'brand'}
        />
      ),
    },
  ];

  return (
    <DashboardLayout>
      <Slab>
        <SlabBody>
          <Div maxWidth={'500px'}>
            <UpdateTermDetail termDetail={termDetail} />
          </Div>
        </SlabBody>
      </Slab>
      <br />
      <Slab>
        <SlabHeading title="Term Results" />
        <SlabBody>
          <ServerPaginatedTable
            scroll={true}
            headers={headers}
            data={termDetails.data}
            keyExtractor={(row) => row.id}
            paginator={termDetails}
            hideSearchField={true}
          />
        </SlabBody>
      </Slab>
    </DashboardLayout>
  );
}

function UpdateTermDetail({ termDetail }: { termDetail: TermDetail }) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const webForm = useWebForm({
    start_date: termDetail.start_date
      ? format(new Date(termDetail.start_date), dateFormat)
      : '',
    end_date: termDetail.end_date
      ? format(new Date(termDetail.end_date), dateFormat)
      : '',
    expected_attendance_count: String(
      termDetail.expected_attendance_count ?? ''
    ),
    next_term_resumption_date: termDetail.next_term_resumption_date
      ? format(new Date(termDetail.next_term_resumption_date), dateFormat)
      : '',
  });

  const onSubmit = async () => {
    const res = await webForm.submit((data, web) =>
      web.put(instRoute('term-details.update', [termDetail]), data)
    );
    if (!handleResponseToast(res)) return;
    Inertia.reload({ only: ['termDetails'] });
  };
  const title = `Update ${termDetail.academic_session?.title} ${
    termDetail.for_mid_term ? 'Mid-' : ''
  } ${ucFirst(termDetail.term)} Term Detail`;
  return (
    <VStack spacing={2} align={'start'}>
      <Text fontWeight={'bold'} fontSize={'20px'}>
        {title}
      </Text>
      <Divider />
      <FormControlBox
        form={webForm as any}
        title="Opening Date"
        formKey="start_date"
      >
        <Input
          type={'date'}
          value={webForm.data.start_date}
          onChange={(e) =>
            webForm.setValue('start_date', e.currentTarget.value)
          }
        />
      </FormControlBox>
      <FormControlBox
        form={webForm as any}
        title="Closing Date"
        formKey="end_date"
      >
        <Input
          type={'date'}
          value={webForm.data.end_date}
          onChange={(e) => webForm.setValue('end_date', e.currentTarget.value)}
        />
      </FormControlBox>
      <FormControlBox
        form={webForm as any}
        title="Next Term Resumption Date [Optional]"
        formKey="next_term_resumption_date"
      >
        <Input
          type={'date'}
          value={webForm.data.next_term_resumption_date}
          onChange={(e) =>
            webForm.setValue('next_term_resumption_date', e.currentTarget.value)
          }
        />
      </FormControlBox>
      <InputForm
        form={webForm as any}
        formKey="expected_attendance_count"
        title="No of Times School Held"
        onChange={(e) =>
          webForm.setValue('expected_attendance_count', e.currentTarget.value)
        }
      />
      <BrandButton
        colorScheme={'brand'}
        onClick={onSubmit}
        isLoading={webForm.processing}
      >
        Save
      </BrandButton>
    </VStack>
  );
}
