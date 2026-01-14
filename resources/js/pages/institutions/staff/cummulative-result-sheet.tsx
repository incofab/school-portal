import {
  AcademicSession,
  Classification,
  Course,
  CourseResult,
  Student,
  TermResult,
} from '@/types/models';
import {
  FormControl,
  HStack,
  Text,
  VStack,
  Wrap,
  WrapItem,
} from '@chakra-ui/react';
import React, { useMemo } from 'react';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { Div } from '@/components/semantic';
import useSharedProps from '@/hooks/use-shared-props';
import '@/../../public/style/result-sheet.css';
import { SelectOptionType, TermType } from '@/types/types';
import ResultUtil from '@/util/result-util';
import { PageTitle } from '@/components/page-header';
import FormControlBox from '@/components/forms/form-control-box';
import useWebForm from '@/hooks/use-web-form';
import ClassificationSelect from '@/components/selectors/classification-select';
import AcademicSessionSelect from '@/components/selectors/academic-session-select';
import { preventNativeSubmit, roundNumber } from '@/util/util';
import { Inertia } from '@inertiajs/inertia';
import { FormButton } from '@/components/buttons';
import EnumSelect from '@/components/dropdown-select/enum-select';
import useDownloadHtml from '@/util/download-html';

interface HasTermDataProp {
  hasFirstTermRecords: boolean;
  hasSecondTermRecords: boolean;
  hasThirdTermRecords: boolean;
}

interface SessionResultLog {
  student: Student;
  firstTermResult: TermResult;
  secondTermResult: TermResult;
  thirdTermResult: TermResult;
  firstTermCourseResult: { [courseId: number]: CourseResult };
  secondTermCourseResult: { [courseId: number]: CourseResult };
  thirdTermCourseResult: { [courseId: number]: CourseResult };
}

interface Props {
  sessionResults: SessionResultLog[];
  courses: {
    firstTermCourses: Course[];
    secondTermCourses: Course[];
    thirdTermCourses: Course[];
  };
  classification?: Classification;
  academicSession?: AcademicSession;
  term?: TermType;
}

export default function CummulativeResultSheet({
  sessionResults,
  courses,
  classification,
  academicSession,
  term,
}: Props) {
  const { currentInstitution } = useSharedProps();
  const { DownloadButton } = useDownloadHtml();

  function VerticalText({ text }: { text: string }) {
    return <Text className="vertical-header">{text}</Text>;
  }
  function getStudentColumnData(
    sessionResultLog: SessionResultLog,
    hasTermData: HasTermDataProp
  ) {
    const termData1 = getTermColumnData(TermType.First, sessionResultLog);
    const termData2 = getTermColumnData(TermType.Second, sessionResultLog);
    const termData3 = getTermColumnData(TermType.Third, sessionResultLog);

    if (termData1.length > 0) {
      hasTermData.hasFirstTermRecords = true;
    }
    if (termData2.length > 0) {
      hasTermData.hasSecondTermRecords = true;
    }
    if (termData3.length > 0) {
      hasTermData.hasThirdTermRecords = true;
    }
    return [
      { label: 'Name', value: sessionResultLog.student.user!.full_name },
      ...termData1,
      ...termData2,
      ...termData3,
    ];
  }

  function getTermColumnData(term: string, sessionResultLog: SessionResultLog) {
    // @ts-ignore
    const courseResult = sessionResultLog[term + 'TermCourseResult'] as {
      [courseId: number]: CourseResult;
    };
    // @ts-ignore
    const termResult = sessionResultLog[term + 'TermResult'] as TermResult;
    // @ts-ignore
    const termCourses = courses[term + 'TermCourses'] as Course[];
    // console.log('Term courses', termCourses);

    if (!termCourses || !termResult) return [];

    return [
      ...termCourses.map((course) => ({
        label: course.title,
        value: courseResult[course.id]?.result,
      })),
      { label: 'Total', value: termResult.total_score },
      { label: 'Average', value: roundNumber(termResult.average, 2) },
      {
        label: 'Position',
        value:
          termResult.position +
          ResultUtil.getPositionSuffix(termResult.position),
      },
    ];
  }

  const resultData: {
    result: SelectOptionType<string | number>[][];
    hasTermData: HasTermDataProp;
  } = useMemo(() => {
    const hasTermData = {} as HasTermDataProp;
    if (!sessionResults) {
      return {
        result: [],
        hasTermData: hasTermData,
      };
    }
    const formated: SelectOptionType<string | number>[][] = sessionResults.map(
      (sessionResultLog) => {
        return getStudentColumnData(sessionResultLog, hasTermData);
      }
    );
    return { result: formated, hasTermData: hasTermData };
  }, []);

  const svgCode = `<svg xmlns='http://www.w3.org/2000/svg' width='140' height='100' opacity='0.08' viewBox='0 0 100 100' transform='rotate(45)'><text x='0' y='50' font-size='18' fill='%23000'>${currentInstitution.name}</text></svg>`;
  const backgroundStyle = {
    backgroundImage: `url("data:image/svg+xml;charset=utf-8,${encodeURIComponent(
      svgCode
    )}")`,
    backgroundRepeat: 'repeat',
    backgroundColor: 'white',
  };

  function hasResultData() {
    if (resultData.result.length === 0) {
      return false;
    }
    return (
      resultData.hasTermData.hasFirstTermRecords ||
      resultData.hasTermData.hasSecondTermRecords ||
      resultData.hasTermData.hasThirdTermRecords
    );
  }

  return (
    <Div style={backgroundStyle} minHeight={'1170px'}>
      <Div mx={'auto'} px={3} py={2}>
        <VStack align={'stretch'}>
          <PageTitle textAlign={'center'}>{currentInstitution.name}</PageTitle>
          <Text textAlign={'center'}>Cummulative result sheet</Text>
          <ClassAndSessionSelector
            academicSession={academicSession}
            classification={classification}
            term={term}
          />
          {hasResultData() && (
            <div className="table-container">
              <DownloadButton
                filename={`cummulative-result-sheet-c${classification?.id}-a${academicSession?.id}-t${term}`}
                title="Download"
                mb={3}
              />
              <table className="result-table" width={'100%'}>
                <thead>
                  <tr>
                    <th></th>
                    {resultData.hasTermData.hasFirstTermRecords &&
                      courses.firstTermCourses.length > 0 && (
                        <th colSpan={courses.firstTermCourses.length + 3}>
                          <Text>First Term</Text>
                        </th>
                      )}
                    {resultData.hasTermData.hasSecondTermRecords &&
                      courses.secondTermCourses.length > 0 && (
                        <th colSpan={courses.secondTermCourses.length + 3}>
                          <Text>Second Term</Text>
                        </th>
                      )}
                    {resultData.hasTermData.hasThirdTermRecords &&
                      courses.thirdTermCourses && (
                        <th colSpan={courses.thirdTermCourses.length + 3}>
                          <Text>Third Term</Text>
                        </th>
                      )}
                  </tr>
                  <tr>
                    {resultData.result[0].map((item, i) => (
                      <th key={'header-' + i + item.label}>
                        {item.label === 'Name' ? (
                          <>{item.label}</>
                        ) : (
                          <VerticalText text={item.label} />
                        )}
                      </th>
                    ))}
                  </tr>
                </thead>
                <tbody>
                  {resultData.result.map((result, i) => (
                    <tr key={'t-row-' + i}>
                      {result.map((item, j) => (
                        <td key={`row-item-${i}-${j}-${item.label}`}>
                          {item.value}
                        </td>
                      ))}
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </VStack>
      </Div>
    </Div>
  );
}

function ClassAndSessionSelector({
  classification,
  academicSession,
  term,
}: {
  classification?: Classification;
  academicSession?: AcademicSession;
  term?: string;
}) {
  const { instRoute } = useInstitutionRoute();
  const webForm = useWebForm({
    term: term ?? 'all',
    academicSession: academicSession?.id ?? '',
    classification: classification?.id ?? '',
  });

  const submit = () => {
    Inertia.visit(
      instRoute('cummulative-result.index', {
        classification: webForm.data.classification,
        academicSession: webForm.data.academicSession,
        term: webForm.data.term === 'all' ? '' : webForm.data.term,
      })
    );
  };

  const termTypes: { [key: string]: string } = {
    All: 'all',
  };

  Object.entries(TermType).map(([key, val]) => {
    termTypes[key] = val;
  });
  const minWidth = '150px';
  return (
    <Wrap
      align={'end'}
      as={'form'}
      spacing={2}
      onSubmit={preventNativeSubmit(submit)}
    >
      <WrapItem minW={minWidth}>
        <FormControlBox
          form={webForm as any}
          formKey={'classification'}
          title="Class"
          isRequired
        >
          <ClassificationSelect
            selectValue={webForm.data.classification}
            onChange={(e: any) => webForm.setValue('classification', e.value)}
            required
          />
        </FormControlBox>
      </WrapItem>
      <WrapItem minW={minWidth}>
        <FormControlBox
          form={webForm as any}
          formKey={'academicSession'}
          title="Academic Session"
          isRequired
        >
          <AcademicSessionSelect
            selectValue={webForm.data.academicSession}
            onChange={(e: any) => webForm.setValue('academicSession', e.value)}
            required
          />
        </FormControlBox>
      </WrapItem>
      <WrapItem minW={minWidth}>
        <FormControlBox form={webForm as any} formKey={'term'} title="Term">
          <EnumSelect
            enumData={termTypes}
            selectValue={webForm.data.term}
            onChange={(e: any) => webForm.setValue('term', e.value)}
          />
        </FormControlBox>
      </WrapItem>
      <WrapItem>
        <FormControl>
          <FormButton
            isLoading={webForm.processing}
            marginTop={'35px'}
            variant={'outline'}
            className="hidden-on-print"
          />
        </FormControl>
      </WrapItem>
    </Wrap>
  );
}
