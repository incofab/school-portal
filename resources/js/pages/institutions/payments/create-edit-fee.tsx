import React from 'react';
import {
  FormControl,
  HStack,
  Icon,
  IconButton,
  Input,
  Text,
  VStack,
} from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import useWebForm from '@/hooks/use-web-form';
import { preventNativeSubmit } from '@/util/util';
import { Inertia } from '@inertiajs/inertia';
import {
  Association,
  Classification,
  ClassificationGroup,
  Fee,
} from '@/types/models';
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
  CheckCircleIcon,
  PlusIcon,
  TrashIcon,
} from '@heroicons/react/24/outline';
import SelectFeeCategoryModal from '@/components/modals/select-fee-category-modal';
import useModalToggle from '@/hooks/use-modal-toggle';
import feeableUtil from '@/util/feeable-util';

interface FeeCategoryMorph {
  feeable_id: number;
  feeable_type: string;
  label: string;
  value: number;
}
interface Props {
  fee?: Fee;
  associations: Association[];
  classificationGroups: ClassificationGroup[];
  classifications: Classification[];
}

export default function CreateOrUpdateFee({
  fee,
  associations,
  classificationGroups,
  classifications,
}: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const selectFeeCategoryModalToggle = useModalToggle();
  const { currentAcademicSessionId, currentTerm } = useSharedProps();
  const webForm = useWebForm({
    title: fee?.title ?? '',
    amount: fee?.amount ?? '',
    payment_interval: fee?.payment_interval ?? FeePaymentInterval.Termly,
    term: fee?.term ?? currentTerm,
    academic_session_id: fee?.academic_session_id ?? currentAcademicSessionId,
    fee_items: fee?.fee_items ?? [{ title: '', amount: 0 }],
    fee_categories:
      fee?.fee_categories.map((item) => ({
        feeable_id: item.feeable_id,
        feeable_type: item.feeable_type,
        label: feeableUtil(item.feeable).getName(),
        value: item.feeable_id,
      })) ?? ([] as FeeCategoryMorph[]),
  });

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
                  onChange={(e: any) =>
                    webForm.setValue('payment_interval', e.value)
                  }
                />
              </FormControlBox>

              <FormControlBox form={webForm as any} formKey="term" title="Term">
                <EnumSelect
                  selectValue={webForm.data.term}
                  enumData={TermType}
                  onChange={(e: any) => webForm.setValue('term', e.value)}
                />
              </FormControlBox>

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
        classificationGroups={classificationGroups}
        classifications={classifications}
        feeCategories={webForm.data.fee_categories as FeeCategoryMorph[]}
        {...selectFeeCategoryModalToggle.props}
        onSuccess={(result) => {
          webForm.setValue('fee_categories', result);
        }}
      />
    </DashboardLayout>
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
              if (feeItems.length === 1) {
                return;
              }
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
