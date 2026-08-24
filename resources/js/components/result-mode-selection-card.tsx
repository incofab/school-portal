import React from 'react';
import {
  AcademicCapIcon,
  CheckCircleIcon,
  ClipboardDocumentCheckIcon,
} from '@heroicons/react/24/outline';
import {
  Badge,
  Box,
  BoxProps,
  Button,
  FormControl,
  FormErrorMessage,
  HStack,
  Icon,
  SimpleGrid,
  Stack,
  Text,
  useColorModeValue,
} from '@chakra-ui/react';

export type ResultMode = 'full-term' | 'mid-term' | '';

interface Props {
  value: ResultMode;
  isDisabled?: boolean;
  error?: string;
  onChange: (value: Exclude<ResultMode, ''>) => void;
}

export default function ResultModeSelectionCard({
  value,
  isDisabled = false,
  error,
  onChange,
  ...props
}: Props & BoxProps) {
  const hasSelectedResultMode = value !== '';
  const selectionPanelBg = useColorModeValue('white', 'gray.800');
  const selectionPanelMuted = useColorModeValue('gray.600', 'gray.300');
  const selectionPanelBorder = useColorModeValue('brand.100', 'gray.700');
  const optionBg = useColorModeValue('gray.50', 'gray.700');
  const optionSelectedBg = useColorModeValue('brand.50', 'whiteAlpha.100');
  const optionBorder = useColorModeValue('gray.200', 'gray.600');
  const optionSelectedBorder = useColorModeValue('brand.500', 'brand.300');
  const optionShadow = useColorModeValue(
    '0 12px 30px rgba(0, 0, 0, 0.08)',
    '0 12px 30px rgba(0, 0, 0, 0.28)'
  );

  return (
    <Box
      bg={selectionPanelBg}
      borderColor={selectionPanelBorder}
      borderWidth={1}
      borderRadius="lg"
      boxShadow="0px 2px 6px rgba(0, 0, 0, 0.1)"
      p={{ base: 4, md: 6 }}
      {...props}
    >
      <Stack spacing={5}>
        <HStack justify="space-between" align="start" spacing={4}>
          <Box>
            <Badge colorScheme="brand" mb={2}>
              Required First Step
            </Badge>
            <Text fontSize={{ base: 'lg', md: 'xl' }} fontWeight="bold">
              Select result recording type
            </Text>
            <Text color={selectionPanelMuted} fontSize="sm" mt={1}>
              Choose full term or mid-term before entering a student result.
            </Text>
          </Box>
          <Badge
            colorScheme={hasSelectedResultMode ? 'brand' : 'red'}
            variant={hasSelectedResultMode ? 'subtle' : 'solid'}
            flexShrink={0}
          >
            {hasSelectedResultMode
              ? value === 'mid-term'
                ? 'Mid-Term Result'
                : 'Full Term Result'
              : 'Selection required'}
          </Badge>
        </HStack>

        <FormControl
          isInvalid={Boolean(error)}
          role="radiogroup"
          aria-label="Result recording type"
        >
          <SimpleGrid columns={{ base: 1, md: 2 }} spacing={4}>
            <ResultModeOption
              title="Full Term"
              description="Record scores for the complete term."
              icon={ClipboardDocumentCheckIcon}
              isSelected={value === 'full-term'}
              isDisabled={isDisabled}
              bg={optionBg}
              selectedBg={optionSelectedBg}
              borderColor={optionBorder}
              selectedBorderColor={optionSelectedBorder}
              selectedShadow={optionShadow}
              onClick={() => onChange('full-term')}
            />
            <ResultModeOption
              title="Mid-Term"
              description="Record scores for the mid-term assessment."
              icon={AcademicCapIcon}
              isSelected={value === 'mid-term'}
              isDisabled={isDisabled}
              bg={optionBg}
              selectedBg={optionSelectedBg}
              borderColor={optionBorder}
              selectedBorderColor={optionSelectedBorder}
              selectedShadow={optionShadow}
              onClick={() => onChange('mid-term')}
            />
          </SimpleGrid>
          <FormErrorMessage>{error}</FormErrorMessage>
        </FormControl>
      </Stack>
    </Box>
  );
}

interface ResultModeOptionProps {
  title: string;
  description: string;
  icon: React.ElementType;
  isSelected: boolean;
  isDisabled: boolean;
  bg: string;
  selectedBg: string;
  borderColor: string;
  selectedBorderColor: string;
  selectedShadow: string;
  onClick: () => void;
}

function ResultModeOption({
  title,
  description,
  icon,
  isSelected,
  isDisabled,
  bg,
  selectedBg,
  borderColor,
  selectedBorderColor,
  selectedShadow,
  onClick,
}: ResultModeOptionProps) {
  return (
    <Button
      type="button"
      role="radio"
      aria-checked={isSelected}
      aria-label={`${title} ${description}`}
      onClick={onClick}
      isDisabled={isDisabled}
      variant="outline"
      h="auto"
      minH="132px"
      whiteSpace="normal"
      justifyContent="stretch"
      textAlign="left"
      p={4}
      borderWidth={2}
      borderRadius="lg"
      borderColor={isSelected ? selectedBorderColor : borderColor}
      bg={isSelected ? selectedBg : bg}
      boxShadow={isSelected ? selectedShadow : undefined}
      _hover={{
        borderColor: selectedBorderColor,
        bg: selectedBg,
      }}
      _active={{ bg: selectedBg }}
    >
      <HStack align="start" spacing={3} w="full">
        <Box
          h={10}
          minW={10}
          borderRadius="full"
          display="grid"
          placeItems="center"
          bg={isSelected ? 'brand.500' : 'brand.50'}
          color={isSelected ? 'white' : 'brand.600'}
        >
          <Icon as={isSelected ? CheckCircleIcon : icon} fontSize="xl" />
        </Box>
        <Box>
          <Text fontWeight="bold" mb={1}>
            {title}
          </Text>
          <Text fontSize="sm" fontWeight="normal" lineHeight="1.55">
            {description}
          </Text>
        </Box>
      </HStack>
    </Button>
  );
}
