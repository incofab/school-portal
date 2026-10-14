import React from 'react';
import {
  Badge,
  Button,
  HStack,
  Select,
  Table,
  Tbody,
  Td,
  Text,
  Th,
  Thead,
  Tr,
  VStack,
  Input,
} from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import {
  Designation,
  DesignationAssignment,
  AcademicSession,
  Classification,
} from '@/types/models';
import { PaginationResponse } from '@/types/types';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { Inertia } from '@inertiajs/inertia';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';

interface Props {
  assignments: PaginationResponse<DesignationAssignment>;
  designations: Designation[];
  classifications: Classification[];
  academicSessions: AcademicSession[];
}
export default function ListDesignationAssignments({
  assignments,
  designations,
  classifications,
  academicSessions,
}: Props) {
  const { instRoute } = useInstitutionRoute();
  const filters = new URLSearchParams(window.location.search);
  const form = useWebForm({
    academic_session_id: filters.get('academic_session_id') ?? '',
    designation_id: filters.get('designation_id') ?? '',
    classification_id: filters.get('classification_id') ?? '',
    person: filters.get('person') ?? '',
    status: filters.get('status') ?? 'active',
  });
  const actionForm = useWebForm({});
  const { handleResponseToast } = useMyToast();
  function filter(e: React.FormEvent) {
    e.preventDefault();
    Inertia.visit(instRoute('designation-assignments.index', form.data));
  }
  async function end(item: DesignationAssignment) {
    if (!window.confirm('End this assignment and preserve it in history?'))
      return;
    const result = await actionForm.submit((_, web) =>
      web.post(instRoute('designation-assignments.end', [item]))
    );
    if (handleResponseToast(result)) Inertia.reload();
  }
  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading
          title="Designation assignments"
          rightElement={
            <HStack>
              <LinkButton
                title="Designations"
                href={instRoute('designations.index')}
              />
            </HStack>
          }
        />
        <SlabBody>
          <VStack
            as="form"
            align="stretch"
            spacing={3}
            mb={5}
            onSubmit={filter}
          >
            <HStack align="end" flexWrap="wrap">
              <label>
                Academic session
                <Select
                  value={form.data.academic_session_id}
                  onChange={(e) =>
                    form.setValue('academic_session_id', e.target.value)
                  }
                >
                  <option value="">All sessions</option>
                  {academicSessions.map((s) => (
                    <option key={s.id} value={s.id}>
                      {s.title}
                    </option>
                  ))}
                </Select>
              </label>
              <label>
                Designation
                <Select
                  value={form.data.designation_id}
                  onChange={(e) =>
                    form.setValue('designation_id', e.target.value)
                  }
                >
                  <option value="">All designations</option>
                  {designations.map((d) => (
                    <option key={d.id} value={d.id}>
                      {d.title}
                    </option>
                  ))}
                </Select>
              </label>
              <label>
                Class snapshot
                <Select
                  value={form.data.classification_id}
                  onChange={(e) =>
                    form.setValue('classification_id', e.target.value)
                  }
                >
                  <option value="">All classes</option>
                  {classifications.map((c) => (
                    <option key={c.id} value={c.id}>
                      {c.title}
                    </option>
                  ))}
                </Select>
              </label>
              <label>
                Person
                <Input
                  value={form.data.person}
                  onChange={(e) => form.setValue('person', e.target.value)}
                  placeholder="Search name"
                />
              </label>
              <label>
                Status
                <Select
                  value={form.data.status}
                  onChange={(e) => form.setValue('status', e.target.value)}
                >
                  <option value="active">Active</option>
                  <option value="history">History</option>
                  <option value="all">All</option>
                </Select>
              </label>
              <Button type="submit" colorScheme="brand">
                Apply filters
              </Button>
            </HStack>
          </VStack>
          <Table size="sm">
            <Thead>
              <Tr>
                <Th>Person</Th>
                <Th>Designation</Th>
                <Th>Class snapshot</Th>
                <Th>Academic session</Th>
                <Th>Assigned</Th>
                <Th>Status</Th>
                <Th />
              </Tr>
            </Thead>
            <Tbody>
              {assignments.data.map((item) => (
                <Tr key={item.id}>
                  <Td>
                    {item.institution_user?.user?.full_name ?? 'Unknown'}
                    {item.institution_user?.student?.code && (
                      <Text fontSize="xs" color="gray.600">
                        {item.institution_user.student.code}
                      </Text>
                    )}
                  </Td>
                  <Td>{item.designation?.title}</Td>
                  <Td>{item.classification?.title ?? '—'}</Td>
                  <Td>{item.academic_session?.title}</Td>
                  <Td>{new Date(item.assigned_at).toLocaleDateString()}</Td>
                  <Td>
                    <Badge colorScheme={item.ended_at ? 'gray' : 'green'}>
                      {item.ended_at ? 'Ended' : 'Active'}
                    </Badge>
                  </Td>
                  <Td>
                    {!item.ended_at && (
                      <Button
                        size="sm"
                        variant="outline"
                        colorScheme="red"
                        isLoading={actionForm.processing}
                        onClick={() => end(item)}
                      >
                        End
                      </Button>
                    )}
                  </Td>
                </Tr>
              ))}
            </Tbody>
          </Table>
          {assignments.data.length === 0 && (
            <Text py={4} color="gray.600">
              No designation assignments match these filters.
            </Text>
          )}
        </SlabBody>
      </Slab>
    </DashboardLayout>
  );
}

function LinkButton({ title, href }: { title: string; href: string }) {
  return (
    <Button as="a" href={href} variant="outline">
      {title}
    </Button>
  );
}
