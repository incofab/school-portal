import React from 'react';
import { BoxProps, Text, TextProps } from '@chakra-ui/react';
import { Div } from './semantic';
import { ResultCommentTemplate } from '@/types/models';

export const GradingTable = ({
  resultCommentTemplate,
}: {
  resultCommentTemplate: ResultCommentTemplate[];
}) => (
  <table className="result-analysis-table">
    <thead>
      <tr>
        <th colSpan={3}>Keys</th>
      </tr>
      <tr>
        <td>Score</td>
        <td>Grade</td>
        <td>Remark</td>
      </tr>
    </thead>
    <tbody>
      {resultCommentTemplate.map((item) => {
        const { grade, grade_label } = item;
        return (
          <tr key={grade}>
            <td>{`${item.min} - ${item.max}`}</td>
            <td>{grade_label}</td>
            <td>{grade}</td>
          </tr>
        );
      })}
    </tbody>
  </table>
);

export const LabelText = function ({
  label,
  text,
  labelProps,
  textProps,
  ...props
}: {
  label: string;
  text: string | number | undefined | React.ReactNode;
  labelProps?: TextProps;
  textProps?: TextProps;
} & BoxProps) {
  return (
    <Div {...props}>
      <Text
        as={'span'}
        fontWeight={'semibold'}
        display={'inline-block'}
        {...labelProps}
      >
        {label}:
      </Text>
      <Text as={'span'} ml={3} {...textProps} display={'inline-block'}>
        {text}
      </Text>
    </Div>
  );
};
