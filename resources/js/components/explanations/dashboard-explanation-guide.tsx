import React from 'react';
import {
  Badge,
  Box,
  Button,
  HStack,
  Icon,
  Modal,
  ModalBody,
  ModalCloseButton,
  ModalContent,
  ModalFooter,
  ModalHeader,
  ModalOverlay,
  SimpleGrid,
  Stack,
  Text,
  Tooltip,
  useColorModeValue,
  useDisclosure,
} from '@chakra-ui/react';
import { usePage } from '@inertiajs/inertia-react';
import {
  InformationCircleIcon,
  LightBulbIcon,
  QuestionMarkCircleIcon,
} from '@heroicons/react/24/outline';
import { Div, Li, Ul } from '@/components/semantic';
import {
  DashboardExplanation,
  findDashboardExplanation,
} from '@/components/explanations/dashboard-explanations';
import useSharedProps from '@/hooks/use-shared-props';

export default function DashboardExplanationGuide() {
  const page = usePage();
  const { currentInstitution } = useSharedProps();
  const { isOpen, onClose, onOpen } = useDisclosure();
  const explanation = findDashboardExplanation(
    page.url.split('?')[0],
    currentInstitution.uuid
  );
  const buttonColor = useColorModeValue('brand.600', 'brand.200');
  const buttonBg = useColorModeValue('brand.50', 'whiteAlpha.100');

  if (!explanation) {
    return null;
  }

  return (
    <>
      <Tooltip label={`Open guide for ${explanation.title}`} hasArrow>
        <Button
          aria-label={`Open guide for ${explanation.title}`}
          leftIcon={<Icon as={QuestionMarkCircleIcon} fontSize={'lg'} />}
          variant={'ghost'}
          color={buttonColor}
          bg={buttonBg}
          size={'sm'}
          onClick={onOpen}
          px={{ base: 2, md: 3 }}
        >
          <Text as={'span'} display={{ base: 'none', md: 'inline' }}>
            Guide
          </Text>
        </Button>
      </Tooltip>

      <ExplanationModal
        explanation={explanation}
        isOpen={isOpen}
        onClose={onClose}
      />
    </>
  );
}

interface ExplanationModalProps {
  explanation: DashboardExplanation;
  isOpen: boolean;
  onClose: () => void;
}

function ExplanationModal({
  explanation,
  isOpen,
  onClose,
}: ExplanationModalProps) {
  const headerBg = useColorModeValue('brand.600', 'gray.800');
  const headerColor = useColorModeValue('white', 'gray.50');
  const bodyBg = useColorModeValue('gray.50', 'gray.900');
  const panelBg = useColorModeValue('white', 'gray.800');
  const panelBorder = useColorModeValue('gray.200', 'gray.700');
  const mutedColor = useColorModeValue('gray.600', 'gray.300');
  const iconBg = useColorModeValue('brand.50', 'whiteAlpha.100');
  const iconColor = useColorModeValue('brand.600', 'brand.200');

  return (
    <Modal
      isOpen={isOpen}
      onClose={onClose}
      size={'4xl'}
      scrollBehavior={'inside'}
    >
      <ModalOverlay bg={'blackAlpha.500'} backdropFilter={'blur(4px)'} />
      <ModalContent overflow={'hidden'} mx={3}>
        <ModalHeader bg={headerBg} color={headerColor} pr={12}>
          <HStack align={'start'} spacing={3}>
            <Box
              bg={'whiteAlpha.200'}
              borderRadius={'full'}
              h={10}
              w={10}
              display={'grid'}
              placeItems={'center'}
              flexShrink={0}
            >
              <Icon as={InformationCircleIcon} fontSize={'2xl'} />
            </Box>
            <Div>
              <Text fontSize={{ base: 'lg', md: 'xl' }} fontWeight={'bold'}>
                {explanation.title}
              </Text>
              <Text fontSize={'sm'} fontWeight={'normal'} opacity={0.9} mt={1}>
                Context guide for this dashboard area
              </Text>
            </Div>
          </HStack>
        </ModalHeader>
        <ModalCloseButton color={headerColor} />
        <ModalBody bg={bodyBg} py={5}>
          <Stack spacing={4}>
            <Box
              bg={panelBg}
              border={'1px solid'}
              borderColor={panelBorder}
              borderRadius={'lg'}
              p={4}
            >
              <HStack align={'start'} spacing={3}>
                <Box
                  bg={iconBg}
                  color={iconColor}
                  borderRadius={'full'}
                  h={9}
                  w={9}
                  display={'grid'}
                  placeItems={'center'}
                  flexShrink={0}
                >
                  <Icon as={LightBulbIcon} fontSize={'xl'} />
                </Box>
                <Div>
                  <Text fontWeight={'semibold'} mb={1}>
                    What this page is for
                  </Text>
                  <Text color={mutedColor} lineHeight={'1.7'}>
                    {explanation.summary}
                  </Text>
                </Div>
              </HStack>
            </Box>

            <SimpleGrid columns={{ base: 1, lg: 2 }} spacing={4}>
              <GuideSection
                title={'Use This Area To'}
                items={explanation.whenToUse}
              />
              <GuideSection
                title={'Recommended Workflow'}
                items={explanation.steps}
                ordered
              />
            </SimpleGrid>

            <GuideSection title={'Important Notes'} items={explanation.tips} />

            {explanation.related && explanation.related.length > 0 && (
              <Box
                bg={panelBg}
                border={'1px solid'}
                borderColor={panelBorder}
                borderRadius={'lg'}
                p={4}
              >
                <Text fontWeight={'semibold'} mb={3}>
                  Related Areas
                </Text>
                <HStack spacing={2} flexWrap={'wrap'}>
                  {explanation.related.map((item) => (
                    <Badge
                      key={item}
                      colorScheme={'brand'}
                      variant={'subtle'}
                      px={2}
                      py={1}
                      borderRadius={'md'}
                    >
                      {item}
                    </Badge>
                  ))}
                </HStack>
              </Box>
            )}
          </Stack>
        </ModalBody>
        <ModalFooter
          bg={bodyBg}
          borderTop={'1px solid'}
          borderColor={panelBorder}
        >
          <Button colorScheme={'brand'} size={'sm'} onClick={onClose}>
            Close Guide
          </Button>
        </ModalFooter>
      </ModalContent>
    </Modal>
  );
}

interface GuideSectionProps {
  title: string;
  items: string[];
  ordered?: boolean;
}

function GuideSection({ title, items, ordered = false }: GuideSectionProps) {
  const panelBg = useColorModeValue('white', 'gray.800');
  const panelBorder = useColorModeValue('gray.200', 'gray.700');
  const markerBg = useColorModeValue('brand.50', 'whiteAlpha.100');
  const markerColor = useColorModeValue('brand.600', 'brand.200');

  return (
    <Box
      bg={panelBg}
      border={'1px solid'}
      borderColor={panelBorder}
      borderRadius={'lg'}
      p={4}
      h={'full'}
    >
      <Text fontWeight={'semibold'} mb={3}>
        {title}
      </Text>
      <Ul listStyleType={'none'} m={0} p={0}>
        {items.map((item, index) => (
          <Li key={item} display={'flex'} gap={3} mb={3} _last={{ mb: 0 }}>
            <Box
              bg={markerBg}
              color={markerColor}
              borderRadius={'full'}
              h={6}
              minW={6}
              display={'grid'}
              placeItems={'center'}
              fontSize={'xs'}
              fontWeight={'bold'}
              mt={'1px'}
            >
              {ordered ? index + 1 : 'i'}
            </Box>
            <Text lineHeight={'1.65'}>{item}</Text>
          </Li>
        ))}
      </Ul>
    </Box>
  );
}
