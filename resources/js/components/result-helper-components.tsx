import React from 'react';
import { BoxProps, Text, TextProps } from '@chakra-ui/react';
import ResultUtil from '@/util/result-util';
import { Div } from './semantic';

export const GradingTable = () => (
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
      <tr>
        <td>0 - 39</td>
        <td>A</td>
        <td>{ResultUtil.getRemark('A')}</td>
      </tr>
      <tr>
        <td>60 - 69</td>
        <td>B</td>
        <td>{ResultUtil.getRemark('B')}</td>
      </tr>
      <tr>
        <td>50 - 59</td>
        <td>C</td>
        <td>{ResultUtil.getRemark('C')}</td>
      </tr>
      <tr>
        <td>45 - 49</td>
        <td>D</td>
        <td>{ResultUtil.getRemark('D')}</td>
      </tr>
      <tr>
        <td>40 - 44</td>
        <td>E</td>
        <td>{ResultUtil.getRemark('E')}</td>
      </tr>
      <tr>
        <td>0 - 39</td>
        <td>F</td>
        <td>{ResultUtil.getRemark('F')}</td>
      </tr>
    </tbody>
  </table>
);

export const LabelText = function ({
  label,
  text,
  labelProps,
  textProps,
}: {
  label: string;
  text: string | number | undefined | React.ReactNode;
  labelProps?: TextProps;
  textProps?: TextProps;
}) {
  return (
    <Div>
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
