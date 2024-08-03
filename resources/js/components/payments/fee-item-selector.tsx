import { Fee } from '@/types/models';
import { formatAsCurrency } from '@/util/util';
import { Checkbox, Divider, Text, VStack } from '@chakra-ui/react';

export function FeeItemSelector({
  fees,
  selected_fee_ids,
  updateSelection,
  receipt_type_id,
  classification_group_id,
  classification_id,
}: {
  fees: Fee[];
  selected_fee_ids: number[];
  updateSelection: (feeIds: number[]) => void;
  receipt_type_id?: number;
  classification_group_id?: number;
  classification_id?: number;
}) {
  let filteredFees = fees.filter((fee) => {
    if (
      classification_group_id &&
      fee.classification_group_id &&
      fee.classification_group_id !== classification_group_id
    ) {
      return false;
    }
    if (
      classification_id &&
      fee.classification_id &&
      fee.classification_id !== classification_id
    ) {
      return false;
    }
    if (fee.receipt_type_id !== receipt_type_id) {
      return false;
    }
    return true;
  });

  function getTotalAmount() {
    var amount = 0;
    selected_fee_ids.map((id) => {
      let fee = fees.find((fee) => fee.id === id);
      amount += fee?.amount ?? 0;
    });
    return amount;
  }

  const allSelected =
    filteredFees.length > 0 && filteredFees.length === selected_fee_ids.length;
  return (
    <VStack spacing={2} align={'stretch'}>
      <Text my={2} fontWeight={'bold'}>
        {formatAsCurrency(getTotalAmount())}
      </Text>
      {filteredFees.length > 0 && (
        <>
          <Checkbox
            isChecked={allSelected}
            onChange={(e) => {
              updateSelection(
                e.currentTarget.checked ? filteredFees.map((fee) => fee.id) : []
              );
            }}
            size={'md'}
            colorScheme="brand"
          >
            Select All
          </Checkbox>
          <Divider my={2} />
        </>
      )}
      {filteredFees.map((fee) => {
        return (
          <Checkbox
            key={fee.id}
            isChecked={selected_fee_ids.includes(fee.id)}
            onChange={(e) => {
              if (e.currentTarget.checked) {
                selected_fee_ids.push(fee.id);
              } else {
                selected_fee_ids = selected_fee_ids.filter(
                  (item) => item !== fee.id
                );
              }
              updateSelection(selected_fee_ids);
            }}
            size={'md'}
            colorScheme="brand"
          >
            {fee.title} ({formatAsCurrency(fee.amount)})
          </Checkbox>
        );
      })}
    </VStack>
  );
}
