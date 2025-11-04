
import { DashboardData } from '@/types/dashboard';
import React from 'react';
import { SimpleGrid, Box, Text, useColorModeValue } from '@chakra-ui/react';

interface StatCardProps {
  title: string;
  stat: string;
}

function StatCard({ title, stat }: StatCardProps) {
  return (
    <Box
      p={5}
      shadow="md"
      borderWidth="1px"
      borderColor={useColorModeValue('gray.200', 'gray.500')}
      rounded="lg"
      background={useColorModeValue('white', 'gray.700')}
    >
      <Text fontWeight="bold" fontSize="xl">
        {stat}
      </Text>
      <Text fontSize="sm">{title}</Text>
    </Box>
  );
}

export default function DashboardStats({ data }: { data: DashboardData }) {
  const stats = [
    { title: 'Subjects', stat: data.num_subjects.toString() },
    { title: 'Students', stat: data.num_students.toString() },
    { title: 'Staff', stat: data.num_staff.toString() },
    { title: 'Classes', stat: data.num_classes.toString() },
  ];

  return (
    <SimpleGrid columns={{ base: 1, sm: 2, md: 4 }} spacing={5} my={5}>
      {stats.map(data => (
        <StatCard key={data.title} {...data} />
      ))}
    </SimpleGrid>
  );
}
