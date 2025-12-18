import {
  AcademicSession,
  Classification,
  TermResult,
  LearningEvaluation,
  ResultCommentTemplate,
} from '@/types/models';
import React, { useState } from 'react';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import DashboardLayout from '@/layout/dashboard-layout';
import { Div } from '@/components/semantic';
import {
  Divider,
  HStack,
  Icon,
  IconButton,
  Spacer,
  Stack,
  Text,
  useColorModeValue,
  VStack,
} from '@chakra-ui/react';
import { BrandButton } from '@/components/buttons';
import useModalToggle from '@/hooks/use-modal-toggle';
import TermResultTeacherCommentModal from '@/components/modals/term-result-teacher-comment-modal';
import { Inertia } from '@inertiajs/inertia';
import TermResultPrincipalCommentModal from '@/components/modals/term-result-principal-comment-modal';
import SetTermResultEvaluation from '../learning-evaluations/set-term-result-evaluations-component';
import {
  ArrowLeftIcon,
  ArrowRightIcon,
  PencilIcon,
} from '@heroicons/react/24/outline';
import { LabelText } from '@/components/result-helper-components';
import { TermType } from '@/types/types';
import { roundNumber, ucFirst } from '@/util/util';
import { TermResultExtraData } from '../learning-evaluations/term-result-extra-data';
import ResultUtil from '@/util/result-util';

interface Props {
  classification: Classification;
  termResults: TermResult[];
  learningEvaluations?: LearningEvaluation[];
  academicSession: AcademicSession;
  term: TermType;
  forMidTerm?: boolean;
  resultCommentTemplate: ResultCommentTemplate[];
}

export default function RecordClassStudentsEvaluations({
  classification,
  termResults,
  learningEvaluations,
  academicSession,
  term,
  forMidTerm,
  resultCommentTemplate,
}: Props) {
  const [index, setIndex] = useState(0);
  const teacherCommentModalToggle = useModalToggle();
  const principalCommentModalToggle = useModalToggle();
  const termResult = termResults[index];

  const principalComment =
    termResult.principal_comment ??
    ResultUtil.getCommentFromTemplate(termResult.average, resultCommentTemplate)
      ?.comment;
  const teacherComment =
    termResult.teacher_comment ??
    ResultUtil.getCommentFromTemplate(termResult.average, resultCommentTemplate)
      ?.comment_2;

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading title="Update Class Students Evaluations" />
        <SlabBody>
          <HStack justifyContent={'space-between'}>
            <BrandButton
              title="Previous"
              leftIcon={<Icon as={ArrowLeftIcon} />}
              variant={'outline'}
              onClick={() => {
                if (index > 0) {
                  setIndex(index - 1);
                }
              }}
              disabled={index === 0}
            />
            <Spacer />
            <Div>
              {index + 1} of {termResults.length}
            </Div>
            <Spacer />
            <BrandButton
              title="Next"
              variant={'outline'}
              rightIcon={<Icon as={ArrowRightIcon} />}
              onClick={() => {
                if (index < termResults.length - 1) {
                  setIndex(index + 1);
                }
              }}
              disabled={index === termResults.length - 1}
            />
          </HStack>
        </SlabBody>
        {termResult ? (
          <>
            <VStack mb={3} spacing={2} align={'stretch'}>
              {[
                { label: 'Name', value: termResult.student!.user?.full_name },
                {
                  label: 'Portal Id',
                  value: termResult.student!.code,
                },
                { label: 'Class', value: classification.title },
                {
                  label: 'Term',
                  value: `${ucFirst(term)} ${forMidTerm ? 'Mid-' : ''}Term`,
                },
                { label: 'Session', value: academicSession.title },
                { label: 'Position', value: termResult.position },
                { label: 'Average', value: roundNumber(termResult.average) },
              ].map((item) => (
                <LabelText
                  label={item.label}
                  text={item.value}
                  key={item.label}
                  labelProps={{ fontWeight: 'semibold', minWidth: '80px' }}
                />
              ))}
            </VStack>
            <Stack direction={{ base: 'column', md: 'row' }} spacing={3}>
              <Div
                maxWidth={'500px'}
                background={useColorModeValue('#FAFAFA', 'gray.700')}
                py={4}
                px={5}
                flex={1}
              >
                <SetTermResultEvaluation
                  termResult={termResult}
                  learningEvaluations={learningEvaluations}
                />
              </Div>
              <Div
                flex={1}
                background={useColorModeValue('#FAFAFA', 'gray.700')}
                p={4}
              >
                <TermResultExtraData termResult={termResult} />
                <Divider height={20} />
                <VStack divider={<Divider />} spacing={4} align={'stretch'}>
                  <>
                    <Text fontWeight={'semibold'} size={'sm'}>
                      Teacher's Comment
                    </Text>
                    <HStack align={'stretch'}>
                      <Text>{teacherComment}</Text>
                      <Spacer />
                      <IconButton
                        aria-label="edit teacher's comment"
                        icon={<Icon as={PencilIcon} />}
                        variant={'outline'}
                        onClick={teacherCommentModalToggle.open}
                      />
                    </HStack>
                  </>
                  <>
                    <Text fontWeight={'semibold'} size={'sm'}>
                      Principal/Head Teacher's Comment
                    </Text>
                    <HStack align={'stretch'}>
                      <Text>{principalComment}</Text>
                      <Spacer />
                      <IconButton
                        aria-label="edit Administrator's comment"
                        icon={<Icon as={PencilIcon} />}
                        variant={'outline'}
                        onClick={principalCommentModalToggle.open}
                      />
                    </HStack>
                  </>
                </VStack>
              </Div>
            </Stack>
            <TermResultTeacherCommentModal
              termResult={termResult}
              templateComment={teacherComment}
              {...teacherCommentModalToggle.props}
              onSuccess={() => Inertia.reload({ only: ['termResult'] })}
            />
            <TermResultPrincipalCommentModal
              termResult={termResult}
              templateComment={principalComment}
              {...principalCommentModalToggle.props}
              onSuccess={() => Inertia.reload({ only: ['termResult'] })}
            />
          </>
        ) : (
          <Text>
            No term result found <br />
            You need to evaluate the term results for this class first
          </Text>
        )}
      </Slab>
    </DashboardLayout>
  );
}
