import React from 'react';
import { Line, Pie, Bar } from 'react-chartjs-2';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  ArcElement,
  BarElement,
} from 'chart.js';
import {
  Box,
  Flex,
  HStack,
  Icon,
  IconButton,
  SimpleGrid,
  Text,
  useColorModeValue,
  VStack,
  Tooltip as ChakraTooltip,
} from '@chakra-ui/react';

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  ArcElement,
  BarElement
);

import { DashboardData } from '@/types/dashboard';
import { ucFirst } from '@/util/util';
import { ArrowPathIcon } from '@heroicons/react/24/outline';
import { Inertia } from '@inertiajs/inertia';
import { InertiaLink } from '@inertiajs/inertia-react';

const ChartBox: React.FC<{
  title: string | React.ReactNode;
  children: React.ReactNode;
}> = ({ title, children }) => (
  <Box
    border={'solid'}
    borderWidth={1}
    borderColor={useColorModeValue('gray.200', 'gray.500')}
    rounded={'lg'}
    boxShadow={'0px 2px 6px rgba(0, 0, 0, 0.1)'}
    background={useColorModeValue('white', 'gray.700')}
    p={4}
  >
    <Text fontSize="lg" fontWeight="bold" mb={4} as={'div'}>
      {title}
    </Text>
    {children}
  </Box>
);

export default function DashboardCharts({ data }: { data: DashboardData }) {
  const studentPopulationYearGrowthData = {
    labels: data.student_population_year_growth.map((d) => d.year),
    datasets: [
      {
        label: 'Student Population Growth (Yearly)',
        data: data.student_population_year_growth.map((d) => d.count),
        fill: false,
        borderColor: 'rgb(75, 192, 192)',
        tension: 0.1,
      },
    ],
  };

  const studentPopulationMonthGrowthData = {
    labels: data.student_population_month_growth.map((d) => d.month),
    datasets: [
      {
        label: 'Student Population Growth (Monthly)',
        data: data.student_population_month_growth.map((d) => d.count),
        fill: false,
        borderColor: 'rgb(75, 192, 192)',
        tension: 0.1,
      },
    ],
  };

  const genderDistributionData = {
    labels: data.gender_distribution.map((d) => d.gender),
    datasets: [
      {
        label: 'Gender Distribution',
        data: data.gender_distribution.map((d) => d.count),
        backgroundColor: ['rgba(54, 162, 235, 0.8)', 'rgba(255, 99, 132, 0.8)'],
      },
    ],
  };

  const feePaymentData = {
    labels: data.fee_payments.map((d) => d.month),
    datasets: [
      {
        label: 'Fee Payments (in thousands)',
        data: data.fee_payments.map((d) => d.total),
        backgroundColor: 'rgba(75, 192, 192, 0.8)',
      },
    ],
  };

  const usersByRole = data.users_by_role
    ? {
        labels: Object.entries(data.users_by_role).map(([key]) => ucFirst(key)),
        datasets: [
          {
            label: 'School Size by Roles',
            data: Object.entries(data.users_by_role).map(([, value]) => value),
            backgroundColor: 'rgba(224, 5, 78, 0.8)',
          },
        ],
      }
    : null;

  const classStudentData = {
    labels: data.students_per_class.map((d) => d.title),
    datasets: [
      {
        label: 'Male',
        data: data.students_per_class.map((d) => d.male_students_count),
        backgroundColor: 'rgba(54, 162, 235, 0.8)',
      },
      {
        label: 'Female',
        data: data.students_per_class.map((d) => d.female_students_count),
        backgroundColor: 'rgba(255, 99, 132, 0.8)',
      },
      {
        label: 'Total',
        data: data.students_per_class.map((d) => d.students_count),
        backgroundColor: 'rgba(153, 102, 255, 0.8)',
      },
    ],
  };

  return (
    <>
      <Flex justifyContent="flex-end" alignItems="center">
        <ChakraTooltip label="Force Refresh Dashboard Statistics">
          <IconButton
            colorScheme="brand"
            aria-label="Force Refresh Dashboard Statistics"
            icon={<Icon as={ArrowPathIcon} />}
            variant={'ghost'}
            onClick={() => {
              const msg =
                'Are you sure you want to refresh the dashboard statistics?';
              if (window.confirm(msg)) {
                Inertia.visit(window.location.href + '?refresh=1', {
                  preserveScroll: true,
                });
              }
            }}
          />
        </ChakraTooltip>
      </Flex>
      <SimpleGrid columns={{ base: 1, md: 2 }} spacing={6} mt={0} mb={6}>
        <VStack spacing={2} w={'full'} align={'stretch'}>
          <ChartBox title="Student Population Growth (Monthly)">
            <Line data={studentPopulationMonthGrowthData} />
          </ChartBox>
          <ChartBox title="Student Population Growth (Yearly)">
            <Line data={studentPopulationYearGrowthData} />
          </ChartBox>
        </VStack>
        <ChartBox
          title={
            <HStack justify={'space-between'}>
              <Text>Students by Gender</Text>{' '}
              <Text>Total: {data.num_students}</Text>
            </HStack>
          }
        >
          <Pie data={genderDistributionData} />
        </ChartBox>
        <ChartBox title="Recent Fee Payments">
          <Bar data={feePaymentData} />
        </ChartBox>
        <ChartBox title="Students per Class">
          <Bar data={classStudentData} />
        </ChartBox>
        {usersByRole && (
          <ChartBox title="School Size by Roles">
            <Bar data={usersByRole} />
          </ChartBox>
        )}
      </SimpleGrid>
    </>
  );
}
