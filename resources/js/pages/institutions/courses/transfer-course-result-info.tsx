import React from 'react';
import {
  Box,
  Button,
  Checkbox,
  Divider,
  HStack,
  Spacer,
  Text,
  VStack,
} from '@chakra-ui/react';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import DashboardLayout from '@/layout/dashboard-layout';
import { Assessment, CourseResultInfo } from '@/types/models';
import { TermType } from '@/types/types';
import startCase from 'lodash/startCase';
import useWebForm from '@/hooks/use-web-form';
import useInstitutionRoute from '@/hooks/use-institution-route';
import useMyToast from '@/hooks/use-my-toast';
import FormControlBox from '@/components/forms/form-control-box';
import useSharedProps from '@/hooks/use-shared-props';
import MySelect from '@/components/dropdown-select/my-select';
import { Inertia } from '@inertiajs/inertia';

interface Props {
  courseResultInfo: CourseResultInfo;
  sourceAssessments: Assessment[];
  assessments: Assessment[];
  targetTerm?: string;
  targetForMidTerm?: boolean;
}

function filterAssessments(
  assessments: Assessment[],
  term: string,
  forMidTerm: boolean,
  classificationId: number
) {
  return assessments.filter((assessment) => {
    const matchesTerm = assessment.term === null || assessment.term === term;
    const matchesMidTerm =
      assessment.for_mid_term === null ||
      assessment.for_mid_term === forMidTerm;
    const matchesClass =
      !assessment.classifications?.length ||
      assessment.classifications.some(
        (classification) => classification.id === classificationId
      );
    return matchesTerm && matchesMidTerm && matchesClass;
  });
}

export default function TransferCourseResultInfo({
  courseResultInfo,
  sourceAssessments,
  assessments,
  targetTerm,
  targetForMidTerm,
}: Props) {
  const { instRoute } = useInstitutionRoute();
  const { handleResponseToast } = useMyToast();
  const { usesMidTermResult } = useSharedProps();

  const initialTerm = targetTerm ?? courseResultInfo.term;
  const initialForMidTerm = targetForMidTerm ?? false;

  const buildAssessmentMap = (
    term: string,
    forMidTerm: boolean
  ): Record<string, (string | number)[]> => {
    const targetAssessments = filterAssessments(
      assessments,
      term,
      forMidTerm,
      courseResultInfo.classification_id
    );
    const targetIds = [
      ...targetAssessments.map((assessment) => assessment.id),
      'exam',
    ];
    const mapping: Record<string, (string | number)[]> = {};
    targetIds.forEach((id) => {
      mapping[String(id)] = [];
    });
    return mapping;
  };

  const webForm = useWebForm({
    term: initialTerm,
    for_mid_term: usesMidTermResult && initialForMidTerm,
    assessment_map: buildAssessmentMap(initialTerm, initialForMidTerm),
  });

  const targetAssessments = filterAssessments(
    assessments,
    webForm.data.term,
    webForm.data.for_mid_term,
    courseResultInfo.classification_id
  );
  const targetAssessmentOptions = targetAssessments.map((target) => ({
    label: `${target.title} (Max ${target.max})`,
    value: target.id,
  }));
  const sourceOptions = [
    ...sourceAssessments.map((assessment) => ({
      label: `${assessment.title} (Max ${assessment.max})`,
      value: assessment.id,
    })),
    { label: 'Exam', value: 'exam' },
  ];
  const termOptions = Object.values(TermType).map((term) => ({
    label: startCase(term),
    value: term,
  }));
  const midTermOptions = [
    { label: 'Term Result', value: false },
    { label: 'Mid-Term Result', value: true },
  ];

  const onSubmit = async () => {
    const res = await webForm.submit((data, web) =>
      web.post(
        instRoute('course-result-info.transfer.store', [courseResultInfo.id]),
        data
      )
    );
    if (!handleResponseToast(res)) return;
    Inertia.visit(instRoute('course-result-info.index'));
  };

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading title="Transfer Course Result" />
        <SlabBody>
          <VStack align="stretch" spacing={4}>
            <Box
              borderWidth="1px"
              borderColor="gray.200"
              borderRadius="lg"
              p={4}
              bg="white"
            >
              <VStack align="stretch" spacing={1}>
                <Text fontWeight="bold" fontSize="lg">
                  {courseResultInfo.course?.title}
                </Text>
                <Text fontSize="sm" color="gray.600">
                  {courseResultInfo.classification?.title} •{' '}
                  {courseResultInfo.academic_session?.title}
                </Text>
                <Text fontSize="sm" color="gray.600">
                  Current: {startCase(courseResultInfo.term)}{' '}
                  {courseResultInfo.for_mid_term ? 'Mid-' : ''}Term
                </Text>
              </VStack>
              <Divider my={4} />
              {/* <HStack spacing={4} align="flex-start"> */}
              <FormControlBox
                form={webForm as any}
                title="Target Term"
                formKey="term"
              >
                <MySelect
                  selectValue={webForm.data.term}
                  isMulti={false}
                  getOptions={() => termOptions}
                  onChange={(e: any) =>
                    webForm.setData({
                      ...webForm.data,
                      term: e?.value,
                      assessment_map: buildAssessmentMap(
                        e?.value,
                        webForm.data.for_mid_term
                      ),
                    })
                  }
                />
              </FormControlBox>
              {usesMidTermResult && (
                <>
                  <Spacer height={5} />
                  <FormControlBox
                    form={webForm as any}
                    formKey="for_mid_term"
                    title=""
                  >
                    <Checkbox
                      isChecked={webForm.data.for_mid_term}
                      onChange={(e) => {
                        const isChecked = e.currentTarget.checked;
                        // webForm.setValue('for_mid_term', e.currentTarget.checked)
                        webForm.setData({
                          ...webForm.data,
                          for_mid_term: isChecked,
                          assessment_map: buildAssessmentMap(
                            webForm.data.term,
                            isChecked
                          ),
                        });
                      }}
                    >
                      For Mid-Term Result
                    </Checkbox>
                  </FormControlBox>
                  {/* <FormControlBox
                      form={webForm as any}
                      title="Target Result Type"
                      formKey="for_mid_term"
                    >
                      <MySelect
                        selectValue={webForm.data.for_mid_term}
                        isMulti={false}
                        getOptions={() => midTermOptions}
                        onChange={(e: any) =>
                          webForm.setData({
                            ...webForm.data,
                            for_mid_term: e?.value,
                            assessment_map: buildAssessmentMap(
                              webForm.data.term,
                              e?.value
                            ),
                          })
                        }
                      />
                    </FormControlBox> */}
                </>
              )}
              {/* </HStack> */}
            </Box>

            <Box
              borderWidth="1px"
              borderColor="gray.200"
              borderRadius="lg"
              p={4}
              bg="white"
            >
              <VStack align="stretch" spacing={2}>
                <Text fontWeight="semibold">Assessment Mapping</Text>
                <Text fontSize="sm" color="gray.600">
                  You can map multiple source assessments to a single target
                  assessment. Mapped scores will be summed.
                </Text>
                <Divider />
                {[
                  ...targetAssessmentOptions,
                  { label: 'Exam', value: 'exam' },
                ].map((target) => (
                  <HStack key={target.value} spacing={3}>
                    <Box flex="1">
                      <Text fontWeight="medium">{target.label}</Text>
                      <Text fontSize="xs" color="gray.500">
                        Target {target.value === 'exam' ? 'Exam' : 'Assessment'}
                      </Text>
                    </Box>
                    <Box flex="1">
                      <MySelect
                        isMulti={true}
                        selectValue={
                          webForm.data.assessment_map[String(target.value)] ??
                          []
                        }
                        getOptions={() => sourceOptions}
                        onChange={(values: any) =>
                          webForm.setValue('assessment_map', {
                            ...webForm.data.assessment_map,
                            [String(target.value)]: (values ?? []).map(
                              (item: any) => item.value
                            ),
                          })
                        }
                      />
                    </Box>
                  </HStack>
                ))}
              </VStack>
            </Box>

            <HStack justifyContent="flex-end" spacing={2}>
              <Button
                variant="ghost"
                onClick={() =>
                  Inertia.visit(instRoute('course-result-info.index'))
                }
              >
                Cancel
              </Button>
              <Button
                colorScheme="brand"
                onClick={onSubmit}
                isLoading={webForm.processing}
              >
                Transfer Results
              </Button>
            </HStack>
          </VStack>
        </SlabBody>
      </Slab>
    </DashboardLayout>
  );
}
