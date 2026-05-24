import React from 'react';
import {
  Alert,
  AlertDescription,
  AlertIcon,
  AlertTitle,
  Box,
  Button,
  Divider,
  HStack,
  Icon,
  ListItem,
  OrderedList,
  SimpleGrid,
  Stack,
  Text,
  UnorderedList,
  VStack,
} from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { LinkButton } from '@/components/buttons';
import useInstitutionRoute from '@/hooks/use-institution-route';
import {
  ArrowDownTrayIcon,
  PlayCircleIcon,
  ServerStackIcon,
} from '@heroicons/react/24/outline';

interface Props {
  videoUrl: string;
  downloadUrl: string;
}

export default function OfflineCbtSetupGuide({ videoUrl, downloadUrl }: Props) {
  const { instRoute } = useInstitutionRoute();

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading
          title="Offline CBT Setup Guide"
          rightElement={
            <LinkButton
              href={instRoute('events.index')}
              title="Back to Events"
            />
          }
        />
        <SlabBody>
          <VStack align="stretch" spacing={6}>
            <Alert status="success" variant="left-accent" borderRadius="md">
              <AlertIcon />
              <Box>
                <AlertTitle>
                  Run CBT with little or no student internet.
                </AlertTitle>
                <AlertDescription>
                  Only the server computer needs internet for setup, event
                  syncing, and result upload. Student devices stay on the same
                  local network and write the exam fully offline.
                </AlertDescription>
              </Box>
            </Alert>

            <SimpleGrid columns={{ base: 1, lg: 3 }} spacing={4}>
              <GuideCard
                icon={PlayCircleIcon}
                title="Watch the video tutorial"
                description="Follow the complete walkthrough on YouTube if you want to see the setup performed step by step."
                action={
                  <Button
                    as="a"
                    href={videoUrl}
                    target="_blank"
                    rel="noreferrer"
                    colorScheme="red"
                    size="sm"
                  >
                    Open Video
                  </Button>
                }
              />
              <GuideCard
                icon={ArrowDownTrayIcon}
                title="Download the offline application"
                description="Get the EduManager offline CBT package before you begin the installation on the server computer."
                action={
                  <Button
                    as="a"
                    href={downloadUrl}
                    colorScheme="brand"
                    size="sm"
                  >
                    Download App
                  </Button>
                }
              />
              <GuideCard
                icon={ServerStackIcon}
                title="Use one dedicated server"
                description="Choose one reliable computer for the offline server and keep it running throughout the examination period."
              />
            </SimpleGrid>

            <Section
              title="What You Need Before You Start"
              content={
                <UnorderedList spacing={2} pl={4}>
                  <ListItem>
                    Connect all computers or devices to the same local network
                    using a router, Wi-Fi, or Ethernet.
                  </ListItem>
                  <ListItem>
                    Designate one stronger computer as the server. A minimum of
                    8GB RAM is recommended.
                  </ListItem>
                  <ListItem>
                    Ensure only the server has internet during installation,
                    event sync, and result upload.
                  </ListItem>
                  <ListItem>
                    Create the CBT event first on the online EduManager portal,
                    including students, subjects, questions, duration, and event
                    settings.
                  </ListItem>
                </UnorderedList>
              }
            />

            <Section
              title="Server Setup"
              content={
                <OrderedList spacing={4} pl={4}>
                  <ListItem>
                    <Text fontWeight="semibold">
                      Download the offline application
                    </Text>
                    <Text>
                      Open the download link above and save the file. If your
                      computer shows a security warning, continue when you are
                      sure the download is from EduManager.
                    </Text>
                  </ListItem>
                  <ListItem>
                    <Text fontWeight="semibold">
                      Extract and place the folder correctly
                    </Text>
                    <Text>
                      Extract the downloaded file, rename the extracted folder
                      to <code>zam</code>, then move it to <code>C:\zam</code>.
                      Use that exact folder name.
                    </Text>
                  </ListItem>
                  <ListItem>
                    <Text fontWeight="semibold">
                      Start the local server services
                    </Text>
                    <Text>
                      Open <code>C:\zam</code>, go into <code>Controls</code>,
                      and run <code>ZAM Control</code>. Start every required
                      service until they show green and remain stable.
                    </Text>
                  </ListItem>
                  <ListItem>
                    <Text fontWeight="semibold">
                      Complete first-time registration
                    </Text>
                    <Text>
                      On the server computer, visit{' '}
                      <code>http://localhost/cbt/public</code> and enter your
                      school name, admin email, and institution code from the
                      main EduManager portal.
                    </Text>
                  </ListItem>
                  <ListItem>
                    <Text fontWeight="semibold">Sync the CBT events</Text>
                    <Text>
                      Inside the offline app, open the sync area, choose the
                      event or events you want to run, and download them to the
                      local server before students arrive.
                    </Text>
                  </ListItem>
                </OrderedList>
              }
            />

            <Section
              title="How Students Join the Exam"
              content={
                <OrderedList spacing={4} pl={4}>
                  <ListItem>
                    <Text fontWeight="semibold">
                      Find the server IP address
                    </Text>
                    <Text>
                      On the server, open Command Prompt, run{' '}
                      <code>ipconfig</code>, and note the IPv4 address, for
                      example <code>192.168.1.100</code>.
                    </Text>
                  </ListItem>
                  <ListItem>
                    <Text fontWeight="semibold">
                      Open the exam page on student devices
                    </Text>
                    <Text>
                      On each student computer connected to the same network,
                      open a browser and visit{' '}
                      <code>http://[SERVER-IP]/cbt/public/examiner</code>.
                    </Text>
                  </ListItem>
                  <ListItem>
                    <Text fontWeight="semibold">
                      Start with event code and student ID
                    </Text>
                    <Text>
                      Students enter the event code given by the examiner and
                      their EduManager student ID to begin the exam.
                    </Text>
                  </ListItem>
                  <ListItem>
                    <Text fontWeight="semibold">Complete and submit</Text>
                    <Text>
                      Students can move across subjects, answer with the mouse
                      or keyboard shortcuts, and submit when done. Responses are
                      saved locally on the server.
                    </Text>
                  </ListItem>
                </OrderedList>
              }
            />

            <Section
              title="After the Exam"
              content={
                <OrderedList spacing={4} pl={4}>
                  <ListItem>
                    <Text fontWeight="semibold">Evaluate the event</Text>
                    <Text>
                      Open the event in the offline app, review participants,
                      and run evaluation after all students have submitted.
                    </Text>
                  </ListItem>
                  <ListItem>
                    <Text fontWeight="semibold">
                      Upload results back online
                    </Text>
                    <Text>
                      Use the upload action in the offline app to send results
                      back to the main EduManager portal for reporting,
                      rankings, and analysis.
                    </Text>
                  </ListItem>
                </OrderedList>
              }
            />

            <Section
              title="Best-Practice Tips"
              content={
                <UnorderedList spacing={2} pl={4}>
                  <ListItem>
                    Test the full setup with a small group before the main exam
                    day.
                  </ListItem>
                  <ListItem>
                    Keep the server computer and ZAM Control running throughout
                    the exam.
                  </ListItem>
                  <ListItem>
                    Use a dedicated or higher-spec server for larger candidate
                    numbers.
                  </ListItem>
                  <ListItem>
                    Confirm every student device can open the server exam URL
                    before starting.
                  </ListItem>
                  <ListItem>
                    Sync events again after any important online exam changes
                    and before the exam begins.
                  </ListItem>
                </UnorderedList>
              }
            />

            <Divider />

            <Alert status="warning" borderRadius="md">
              <AlertIcon />
              <Box>
                <AlertTitle>Quick reminder</AlertTitle>
                <AlertDescription>
                  The offline app depends on the institution code from your
                  EduManager portal and a working local network. Prepare those
                  first, then use the video or download button above if you need
                  the full installation walkthrough.
                </AlertDescription>
              </Box>
            </Alert>

            <HStack spacing={3} flexWrap="wrap">
              <Button
                as="a"
                href={downloadUrl}
                colorScheme="brand"
                leftIcon={<Icon as={ArrowDownTrayIcon} />}
              >
                Download Offline Application
              </Button>
              <Button
                as="a"
                href={videoUrl}
                target="_blank"
                rel="noreferrer"
                variant="outline"
                colorScheme="red"
                leftIcon={<Icon as={PlayCircleIcon} />}
              >
                Watch Video Tutorial
              </Button>
              <LinkButton
                href={instRoute('events.index')}
                title="Return to Events"
              />
            </HStack>
          </VStack>
        </SlabBody>
      </Slab>
    </DashboardLayout>
  );
}

function Section({
  title,
  content,
}: {
  title: string;
  content: React.ReactNode;
}) {
  return (
    <Box>
      <Text fontSize="lg" fontWeight="bold" mb={2}>
        {title}
      </Text>
      <Box
        borderWidth="1px"
        borderRadius="md"
        borderColor="gray.200"
        p={4}
        bg="white"
      >
        {content}
      </Box>
    </Box>
  );
}

function GuideCard({
  icon,
  title,
  description,
  action,
}: {
  icon: any;
  title: string;
  description: string;
  action?: React.ReactNode;
}) {
  return (
    <Box borderWidth="1px" borderRadius="md" borderColor="gray.200" p={4}>
      <Stack spacing={3} align="flex-start">
        <Box bg="brand.50" color="brand.600" borderRadius="full" p={2}>
          <Icon as={icon} boxSize={6} />
        </Box>
        <Text fontWeight="bold">{title}</Text>
        <Text color="gray.600">{description}</Text>
        {action}
      </Stack>
    </Box>
  );
}
