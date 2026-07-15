import React from 'react';
import {
  Badge,
  Box,
  Button,
  Divider,
  FormControl,
  HStack,
  Icon,
  IconButton,
  Input,
  Select,
  SimpleGrid,
  Text,
  VStack,
} from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import useWebForm from '@/hooks/use-web-form';
import { preventNativeSubmit } from '@/util/util';
import { Inertia } from '@inertiajs/inertia';
import { Association, Fee } from '@/types/models';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import CenteredBox from '@/components/centered-box';
import { BrandButton, FormButton } from '@/components/buttons';
import InputForm from '@/components/forms/input-form';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import FormControlBox from '@/components/forms/form-control-box';
import EnumSelect from '@/components/dropdown-select/enum-select';
import { FeeItem, FeePaymentInterval, TermType } from '@/types/types';
import useSharedProps from '@/hooks/use-shared-props';
import AcademicSessionSelect from '@/components/selectors/academic-session-select';
import {
  ArrowPathIcon,
  CheckCircleIcon,
  PlusIcon,
  TrashIcon,
} from '@heroicons/react/24/outline';
import SelectFeeCategoryModal from '@/components/modals/select-fee-category-modal';
import useModalToggle from '@/hooks/use-modal-toggle';
import feeableUtil from '@/util/feeable-util';
import { formatAsCurrency } from '@/util/util';

interface FeeCategoryMorph {
  feeable_id: number;
  feeable_type: string;
  label: string;
  value: number;
}
interface Props {
  fee?: Fee;
  associations: Association[];
  feeTemplates?: Fee[];
}

export default function CreateOrUpdateFee({
  fee,
  associations,
  feeTemplates = [],
}: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const selectFeeCategoryModalToggle = useModalToggle();
  const { currentAcademicSessionId, currentTerm } = useSharedProps();
  const [selectedTemplateId, setSelectedTemplateId] = React.useState('');
  const webForm = useWebForm({
    title: fee?.title ?? '',
    amount: fee?.amount ?? '',
    payment_interval: fee?.payment_interval ?? FeePaymentInterval.Termly,
    term: fee?.term ?? currentTerm ?? '',
    academic_session_id: (fee?.academic_session_id ??
      currentAcademicSessionId) as number | string,
    fee_items: fee?.fee_items ?? [{ title: '', amount: 0 }],
    fee_categories:
      fee?.fee_categories.map((item) => ({
        feeable_id: item.feeable_id,
        feeable_type: item.feeable_type,
        label: feeableUtil(item.feeable).getName(),
        value: item.feeable_id,
      })) ?? ([] as FeeCategoryMorph[]),
  });
  const selectedTemplate = React.useMemo(
    () =>
      feeTemplates.find(
        (template) => template.id.toString() === selectedTemplateId
      ),
    [feeTemplates, selectedTemplateId]
  );

  const applyTemplate = (template: Fee) => {
    const paymentInterval =
      template.payment_interval ?? FeePaymentInterval.Termly;
    const isTermly = paymentInterval === FeePaymentInterval.Termly;
    const isOneTime = paymentInterval === FeePaymentInterval.OneTime;

    webForm.setData({
      ...webForm.data,
      title: template.title,
      amount: template.amount,
      payment_interval: paymentInterval,
      academic_session_id: isOneTime
        ? ''
        : webForm.data.academic_session_id ||
          currentAcademicSessionId ||
          template.academic_session_id ||
          '',
      term: isTermly
        ? webForm.data.term || currentTerm || template.term || ''
        : '',
      fee_items:
        template.fee_items && template.fee_items.length > 0
          ? template.fee_items.map((item) => ({
              title: item.title,
              amount: item.amount,
            }))
          : [{ title: '', amount: 0 }],
      fee_categories: template.fee_categories.map((item) => ({
        feeable_id: item.feeable_id,
        feeable_type: item.feeable_type,
        label: item.feeable ? feeableUtil(item.feeable).getName() : '',
        value: item.feeable_id,
      })),
    });
  };

  const submit = async () => {
    const res = await webForm.submit((data, web) =>
      fee
        ? web.put(instRoute('fees.update', [fee]), data)
        : web.post(instRoute('fees.store'), data)
    );
    if (!handleResponseToast(res)) return;
    Inertia.visit(instRoute('fees.index'));
  };

  return (
    <DashboardLayout>
      <CenteredBox>
        <Slab>
          <SlabHeading title={`${fee ? 'Update' : 'Create'} Fee`} />
          <SlabBody>
            <VStack
              spacing={4}
              as={'form'}
              onSubmit={preventNativeSubmit(submit)}
            >
              {!fee && feeTemplates.length > 0 && (
                <FeeTemplatePicker
                  feeTemplates={feeTemplates}
                  selectedTemplateId={selectedTemplateId}
                  selectedTemplate={selectedTemplate}
                  onSelect={setSelectedTemplateId}
                  onApply={applyTemplate}
                />
              )}
              <InputForm
                form={webForm as any}
                formKey="title"
                title="Fee title"
              />

              <InputForm
                form={webForm as any}
                formKey="amount"
                title="Amount"
              />

              <FormControlBox
                form={webForm as any}
                formKey="payment_interval"
                title="Payment Interval"
              >
                <EnumSelect
                  selectValue={webForm.data.payment_interval}
                  enumData={FeePaymentInterval}
                  onChange={(e: any) => {
                    const interval = e.value;
                    webForm.setData({
                      ...webForm.data,
                      payment_interval: interval,
                      academic_session_id:
                        interval === FeePaymentInterval.OneTime
                          ? ''
                          : webForm.data.academic_session_id,
                      term:
                        interval === FeePaymentInterval.Termly
                          ? webForm.data.term
                          : '',
                    });
                  }}
                />
              </FormControlBox>
              {webForm.data.payment_interval === FeePaymentInterval.Termly && (
                <FormControlBox
                  form={webForm as any}
                  formKey="term"
                  title="Term"
                >
                  <EnumSelect
                    selectValue={webForm.data.term}
                    enumData={TermType}
                    onChange={(e: any) => webForm.setValue('term', e.value)}
                  />
                </FormControlBox>
              )}
              {webForm.data.payment_interval !== FeePaymentInterval.OneTime && (
                <FormControlBox
                  form={webForm as any}
                  formKey="academic_session_id"
                  title="Academic Session"
                >
                  <AcademicSessionSelect
                    selectValue={webForm.data.academic_session_id}
                    isMulti={false}
                    isClearable={true}
                    onChange={(e: any) =>
                      webForm.setValue('academic_session_id', e?.value)
                    }
                  />
                </FormControlBox>
              )}

              <FeeItems feeItems={webForm.data.fee_items} webForm={webForm} />

              <VStack
                border={'1px solid'}
                borderRadius={'7px'}
                borderColor={'gray.200'}
                p={4}
                spacing={3}
                w={'100%'}
                align={'stretch'}
              >
                <Text>Category of Students this Fee is meant for</Text>
                {webForm.data.fee_categories.map((item, i) => (
                  <HStack key={i}>
                    <Icon as={CheckCircleIcon} color={'brand.500'} key={i} />
                    <Text>{item.label}</Text>
                  </HStack>
                ))}
                <BrandButton
                  title="Add Category"
                  onClick={selectFeeCategoryModalToggle.open}
                  width={'120px'}
                  type={'button'}
                />
              </VStack>

              <FormControl>
                <FormButton isLoading={webForm.processing} />
              </FormControl>
            </VStack>
          </SlabBody>
        </Slab>
      </CenteredBox>
      <SelectFeeCategoryModal
        associations={associations}
        feeCategories={webForm.data.fee_categories as FeeCategoryMorph[]}
        {...selectFeeCategoryModalToggle.props}
        onSuccess={(result) => {
          webForm.setValue('fee_categories', result);
        }}
      />
    </DashboardLayout>
  );
}

function FeeTemplatePicker({
  feeTemplates,
  selectedTemplateId,
  selectedTemplate,
  onSelect,
  onApply,
}: {
  feeTemplates: Fee[];
  selectedTemplateId: string;
  selectedTemplate?: Fee;
  onSelect: (id: string) => void;
  onApply: (fee: Fee) => void;
}) {
  return (
    <VStack
      border={'1px solid'}
      borderColor={'gray.200'}
      borderRadius={'7px'}
      p={4}
      spacing={3}
      align={'stretch'}
      w={'100%'}
    >
      <HStack justify={'space-between'} align={'start'} spacing={3}>
        <Box>
          <Text fontWeight={'semibold'}>Reuse previous fee structure</Text>
          <Text color={'gray.600'} fontSize={'sm'}>
            Copy line items, amount, interval, and student categories.
          </Text>
        </Box>
        <Icon as={ArrowPathIcon} boxSize={5} color={'brand.500'} />
      </HStack>

      <HStack align={'end'} spacing={3}>
        <FormControl>
          <Select
            placeholder={'Select a previous fee'}
            value={selectedTemplateId}
            onChange={(e) => onSelect(e.currentTarget.value)}
          >
            {feeTemplates.map((template) => (
              <option key={template.id} value={template.id}>
                {template.title} - {formatAsCurrency(template.amount)}
              </option>
            ))}
          </Select>
        </FormControl>
        <Button
          colorScheme={'brand'}
          leftIcon={<Icon as={ArrowPathIcon} />}
          isDisabled={!selectedTemplate}
          onClick={() => selectedTemplate && onApply(selectedTemplate)}
          flexShrink={0}
        >
          Use structure
        </Button>
      </HStack>

      {selectedTemplate && (
        <>
          <Divider />
          <SimpleGrid columns={{ base: 1, md: 3 }} spacing={3}>
            <TemplatePreviewItem
              label={'Source period'}
              value={
                selectedTemplate.payment_interval === FeePaymentInterval.OneTime
                  ? 'One-time'
                  : [
                      selectedTemplate.academic_session?.title,
                      selectedTemplate.term,
                    ]
                      .filter(Boolean)
                      .join(' / ') || 'Not set'
              }
            />
            <TemplatePreviewItem
              label={'Line items'}
              value={`${selectedTemplate.fee_items?.length ?? 0}`}
            />
            <TemplatePreviewItem
              label={'Categories'}
              value={`${selectedTemplate.fee_categories.length}`}
            />
          </SimpleGrid>
          <HStack spacing={2} wrap={'wrap'}>
            {selectedTemplate.fee_items?.slice(0, 4).map((item, index) => (
              <Badge key={`${item.title}-${index}`} colorScheme={'gray'}>
                {item.title}: {formatAsCurrency(item.amount)}
              </Badge>
            ))}
            {(selectedTemplate.fee_items?.length ?? 0) > 4 && (
              <Badge colorScheme={'gray'}>
                +{(selectedTemplate.fee_items?.length ?? 0) - 4} more
              </Badge>
            )}
          </HStack>
        </>
      )}
    </VStack>
  );
}

function TemplatePreviewItem({
  label,
  value,
}: {
  label: string;
  value: string;
}) {
  return (
    <Box>
      <Text color={'gray.500'} fontSize={'xs'} textTransform={'uppercase'}>
        {label}
      </Text>
      <Text fontWeight={'semibold'}>{value}</Text>
    </Box>
  );
}

function FeeItems({
  feeItems,
  webForm,
}: {
  feeItems: FeeItem[];
  webForm: any;
}) {
  return (
    <VStack
      spacing={2}
      align={'stretch'}
      w={'100%'}
      border={'1px solid'}
      borderColor={'gray.200'}
      borderRadius={'7px'}
      py={3}
      px={4}
    >
      <Text fontWeight={'semibold'} mb={3}>
        Line Items that make up the fee
      </Text>
      {feeItems.map((feeItem, i) => (
        <HStack key={i}>
          <FormControlBox title={'Title'} formKey={'title'} form={webForm}>
            <Input
              onChange={(e) => {
                feeItem.title = e.currentTarget.value;
                feeItems[i] = feeItem;
                webForm.setData({ ...webForm.data, fee_items: feeItems });
              }}
              value={feeItem.title}
            />
          </FormControlBox>
          <FormControlBox title={'Amount'} formKey={'amount'} form={webForm}>
            <Input
              type={'number'}
              onChange={(e) => {
                feeItem.amount = parseInt(e.currentTarget.value);
                feeItems[i] = feeItem;
                webForm.setData({ ...webForm.data, fee_items: feeItems });
              }}
              value={feeItem.amount}
            />
          </FormControlBox>
          <IconButton
            aria-label={'Delete Fee Item'}
            icon={<Icon as={TrashIcon} />}
            variant={'ghost'}
            colorScheme={'red'}
            onClick={() => {
              feeItems.splice(i, 1);
              webForm.setData({ ...webForm.data, fee_items: feeItems });
            }}
            mt={4}
          />
        </HStack>
      ))}
      <IconButton
        aria-label={'Add Fee Item'}
        icon={<Icon as={PlusIcon} />}
        variant={'outline'}
        colorScheme={'brand'}
        onClick={() => {
          feeItems.push({ title: '', amount: 0 });
          webForm.setData({ ...webForm.data, fee_items: feeItems });
        }}
        w={'20px'}
      />
    </VStack>
  );
}
