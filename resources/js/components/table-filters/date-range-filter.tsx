import React, { useState } from 'react';
import {
  Badge,
  Box,
  Button,
  Divider,
  HStack,
  Icon,
  IconButton,
  Input,
  InputGroup,
  InputLeftElement,
  Modal,
  ModalBody,
  ModalCloseButton,
  ModalContent,
  ModalFooter,
  ModalHeader,
  ModalOverlay,
  Select,
  SimpleGrid,
  Stack,
  Text,
  useColorModeValue,
  useDisclosure,
} from '@chakra-ui/react';
import {
  CalendarDaysIcon,
  ChevronRightIcon,
  SparklesIcon,
  XMarkIcon,
} from '@heroicons/react/24/outline';
import { format } from 'date-fns';

type FilterValue = string | number | boolean | undefined | null;

type FilterState = Record<string, FilterValue>;

type DateRangeKeyword =
  | 'today'
  | 'yesterday'
  | 'tomorrow'
  | 'this_week'
  | 'last_week'
  | 'next_week'
  | 'this_month'
  | 'last_month'
  | 'next_month'
  | 'this_quarter'
  | 'last_quarter'
  | 'next_quarter'
  | 'this_year'
  | 'last_year'
  | 'next_year';

interface Props {
  label?: string;
  filterKey: string;
  filters: FilterState;
  // onChange(key: string, value: string): void;
  onChange(dateRange: Record<string, string | null>): void;
  quickKeywords?: DateRangeKeyword[];
}

// const defaultQuickKeywords: DateRangeKeyword[] = [
//   'today',
//   'this_week',
//   'this_month',
//   'last_month',
//   'this_year',
//   'last_year',
// ];

const keywordLabels: Record<DateRangeKeyword, string> = {
  today: 'Today',
  yesterday: 'Yesterday',
  tomorrow: 'Tomorrow',
  this_week: 'This week',
  last_week: 'Last week',
  next_week: 'Next week',
  this_month: 'This month',
  last_month: 'Last month',
  next_month: 'Next month',
  this_quarter: 'This quarter',
  last_quarter: 'Last quarter',
  next_quarter: 'Next quarter',
  this_year: 'This year',
  last_year: 'Last year',
  next_year: 'Next year',
};

export function getDateRangeFilterKeys(filterKey: string) {
  return {
    keyword: `${filterKey}[keyword]`,
    dateFrom: `${filterKey}[date_from]`,
    dateTo: `${filterKey}[date_to]`,
  };
}

export function getDateRangeFilterParams(
  params: Record<string, string | undefined>,
  filterKey: string
) {
  const keys = getDateRangeFilterKeys(filterKey);

  return {
    [keys.keyword]: params[keys.keyword] ?? '',
    [keys.dateFrom]: params[keys.dateFrom] ?? '',
    [keys.dateTo]: params[keys.dateTo] ?? '',
  };
}

export function dateRangeFilterQueryKeys(filterKey: string) {
  return Object.values(getDateRangeFilterKeys(filterKey));
}

function formatFilterDate(value: string) {
  if (!value) {
    return '';
  }

  try {
    return format(new Date(value), 'MMM d, yyyy');
  } catch (error) {
    return value;
  }
}

export default function DateRangeFilter({
  label = 'Date range',
  filterKey,
  filters,
  onChange,
}: Props) {
  const { isOpen, onOpen, onClose } = useDisclosure();
  const keys = getDateRangeFilterKeys(filterKey);
  const [dateRange, setDateRange] = useState<Record<string, string | null>>({
    [keys.keyword]: `${filters[keys.keyword] ?? ''}` as DateRangeKeyword | '',
    [keys.dateFrom]: `${filters[keys.dateFrom] ?? ''}`,
    [keys.dateTo]: `${filters[keys.dateTo] ?? ''}`,
  });
  const hasValue = Boolean(
    dateRange[keys.keyword] ||
      dateRange[keys.dateFrom] ||
      dateRange[keys.dateTo]
  );

  const cardBg = useColorModeValue('white', 'gray.800');
  const subtleBg = useColorModeValue('gray.50', 'whiteAlpha.80');
  const borderColor = useColorModeValue('gray.200', 'whiteAlpha.200');
  const summaryColor = useColorModeValue('gray.600', 'gray.300');
  const activeBg = useColorModeValue('blue.50', 'blue.900');
  const activeBorder = useColorModeValue('blue.200', 'blue.700');

  const updateKeyword = (value: string) => {
    // onChange(keys.keyword, value);
    // onChange(keys.dateFrom, '');
    // onChange(keys.dateTo, '');
    setDateRange({
      [keys.keyword]: value as DateRangeKeyword,
      [keys.dateFrom]: null,
      [keys.dateTo]: null,
    });
  };

  const updateDate = (key: string, value: string) => {
    // onChange(keys.keyword, '');
    // onChange(key, value);
    setDateRange((prev) => ({
      ...prev,
      [keys.keyword]: null,
      [key]: value,
    }));
  };

  const clearValues = () => {
    setDateRange({
      [keys.keyword]: null,
      [keys.dateFrom]: null,
      [keys.dateTo]: null,
    });
  };

  const summary = (() => {
    if (dateRange[keys.keyword]) {
      return keywordLabels[dateRange[keys.keyword] as DateRangeKeyword];
    }

    if (dateRange[keys.dateFrom] || dateRange[keys.dateTo]) {
      return `${formatFilterDate(dateRange[keys.dateFrom] ?? '')} - ${
        formatFilterDate(dateRange[keys.dateTo] ?? '') || 'Any time'
      }`;
    }

    return 'Choose a preset range or exact dates';
  })();

  return (
    <>
      <Box
        as="button"
        type="button"
        textAlign="left"
        w="full"
        borderWidth="1px"
        borderColor={hasValue ? activeBorder : borderColor}
        bg={hasValue ? activeBg : cardBg}
        rounded="2xl"
        px={4}
        py={4}
        boxShadow="0 12px 28px rgba(15, 23, 42, 0.06)"
        transition="all 0.2s ease"
        _hover={{ transform: 'translateY(-1px)', boxShadow: 'lg' }}
        onClick={onOpen}
      >
        <HStack justify="space-between" align="start" spacing={4}>
          <HStack align="start" spacing={3}>
            <Box
              p={2.5}
              rounded="xl"
              bgGradient="linear(to-br, blue.500, cyan.400)"
              color="white"
            >
              <Icon as={CalendarDaysIcon} boxSize={5} />
            </Box>
            <Stack spacing={1}>
              <HStack spacing={2}>
                <Text fontWeight="semibold">{label}</Text>
                {hasValue && (
                  <Badge colorScheme="blue" rounded="full" px={2}>
                    Active
                  </Badge>
                )}
              </HStack>
              <Text fontSize="sm" color={summaryColor}>
                {summary}
              </Text>
            </Stack>
          </HStack>

          <HStack spacing={2}>
            {hasValue && (
              <IconButton
                aria-label={`Clear ${label}`}
                icon={<Icon as={XMarkIcon} boxSize={4} />}
                size="sm"
                variant="ghost"
                rounded="full"
                onClick={(e) => {
                  e.stopPropagation();
                  clearValues();
                }}
              />
            )}
            <Icon as={ChevronRightIcon} boxSize={5} color="gray.400" />
          </HStack>
        </HStack>
      </Box>

      <Modal isOpen={isOpen} onClose={onClose} size="lg" isCentered>
        <ModalOverlay bg="blackAlpha.500" backdropFilter="blur(6px)" />
        <ModalContent rounded="3xl" overflow="hidden">
          <ModalHeader
            bgGradient="linear(to-r, blue.600, cyan.500)"
            color="white"
            py={5}
          >
            <HStack spacing={3}>
              <Box bg="whiteAlpha.250" p={2} rounded="xl">
                <Icon as={CalendarDaysIcon} boxSize={5} />
              </Box>
              <Stack spacing={0}>
                <Text>{label}</Text>
                <Text fontSize="sm" color="whiteAlpha.800" fontWeight="normal">
                  Choose a keyword range or set exact dates.
                </Text>
              </Stack>
            </HStack>
          </ModalHeader>
          <ModalCloseButton color="white" />

          <ModalBody bg={subtleBg} py={6}>
            <Stack spacing={5}>
              <Box
                bg="white"
                borderWidth="1px"
                borderColor={borderColor}
                rounded="2xl"
                p={5}
              >
                <HStack spacing={2} mb={3}>
                  <Icon as={SparklesIcon} boxSize={4} color="blue.500" />
                  <Text fontWeight="semibold">Keyword range</Text>
                </HStack>
                <Select
                  placeholder="Select a quick date range"
                  rounded="xl"
                  bg="white"
                  value={dateRange[keys.keyword] ?? ''}
                  onChange={(e) => updateKeyword(e.currentTarget.value)}
                >
                  {Object.entries(keywordLabels).map(([key, value]) => (
                    <option key={key} value={key}>
                      {value}
                    </option>
                  ))}
                </Select>
                <Text mt={3} fontSize="xs" color={summaryColor}>
                  Selecting a keyword clears any exact date range.
                </Text>
              </Box>

              <HStack>
                <Divider />
                <Text
                  fontSize="xs"
                  textTransform="uppercase"
                  letterSpacing="0.08em"
                  color={summaryColor}
                >
                  Or
                </Text>
                <Divider />
              </HStack>

              <Box
                bg="white"
                borderWidth="1px"
                borderColor={borderColor}
                rounded="2xl"
                p={5}
              >
                <Text fontWeight="semibold" mb={3}>
                  Exact dates
                </Text>
                <SimpleGrid columns={{ base: 1, md: 2 }} spacing={3}>
                  <InputGroup>
                    <InputLeftElement pointerEvents="none">
                      <Icon
                        as={CalendarDaysIcon}
                        color="gray.400"
                        boxSize={4}
                      />
                    </InputLeftElement>
                    <Input
                      type="date"
                      bg="white"
                      rounded="xl"
                      value={dateRange[keys.dateFrom] ?? ''}
                      onChange={(e) =>
                        updateDate(keys.dateFrom, e.currentTarget.value)
                      }
                    />
                  </InputGroup>

                  <InputGroup>
                    <InputLeftElement pointerEvents="none">
                      <Icon
                        as={CalendarDaysIcon}
                        color="gray.400"
                        boxSize={4}
                      />
                    </InputLeftElement>
                    <Input
                      type="date"
                      bg="white"
                      rounded="xl"
                      value={dateRange[keys.dateTo] ?? ''}
                      onChange={(e) =>
                        updateDate(keys.dateTo, e.currentTarget.value)
                      }
                    />
                  </InputGroup>
                </SimpleGrid>
                <Text mt={3} fontSize="xs" color={summaryColor}>
                  Manual dates clear the keyword automatically.
                </Text>
              </Box>
            </Stack>
          </ModalBody>

          <ModalFooter
            bg="white"
            borderTopWidth="1px"
            borderColor={borderColor}
          >
            <HStack w="full" justify="space-between">
              <Button
                variant="ghost"
                onClick={clearValues}
                isDisabled={!hasValue}
              >
                Reset
              </Button>
              <Button
                colorScheme="brand"
                onClick={() => {
                  onChange(dateRange);
                  onClose();
                }}
              >
                Done
              </Button>
            </HStack>
          </ModalFooter>
        </ModalContent>
      </Modal>
    </>
  );
}
