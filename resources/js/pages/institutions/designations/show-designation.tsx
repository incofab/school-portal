import React, { useEffect, useState } from 'react';
import axios from 'axios';
import {
  Badge,
  Button,
  FormControl,
  FormErrorMessage,
  FormLabel,
  HStack,
  Select,
  Table,
  Tbody,
  Td,
  Text,
  Textarea,
  Th,
  Thead,
  Tr,
  VStack,
} from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import {
  Designation,
  DesignationAssignment,
  AcademicSession,
  InstitutionUser,
} from '@/types/models';
import { PaginationResponse } from '@/types/types';
import { LinkButton, FormButton } from '@/components/buttons';
import useInstitutionRoute from '@/hooks/use-institution-route';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import { Inertia } from '@inertiajs/inertia';
import { preventNativeSubmit } from '@/util/util';

interface Props {
  designation: Designation;
  assignments: PaginationResponse<DesignationAssignment>;
  academicSessions: AcademicSession[];
  canManageStaffDesignations: boolean;
}
interface Targets {
  people: InstitutionUser[];
  holder_limit: number;
  current_count: number;
  current_counts_by_class: Record<string, number>;
  scope: string;
}

export default function ShowDesignation({
  designation,
  assignments,
  academicSessions,
  canManageStaffDesignations,
}: Props) {
  const { instRoute } = useInstitutionRoute();
  const { handleResponseToast } = useMyToast();
  const activeSession =
    academicSessions.find((session) => session.is_active) ??
    academicSessions[0];
  const form = useWebForm({
    designation_id: designation.id,
    academic_session_id: activeSession?.id ?? '',
    institution_user_id: '',
    note: '',
  });
  const endForm = useWebForm({});
  const [targets, setTargets] = useState<Targets | null>(null);
  const [targetError, setTargetError] = useState('');
  useEffect(() => {
    if (!form.data.academic_session_id || !designation.is_active) {
      setTargets(null);
      return;
    }
    setTargetError('');
    axios
      .get<Targets>(
        instRoute('designation-assignments.targets', {
          designation_id: designation.id,
          academic_session_id: form.data.academic_session_id,
        })
      )
      .then((response) => setTargets(response.data))
      .catch((error) => {
        setTargets(null);
        setTargetError(
          error.response?.data?.message ?? 'Unable to load eligible people.'
        );
      });
  }, [form.data.academic_session_id, designation.id, designation.is_active]);
  async function assign() {
    const result = await form.submit((data, web) =>
      web.post(instRoute('designation-assignments.store'), data)
    );
    if (handleResponseToast(result)) Inertia.reload();
  }
  async function endAssignment(item: DesignationAssignment) {
    if (
      !window.confirm(
        'End this designation assignment? The history record will be preserved.'
      )
    )
      return;
    const result = await endForm.submit((_, web) =>
      web.post(instRoute('designation-assignments.end', [item]))
    );
    if (handleResponseToast(result)) Inertia.reload();
  }
  const personName = (item: DesignationAssignment) =>
    item.institution_user?.user?.full_name ?? 'Unknown person';
  const selectedPerson = targets?.people.find(
    (person) => person.id === Number(form.data.institution_user_id)
  );
  const applicableCount =
    targets?.scope === 'classification' &&
    selectedPerson?.student?.classification_id
      ? targets.current_counts_by_class[
          String(selectedPerson.student.classification_id)
        ] ?? 0
      : targets?.current_count ?? 0;
  return (
    <DashboardLayout>
      <VStack align="stretch" spacing={5}>
        <Slab>
          <SlabHeading
            title={designation.title}
            rightElement={
              <HStack>
                {(designation.is_for_students ||
                  canManageStaffDesignations) && (
                  <LinkButton
                    title="Edit"
                    href={instRoute('designations.edit', [designation])}
                  />
                )}
                <LinkButton
                  title="All designations"
                  href={instRoute('designations.index')}
                />
              </HStack>
            }
          />
          <SlabBody>
            <HStack mb={3}>
              <Badge
                colorScheme={designation.is_for_students ? 'blue' : 'purple'}
              >
                {designation.is_for_students
                  ? 'Student designation'
                  : 'Staff designation'}
              </Badge>
              <Badge colorScheme={designation.is_active ? 'green' : 'gray'}>
                {designation.is_active ? 'Active' : 'Inactive'}
              </Badge>
              <Text>
                {designation.scope === 'classification'
                  ? 'Limit per eligible class'
                  : 'Institution-wide holder limit'}{' '}
                · {designation.max_holders} holder(s)
              </Text>
            </HStack>
            <Text>{designation.description || 'No description provided.'}</Text>
            <Text mt={3}>
              <b>Eligible classes:</b>{' '}
              {designation.is_for_students
                ? designation.eligible_classes?.map((c) => c.title).join(', ')
                : 'Not applicable'}
            </Text>
          </SlabBody>
        </Slab>
        <Slab>
          <SlabHeading title="Assign this designation" />
          <SlabBody>
            {!designation.is_active ? (
              <Text color="gray.600">
                This designation is inactive and cannot receive new assignments.
              </Text>
            ) : (
              <VStack
                as="form"
                align="stretch"
                spacing={4}
                onSubmit={preventNativeSubmit(assign)}
              >
                <FormControl>
                  <FormLabel>Academic session</FormLabel>
                  <Select
                    value={form.data.academic_session_id}
                    onChange={(e) => {
                      form.setValue(
                        'academic_session_id',
                        Number(e.target.value)
                      );
                      form.setValue('institution_user_id', '');
                    }}
                  >
                    <option value="">Choose a session</option>
                    {academicSessions.map((session) => (
                      <option key={session.id} value={session.id}>
                        {session.title}
                        {session.is_active ? ' (Current)' : ''}
                      </option>
                    ))}
                  </Select>
                </FormControl>
                {targets && (
                  <Text color="gray.600">
                    Holder limit: {targets.holder_limit} · Current count:{' '}
                    {applicableCount}
                    {designation.scope === 'classification'
                      ? selectedPerson
                        ? ` in ${selectedPerson.student?.classification?.title}`
                        : ' in each eligible class'
                      : ' institution-wide'}
                    .
                  </Text>
                )}
                <FormControl isInvalid={!!form.errors.institution_user_id}>
                  <FormLabel>
                    {designation.is_for_students
                      ? 'Eligible student'
                      : 'Eligible staff member'}
                  </FormLabel>
                  <Select
                    value={form.data.institution_user_id}
                    onChange={(e) =>
                      form.setValue('institution_user_id', e.target.value)
                    }
                  >
                    <option value="">Choose a person</option>
                    {targets?.people.map((person) => (
                      <option key={person.id} value={person.id}>
                        {person.user?.full_name}
                        {person.student?.code
                          ? ` · ${person.student.code}`
                          : ''}
                        {person.student?.classification?.title
                          ? ` · ${person.student.classification.title}`
                          : ''}
                      </option>
                    ))}
                  </Select>
                  <FormErrorMessage>
                    {form.errors.institution_user_id}
                  </FormErrorMessage>
                </FormControl>
                <FormControl>
                  <FormLabel>Note (optional)</FormLabel>
                  <Textarea
                    value={form.data.note}
                    onChange={(e) => form.setValue('note', e.target.value)}
                  />
                </FormControl>
                {targetError && <Text color="red.500">{targetError}</Text>}
                <FormButton
                  isLoading={form.processing}
                  title="Assign designation"
                />
              </VStack>
            )}
          </SlabBody>
        </Slab>
        <Slab>
          <SlabHeading title="Assignment history" />
          <SlabBody>
            <Table size="sm">
              <Thead>
                <Tr>
                  <Th>Person</Th>
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
                      {personName(item)}
                      {item.institution_user?.student?.code && (
                        <Text fontSize="xs" color="gray.600">
                          {item.institution_user.student.code}
                        </Text>
                      )}
                    </Td>
                    <Td>{item.classification?.title ?? '—'}</Td>
                    <Td>{item.academic_session?.title}</Td>
                    <Td>{new Date(item.assigned_at).toLocaleDateString()}</Td>
                    <Td>
                      <Badge colorScheme={item.ended_at ? 'gray' : 'green'}>
                        {item.ended_at
                          ? `Ended ${new Date(
                              item.ended_at
                            ).toLocaleDateString()}`
                          : 'Active'}
                      </Badge>
                    </Td>
                    <Td>
                      {!item.ended_at && (
                        <Button
                          size="sm"
                          colorScheme="red"
                          variant="outline"
                          isLoading={endForm.processing}
                          onClick={() => endAssignment(item)}
                        >
                          End assignment
                        </Button>
                      )}
                    </Td>
                  </Tr>
                ))}
              </Tbody>
            </Table>
            {assignments.data.length === 0 && (
              <Text py={4} color="gray.600">
                No assignments have been recorded.
              </Text>
            )}
          </SlabBody>
        </Slab>
      </VStack>
    </DashboardLayout>
  );
}
