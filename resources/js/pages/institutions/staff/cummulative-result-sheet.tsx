import {
  AcademicSession,
  Classification,
  Course,
  CourseResult,
  Student,
  TermResult,
} from '@/types/models';
import {
  Avatar,
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
import FormControlBox from '@/components/forms/form-control-box';
import useWebForm from '@/hooks/use-web-form';
import ClassificationSelect from '@/components/selectors/classification-select';
import AcademicSessionSelect from '@/components/selectors/academic-session-select';
import { preventNativeSubmit, roundNumber, ucFirst } from '@/util/util';
import { Inertia } from '@inertiajs/inertia';
import { FormButton } from '@/components/buttons';
import EnumSelect from '@/components/dropdown-select/enum-select';
import useDownloadHtml from '@/util/download-html';
import ImagePaths from '@/util/images';

interface SessionResultLog {
  student: Student;
  termResults: Partial<Record<TermType, TermResult | null>>;
  courseResults: Partial<Record<TermType, Record<number, CourseResult>>>;
}

interface Props {
  sessionResults: SessionResultLog[];
  terms?: TermType[];
  coursesByTerm?: Partial<Record<TermType, Course[]>>;
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
  terms,
  coursesByTerm,
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

  function termTitle(term: TermType) {
    return `${ucFirst(term)} Term`;
  }

  const normalizedCoursesByTerm: Record<TermType, Course[]> = useMemo(
    () => ({
      [TermType.First]:
        coursesByTerm?.[TermType.First] ?? courses?.firstTermCourses ?? [],
      [TermType.Second]:
        coursesByTerm?.[TermType.Second] ?? courses?.secondTermCourses ?? [],
      [TermType.Third]:
        coursesByTerm?.[TermType.Third] ?? courses?.thirdTermCourses ?? [],
    }),
    [coursesByTerm, courses]
  );

  const selectedTerms = useMemo(
    () =>
      terms?.length
        ? terms
        : ([TermType.First, TermType.Second, TermType.Third] as TermType[]),
    [terms]
  );

  const displayedTerms = useMemo(
    () =>
      selectedTerms.filter((term) => {
        if (normalizedCoursesByTerm[term]?.length > 0) {
          return true;
        }

        return (sessionResults ?? []).some(
          (sessionResult) => sessionResult.termResults?.[term]
        );
      }),
    [selectedTerms, normalizedCoursesByTerm, sessionResults]
  );

  function getTermColumnData(
    term: TermType,
    sessionResultLog: SessionResultLog
  ): SelectOptionType<string | number>[] {
    const courseResults = sessionResultLog.courseResults?.[term] ?? {};
    const termResult = sessionResultLog.termResults?.[term] ?? null;
    const termCourses = normalizedCoursesByTerm[term] ?? [];

    return [
      ...termCourses.map((course) => ({
        label: course.title,
        value: courseResults[course.id]?.result ?? '',
      })),
      { label: 'Total', value: termResult?.total_score ?? '' },
      {
        label: 'Average',
        value:
          termResult?.average === undefined || termResult?.average === null
            ? ''
            : roundNumber(termResult.average, 2),
      },
      {
        label: 'Position',
        value:
          termResult?.position === undefined || termResult?.position === null
            ? ''
            : termResult.position +
              ResultUtil.getPositionSuffix(termResult.position),
      },
    ];
  }

  const resultData: SelectOptionType<string | number>[][] = useMemo(() => {
    if (!sessionResults || displayedTerms.length === 0) {
      return [];
    }

    return sessionResults.map((sessionResultLog) => [
      { label: 'Name', value: sessionResultLog.student.user!.full_name },
      ...displayedTerms.flatMap((term) =>
        getTermColumnData(term, sessionResultLog)
      ),
    ]);
  }, [sessionResults, displayedTerms, normalizedCoursesByTerm]);

  const svgCode = `<svg xmlns='http://www.w3.org/2000/svg' width='140' height='100' opacity='0.08' viewBox='0 0 100 100' transform='rotate(45)'><text x='0' y='50' font-size='18' fill='%23000'>${currentInstitution.name}</text></svg>`;
  const backgroundStyle = {
    backgroundImage: `url("data:image/svg+xml;charset=utf-8,${encodeURIComponent(
      svgCode
    )}")`,
    backgroundRepeat: 'repeat',
    backgroundColor: 'white',
  };

  function hasResultData() {
    return resultData.length > 0 && displayedTerms.length > 0;
  }
  const canShow = Boolean(classification) && Boolean(academicSession);

  return (
    <Div style={backgroundStyle} minHeight={'1170px'}>
      <Div mx={'auto'} px={3} py={2}>
        <VStack align={'stretch'}>
          <Div className="result-sheet-header">
            <HStack background={'#FAFAFA'} p={2}>
              <Avatar
                size={'2xl'}
                name="Institution logo"
                src={currentInstitution.photo ?? ImagePaths.default_school_logo}
              />
              <VStack spacing={1} align={'stretch'} width={'full'}>
                <Text fontSize={'2xl'} fontWeight={'bold'} textAlign={'center'}>
                  {currentInstitution.name}
                </Text>
                <Text
                  textAlign={'center'}
                  fontSize={'18px'}
                  whiteSpace={'nowrap'}
                >
                  {currentInstitution.address}
                  <br /> {currentInstitution.email}
                </Text>
                <Text
                  fontWeight={'semibold'}
                  textAlign={'center'}
                  fontSize={'18px'}
                >
                  {canShow ? (
                    <>
                      {`${classification!.title} - ${academicSession!.title} `}
                      {term ? `- ${ucFirst(term)} Term ` : ''}
                    </>
                  ) : (
                    ''
                  )}
                  Cummulative Result
                </Text>
              </VStack>
            </HStack>
          </Div>

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
                    {displayedTerms.map((term) => (
                      <th
                        key={`term-header-${term}`}
                        colSpan={normalizedCoursesByTerm[term].length + 3}
                      >
                        <Text>{termTitle(term)}</Text>
                      </th>
                    ))}
                  </tr>
                  <tr>
                    {resultData[0].map((item, i) => (
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
                  {resultData.map((result, i) => (
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
      justify={'center'}
      className="hidden-on-print"
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
