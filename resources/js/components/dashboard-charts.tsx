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
  SimpleGrid,
  Text,
  useColorModeValue,
  VStack,
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
import { Div } from './semantic';

const ChartBox: React.FC<{ title: string; children: React.ReactNode }> = ({
  title,
  children,
}) => (
  <Box
    border={'solid'}
    borderWidth={1}
    borderColor={useColorModeValue('gray.200', 'gray.500')}
    rounded={'lg'}
    boxShadow={'0px 2px 6px rgba(0, 0, 0, 0.1)'}
    background={useColorModeValue('white', 'gray.700')}
    p={4}
  >
    <Text fontSize="lg" fontWeight="bold" mb={4}>
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
        backgroundColor: ['rgba(54, 162, 235, 0.6)', 'rgba(255, 99, 132, 0.6)'],
      },
    ],
  };

  const feePaymentData = {
    labels: data.fee_payments.map((d) => d.month),
    datasets: [
      {
        label: 'Fee Payments (in thousands)',
        data: data.fee_payments.map((d) => d.total),
        backgroundColor: 'rgba(75, 192, 192, 0.6)',
      },
    ],
  };

  const classStudentData = {
    labels: data.students_per_class.map((d) => d.title),
    datasets: [
      {
        label: 'Male',
        data: data.students_per_class.map((d) => d.male_students_count),
        backgroundColor: 'rgba(54, 162, 235, 0.7)',
      },
      {
        label: 'Female',
        data: data.students_per_class.map((d) => d.female_students_count),
        backgroundColor: 'rgba(255, 99, 132, 0.7)',
      },
      {
        label: 'Total',
        data: data.students_per_class.map((d) => d.students_count),
        backgroundColor: 'rgba(153, 102, 255, 0.7)',
      },
    ],
  };

  return (
    <SimpleGrid columns={{ base: 1, md: 2 }} spacing={6} my={6}>
      <VStack spacing={2} w={'full'} align={'stretch'}>
        <ChartBox title="Student Population Growth (Monthly)">
          <Line data={studentPopulationMonthGrowthData} />
        </ChartBox>
        <ChartBox title="Student Population Growth (Yearly)">
          <Line data={studentPopulationYearGrowthData} />
        </ChartBox>
      </VStack>
      <ChartBox title="Student Gender Distribution">
        <Pie data={genderDistributionData} />
      </ChartBox>
      <ChartBox title="Recent Fee Payments">
        <Bar data={feePaymentData} />
      </ChartBox>
      <ChartBox title="Students per Class (by Gender)">
        <Bar data={classStudentData} />
      </ChartBox>
    </SimpleGrid>
  );
}
