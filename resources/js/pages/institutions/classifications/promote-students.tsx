import React from 'react';
import {
  Alert,
  AlertDescription,
  AlertIcon,
  FormControl,
  FormLabel,
  HStack,
  Icon,
  IconButton,
  Input,
  VStack,
} from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import useWebForm from '@/hooks/use-web-form';
import { preventNativeSubmit } from '@/util/util';
import { Inertia } from '@inertiajs/inertia';
import {
  AcademicSession,
  Classification,
  ClassificationGroup,
  SessionResult,
} from '@/types/models';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import CenteredBox from '@/components/centered-box';
import { BrandButton, FormButton } from '@/components/buttons';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import ClassificationSelect from '@/components/selectors/classification-select';
import { Div } from '@/components/semantic';
import { PlusIcon, XMarkIcon } from '@heroicons/react/24/solid';
import useSharedProps from '@/hooks/use-shared-props';
import { LabelText } from '@/components/result-helper-components';
import DataTable, { TableHeader } from '@/components/data-table';

interface Promotion {
  destination_classification_id: number | string;
  from: number | string;
  to: number | string;
}
const emptyPromotion: Promotion = {
  destination_classification_id: '',
  from: '',
  to: '',
};
interface Props {
  classifications: Classification[];
  classificationGroup: ClassificationGroup;
  sessionResults: SessionResult[];
}

export default function PromoteStudents({
  classifications,
  classificationGroup,
  sessionResults,
}: Props) {
  const { handleResponseToast } = useMyToast();
  const { currentAcademicSession } = useSharedProps();
  const { instRoute } = useInstitutionRoute();
  const webForm = useWebForm({
    promotions: [emptyPromotion] as Promotion[],
  });

  const submit = async () => {
    const res = await webForm.submit((data, web) => {
      return web.post(
        instRoute('classification-groups.promote-students.store', [
          classificationGroup,
        ]),
        data
      );
    });
    if (!handleResponseToast(res)) {
      return;
    }
    Inertia.visit(instRoute('student-class-movements.index'));
  };
  const displayData = [
    { label: 'Session', value: currentAcademicSession.title },
    { label: 'Class Group', value: classificationGroup.title },
  ];

  return (
    <DashboardLayout>
      {!sessionResults || sessionResults.length < 1 ? (
        <>
          <Alert status="error">
            <AlertIcon />
            <AlertDescription>
              There are no session results for the current session (
              <b>{currentAcademicSession.title}</b>) in{' '}
              <b>{classificationGroup.title}</b> Class group
            </AlertDescription>
          </Alert>
        </>
      ) : (
        <>
          <CenteredBox>
            <Slab>
              <SlabHeading title={`Promote Students`} />
              <Alert status="info">
                <AlertIcon />
                <AlertDescription>
                  Students in <b>{classificationGroup.title}</b> Class will be
                  promoted based on their session result from{' '}
                  <b>{currentAcademicSession.title}</b> Session
                </AlertDescription>
              </Alert>
              <VStack align={'stretch'}>
                {displayData.map(({ label, value }) => (
                  <LabelText label={label} text={value} key={label} />
                ))}
              </VStack>
              <SlabBody>
                <VStack
                  spacing={4}
                  as={'form'}
                  onSubmit={preventNativeSubmit(submit)}
                  align={'stretch'}
                >
                  <BrandButton
                    alignSelf={'end'}
                    title="More"
                    leftIcon={<Icon as={PlusIcon} />}
                    type="button"
                    onClick={() =>
                      webForm.setValue('promotions', [
                        ...webForm.data.promotions,
                        emptyPromotion,
                      ])
                    }
                  />
                  {webForm.data.promotions.map((promotion, index) => (
                    <React.Fragment
                      key={`${promotion.destination_classification_id}${index}`}
                    >
                      <PromotionEntry
                        classifications={classifications}
                        index={index}
                        promotion={promotion}
                        onPromotionUpdated={(p, i) => {
                          const promotions = webForm.data.promotions;
                          promotions[i] = p;
                          webForm.setValue('promotions', promotions);
                        }}
                        onRemove={(index) =>
                          webForm.setValue(
                            'promotions',
                            webForm.data.promotions.filter(
                              (item, i) => i !== index
                            )
                          )
                        }
                      />
                    </React.Fragment>
                  ))}
                  <FormControl>
                    <FormButton isLoading={webForm.processing} />
                  </FormControl>
                </VStack>
              </SlabBody>
            </Slab>
          </CenteredBox>
          <Div mt={5}>
            <DisplaySessionResult
              sessionResults={sessionResults}
              classificationGroup={classificationGroup}
              academicSession={currentAcademicSession}
            />
          </Div>
        </>
      )}
    </DashboardLayout>
  );
}

function PromotionEntry({
  classifications,
  index,
  promotion,
  onPromotionUpdated,
  onRemove,
}: {
  index: number;
  classifications: Classification[];
  promotion: Promotion;
  onPromotionUpdated: (promotion: Promotion, index: number) => void;
  onRemove: (index: number) => void;
}) {
  function updatePromotion(field: string, value: string | number) {
    const updatePromotion = { ...promotion, [field]: Number(value) };
    onPromotionUpdated(updatePromotion, index);
  }
  return (
    <Div
      border={'1px solid #a1a1a1'}
      rounded={'lg'}
      position={'relative'}
      padding={'15px'}
    >
      <FormControl mt={2}>
        <FormLabel>Destination Class</FormLabel>
        <ClassificationSelect
          selectValue={promotion.destination_classification_id}
          value={promotion.destination_classification_id}
          classifications={classifications}
          onChange={(e: any) =>
            updatePromotion('destination_classification_id', e.value)
          }
        />
      </FormControl>
      <HStack align={'stretch'} spacing={3} mt={3}>
        <FormControl>
          <FormLabel>From</FormLabel>
          <Input
            onChange={(e) => updatePromotion('from', e.currentTarget.value)}
            value={promotion.from}
            type="number"
          />
        </FormControl>
        <FormControl>
          <FormLabel>To</FormLabel>
          <Input
            onChange={(e) => updatePromotion('to', e.currentTarget.value)}
            value={promotion.to}
            type="number"
          />
        </FormControl>
      </HStack>
      <IconButton
        aria-label="Remove promotion slab"
        icon={<Icon as={XMarkIcon} />}
        colorScheme={'red'}
        variant={'outline'}
        position={'absolute'}
        top={0}
        right={0}
        size={'sm'}
        mr={2}
        mt={2}
        onClick={() => onRemove(index)}
      />
    </Div>
  );
}

function DisplaySessionResult({
  sessionResults,
  classificationGroup,
  academicSession,
}: {
  sessionResults: SessionResult[];
  classificationGroup: ClassificationGroup;
  academicSession: AcademicSession;
}) {
  const headers: TableHeader<SessionResult>[] = [
    {
      label: 'User',
      value: 'student.user.full_name',
    },
    {
      label: 'Class',
      value: 'student.classification.title',
    },
    {
      label: 'Average',
      value: 'average',
    },
    {
      label: 'Total',
      value: 'result',
    },
    {
      label: 'Grade',
      value: 'grade',
    },
  ];

  return (
    <Slab>
      <SlabHeading
        title={`${classificationGroup.title} session result for ${academicSession.title} session`}
      />
      <SlabBody>
        <DataTable
          scroll={true}
          headers={headers}
          data={sessionResults}
          keyExtractor={(row) => row.id}
          hideSearchField={true}
        />
      </SlabBody>
    </Slab>
  );
}
