// import React, { useState } from 'react';
// import {
//   Button,
//   Checkbox,
//   Divider,
//   HStack,
//   Text,
//   VStack,
// } from '@chakra-ui/react';
// import useWebForm from '@/hooks/use-web-form';
// import GenericModal from '@/components/generic-modal';
// import { TermType } from '@/types/types';
// import useMyToast from '@/hooks/use-my-toast';
// import useInstitutionRoute from '@/hooks/use-institution-route';
// import FormControlBox from '../forms/form-control-box';
// import AcademicSessionSelect from '../selectors/academic-session-select';
// import EnumSelect from '../dropdown-select/enum-select';
// import ClassificationSelect from '../selectors/classification-select';
// import useSharedProps from '@/hooks/use-shared-props';
// import { Assessment } from '@/types/models';
// import { Div } from '../semantic';
// import CourseTeacherSelect from '../selectors/course-teacher-select';
// import { Inertia } from '@inertiajs/inertia';
// import route from '@/util/route';

// interface Props {
//   assessment: Assessment;
// }

// export default function InsertAssessmentScoreFromCourseResultModal({
//   assessment,
// }: Props) {
//   const { handleResponseToast } = useMyToast();
//   const { instRoute } = useInstitutionRoute();
//   const { currentAcademicSessionId, currentTerm } = useSharedProps();

//   const [fromDate, setFromDate] = useState({
//     academic_session_id: currentAcademicSessionId,
//     term: currentTerm,
//     classification_id: '',
//     for_mid_term: false,
//   });

//   const [toDate, setToDate] = useState({
//     academic_session_id: currentAcademicSessionId,
//     term: currentTerm,
//     classification_id: '',
//     for_mid_term: false,
//   });

//   const webForm = useWebForm({
//     course_teacher_id: '',
//   });

//   const onSubmit = async () => {
//     const res = await webForm.submit((data, web) => {
//       return web.post(
//         instRoute('assessments.insert-score-from-course-result', [assessment]),
//         {
//           ...data,
//           from: fromDate,
//           to: toDate,
//         }
//       );
//     });

//     if (!handleResponseToast(res)) return;

//     Inertia.visit(
//       route('course-results.index', {
//         academicSession: toDate.academic_session_id,
//         classification: toDate.classification_id,
//         term: toDate.term,
//         forMidTerm: toDate.for_mid_term,
//       })
//     );
//   };

//   return (
//     <GenericModal
//       props={{ isOpen, onClose }}
//       headerContent={'Evaluate Student Result'}
//       bodyContent={
//         <Div>
//           <Text fontSize={'md'} fontWeight={'semibold'}>
//             Extract scores from
//           </Text>
//           <Divider />
//           <VStack align={'stretch'}>
//             <FormControlBox
//               form={webForm as any}
//               formKey="from.classification_id"
//               title="Class"
//             >
//               <ClassificationSelect
//                 value={fromDate.classification_id}
//                 isMulti={false}
//                 onChange={(e: any) =>
//                   setFromDate({ ...fromDate, classification_id: e.value })
//                 }
//                 required
//               />
//             </FormControlBox>
//             <FormControlBox
//               form={webForm as any}
//               title="Academic Session"
//               formKey="from.academic_session_id"
//             >
//               <AcademicSessionSelect
//                 selectValue={fromDate.academic_session_id}
//                 isMulti={false}
//                 isClearable={true}
//                 onChange={(e: any) =>
//                   setFromDate({ ...fromDate, academic_session_id: e.value })
//                 }
//                 required
//               />
//             </FormControlBox>
//             <FormControlBox
//               form={webForm as any}
//               title="Term"
//               formKey="from.term"
//             >
//               <EnumSelect
//                 enumData={TermType}
//                 selectValue={fromDate.term}
//                 isClearable={true}
//                 onChange={(e: any) =>
//                   setFromDate({ ...fromDate, term: e.value })
//                 }
//                 required
//               />
//             </FormControlBox>
//             <FormControlBox
//               form={webForm as any}
//               formKey="from.for_mid_term"
//               title=""
//             >
//               <Checkbox
//                 isChecked={fromDate.for_mid_term}
//                 onChange={(e) =>
//                   setFromDate({
//                     ...fromDate,
//                     for_mid_term: e.currentTarget.checked,
//                   })
//                 }
//               >
//                 For Mid-Term Result
//               </Checkbox>
//             </FormControlBox>
//           </VStack>
//           <Divider my={5} />
//           <VStack align={'stretch'}>
//             <FormControlBox
//               form={webForm as any}
//               formKey="to.classification_id"
//               title="Class"
//             >
//               <ClassificationSelect
//                 value={toDate.classification_id}
//                 isMulti={false}
//                 onChange={(e: any) =>
//                   setToDate({ ...toDate, classification_id: e.value })
//                 }
//                 required
//               />
//             </FormControlBox>
//             <FormControlBox
//               form={webForm as any}
//               formKey="course_teacher_id"
//               title="Course Teacher"
//             >
//               <CourseTeacherSelect
//                 value={webForm.data.course_teacher_id}
//                 isMulti={false}
//                 onChange={(e: any) =>
//                   webForm.setValue('course_teacher_id', e.value)
//                 }
//                 required
//               />
//             </FormControlBox>
//             <FormControlBox
//               form={webForm as any}
//               title="Academic Session"
//               formKey="to.academic_session_id"
//             >
//               <AcademicSessionSelect
//                 selectValue={toDate.academic_session_id}
//                 isMulti={false}
//                 isClearable={true}
//                 onChange={(e: any) =>
//                   setToDate({ ...toDate, academic_session_id: e.value })
//                 }
//                 required
//               />
//             </FormControlBox>
//             <FormControlBox
//               form={webForm as any}
//               title="Term"
//               formKey="to.term"
//             >
//               <EnumSelect
//                 enumData={TermType}
//                 selectValue={toDate.term}
//                 isClearable={true}
//                 onChange={(e: any) => setToDate({ ...toDate, term: e.value })}
//                 required
//               />
//             </FormControlBox>
//             <FormControlBox
//               form={webForm as any}
//               formKey="to.for_mid_term"
//               title=""
//             >
//               <Checkbox
//                 isChecked={toDate.for_mid_term}
//                 onChange={(e) =>
//                   setToDate({
//                     ...toDate,
//                     for_mid_term: e.currentTarget.checked,
//                   })
//                 }
//               >
//                 For Mid-Term Result
//               </Checkbox>
//             </FormControlBox>
//           </VStack>
//         </Div>
//       }
//       footerContent={
//         <HStack spacing={2}>
//           <Button variant={'ghost'} onClick={onClose}>
//             Close
//           </Button>
//           <Button
//             colorScheme={'brand'}
//             onClick={onSubmit}
//             isLoading={webForm.processing}
//           >
//             Submit
//           </Button>
//         </HStack>
//       }
//     />
//   );
// }
