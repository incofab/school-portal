import React from 'react';
import { Heading, Text } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { BrandButton } from '@/components/buttons';
import useIsStudent from '@/hooks/use-is-student';
import DOMPurify from 'dompurify';
import { Inertia } from '@inertiajs/inertia';
import { AssignmentSubmission } from './../../../types/models';
import useModalToggle from '@/hooks/use-modal-toggle';
import AssignmentScoreModal from '@/components/modals/assignment-score-modal';

interface Props {
  assignmentSubmission: AssignmentSubmission;
}

export default function ShowAssignment({ assignmentSubmission }: Props) {
  const isStudent = useIsStudent();
  const assignmentScoreModalToggle = useModalToggle();

  const sanitizedQuestion = DOMPurify.sanitize(
    assignmentSubmission.assignment?.content
  );

  const sanitizedAnswer = DOMPurify.sanitize(assignmentSubmission.answer);

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading
          title={'Submitted Answer'}
          rightElement={
            !isStudent && (
              <BrandButton
                title="Score"
                onClick={assignmentScoreModalToggle.open}
              />
            )
          }
        />

        <SlabBody>
          <div
            style={{ marginBottom: '30px' }}
            dangerouslySetInnerHTML={{ __html: sanitizedAnswer }}
          />

          {assignmentSubmission.score !== null ? (
            <div style={{ marginBottom: '30px' }}>
              <Heading size={'md'} fontWeight={'medium'}>
                Score Card ::
              </Heading>

              <Text mt={2}>
                Score :: {assignmentSubmission?.score}/
                {assignmentSubmission?.assignment.max_score}
              </Text>

              <Text mt={1}>
                Teacher's Remark :: {assignmentSubmission?.remark}
              </Text>
            </div>
          ) : (
            ''
          )}

          <Heading size={'md'} fontWeight={'medium'}>
            The Question ::
          </Heading>

          <div
            style={{ marginTop: '10px', marginBottom: '30px' }}
            dangerouslySetInnerHTML={{ __html: sanitizedQuestion }}
          />
        </SlabBody>
      </Slab>

      <AssignmentScoreModal
        assignmentSubmission={assignmentSubmission}
        {...assignmentScoreModalToggle.props}
        onSuccess={() => Inertia.reload()}
      />
    </DashboardLayout>
  );
}
