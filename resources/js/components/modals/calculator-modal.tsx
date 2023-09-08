import React, { useState } from 'react';
import {
  Button,
  Text,
  Grid,
  GridItem,
  ButtonProps,
  GridItemProps,
} from '@chakra-ui/react';
import { Div } from '../semantic';
import GenericModal from '../generic-modal';

interface Props {
  isOpen: boolean;
  onClose(): void;
}

export default function CalculatorModal({ isOpen, onClose }: Props) {
  const [input, setInput] = useState<string>('');

  const handleButtonClick = (value: string) => {
    setInput((prevInput) => prevInput + value);
  };
  const handleOperatorClick = (value: string) => {
    setInput((prevInput) => {
      const lastChar = prevInput.substring(prevInput.length - 1);
      if (['+', '-', '*', '/'].includes(lastChar)) {
        return prevInput.substring(0, prevInput.length - 1) + value;
      }
      return prevInput + value;
    });
  };

  const clearInput = () => {
    setInput('');
  };

  const calculateResult = () => {
    try {
      setInput(eval(input).toString());
    } catch (error) {
      setInput('Error');
    }
  };
  const buttons: {
    label: string;
    onClick: () => void;
    buttonProps?: ButtonProps;
    gridItemProps?: GridItemProps;
  }[] = [
    { label: '1', onClick: () => handleButtonClick('1') },
    { label: '2', onClick: () => handleButtonClick('2') },
    { label: '3', onClick: () => handleButtonClick('3') },
    {
      label: '=',
      onClick: () => calculateResult(),
      gridItemProps: { rowSpan: 4 },
    },
    { label: '4', onClick: () => handleButtonClick('4') },
    { label: '5', onClick: () => handleButtonClick('5') },
    { label: '6', onClick: () => handleButtonClick('6') },
    { label: '7', onClick: () => handleButtonClick('7') },
    { label: '8', onClick: () => handleButtonClick('8') },
    { label: '9', onClick: () => handleButtonClick('9') },
    { label: '0', onClick: () => handleButtonClick('0') },
    { label: '.', onClick: () => handleButtonClick('.') },
    { label: 'C', onClick: () => clearInput() },
    { label: '+', onClick: () => handleOperatorClick('+') },
    { label: '-', onClick: () => handleOperatorClick('-') },
    { label: 'x', onClick: () => handleOperatorClick('*') },
    { label: '/', onClick: () => handleOperatorClick('/') },
  ];
  return (
    <GenericModal
      props={{ isOpen, onClose }}
      bodyProps={{ p: 0 }}
      headerContent={'Calculator'}
      bodyContent={
        <Div p={4}>
          <Grid templateColumns="repeat(4, 1fr)" gap={2}>
            <GridItem colSpan={4}>
              <Div p={2} bg="gray.100" borderRadius="md" minHeight="40px">
                <Text fontSize="xl">{input}</Text>
              </Div>
            </GridItem>
            {/* <GridItem colSpan={3}>
              <Grid templateColumns="repeat(3, 1fr)" gap={2}> */}
            {buttons.map((item) => (
              <GridItem key={item.label} {...item.gridItemProps}>
                <Button
                  onClick={item.onClick}
                  colorScheme="blue"
                  size="lg"
                  width="100%"
                  {...item.buttonProps}
                  height={'100%'}
                  minH={'45px'}
                >
                  {item.label}
                </Button>
              </GridItem>
            ))}
            {/* </Grid>
            </GridItem> */}
            {/* <GridItem colSpan={1}>
              <Button
                onClick={calculateResult}
                colorScheme="green"
                size="lg"
                width="100%"
              >
                =
              </Button>
            </GridItem>
            <GridItem colSpan={1}>
              <Button
                onClick={clearInput}
                colorScheme="red"
                size="lg"
                width="100%"
              >
                C
              </Button>
            </GridItem> */}
          </Grid>
        </Div>
      }
      footerContent={
        <Button variant={'brand'} onClick={onClose}>
          Close
        </Button>
      }
    />
  );
}
