import React from 'react';
import {
  Avatar,
  Box,
  Container,
  FormControl,
  HStack,
  SimpleGrid,
  Text,
  Textarea,
  VStack,
  useColorModeValue,
} from '@chakra-ui/react';
import { Inertia } from '@inertiajs/inertia';
import { FormButton } from '@/components/buttons';
import FormControlBox from '@/components/forms/form-control-box';
import InputForm from '@/components/forms/input-form';
import useInstitutionRoute from '@/hooks/use-institution-route';
import useMyToast from '@/hooks/use-my-toast';
import useWebForm from '@/hooks/use-web-form';
import { Institution, VacancyPost } from '@/types/models';
import { generateRandomString, preventNativeSubmit } from '@/util/util';

interface Props {
  institution: Institution;
  vacancyPost: VacancyPost;
}

export default function CreateRecruitmentApplication({
  institution,
  vacancyPost,
}: Props) {
  const { instRoute } = useInstitutionRoute();
  const { handleResponseToast } = useMyToast();
  const form = useWebForm({
    vacancy_post_id: vacancyPost.id,
    reference: String(institution.id) + generateRandomString(16),
    first_name: '',
    last_name: '',
    other_names: '',
    email: '',
    phone: '',
    current_role: '',
    years_of_experience: '',
    highest_qualification: '',
    cv_url: '',
    cover_letter: '',
    cover_letter_url: '',
    portfolio_url: '',
    available_from: '',
  });

  async function submit() {
    const res = await form.submit((data, web) =>
      web.post(
        instRoute('recruitment-applications.store', [vacancyPost.id]),
        data
      )
    );

    if (!handleResponseToast(res)) return;

    Inertia.visit(
      instRoute('recruitment-applications.success', [
        res.data.recruitmentApplication.id,
      ])
    );
  }

  return (
    <Box bg={useColorModeValue('gray.50', 'gray.900')} minH="100vh">
      <Box bg={useColorModeValue('white', 'gray.800')} borderBottomWidth={1}>
        <Container maxW="980px" py={5}>
          <HStack spacing={4}>
            <Avatar src={institution.photo} name={institution.name} />
            <Box>
              <Text fontWeight="bold" fontSize="xl">
                {institution.name}
              </Text>
              <Text color="gray.600">{vacancyPost.title}</Text>
            </Box>
          </HStack>
        </Container>
      </Box>
      <Container maxW="980px" py={{ base: 6, md: 10 }}>
        <Box
          bg={useColorModeValue('white', 'gray.800')}
          borderWidth={1}
          rounded="lg"
          p={{ base: 5, md: 8 }}
        >
          <Text fontSize={{ base: '2xl', md: '3xl' }} fontWeight="bold">
            Recruitment Application
          </Text>
          <Text color="gray.600" mt={2}>
            Submit links to your CV, cover letter, and portfolio where
            available. File uploads are not required.
          </Text>
          <VStack
            spacing={4}
            align="stretch"
            as="form"
            mt={6}
            onSubmit={preventNativeSubmit(submit)}
          >
            <SimpleGrid columns={{ base: 1, md: 2 }} gap={4}>
              <InputForm
                form={form as any}
                formKey="first_name"
                title="First Name"
              />
              <InputForm
                form={form as any}
                formKey="last_name"
                title="Last Name"
              />
              <InputForm
                form={form as any}
                formKey="other_names"
                title="Other Names"
              />
              <InputForm
                form={form as any}
                formKey="email"
                title="Email"
                type="email"
              />
              <InputForm form={form as any} formKey="phone" title="Phone" />
              <InputForm
                form={form as any}
                formKey="current_role"
                title="Current Role"
              />
              <InputForm
                form={form as any}
                formKey="years_of_experience"
                title="Years of Experience"
                type="number"
              />
              <InputForm
                form={form as any}
                formKey="highest_qualification"
                title="Highest Qualification"
              />
              <InputForm
                form={form as any}
                formKey="cv_url"
                title="CV Link [Optional]"
                type="url"
              />
              <InputForm
                form={form as any}
                formKey="cover_letter_url"
                title="Cover Letter Link"
                type="url"
              />
              <InputForm
                form={form as any}
                formKey="portfolio_url"
                title="Portfolio Link"
                type="url"
              />
              <InputForm
                form={form as any}
                formKey="available_from"
                title="Available From"
                type="date"
              />
            </SimpleGrid>
            <FormControlBox
              form={form as any}
              title="Cover Letter"
              formKey="cover_letter"
            >
              <Textarea
                minH="180px"
                value={form.data.cover_letter}
                onChange={(e) =>
                  form.setValue('cover_letter', e.currentTarget.value)
                }
              />
            </FormControlBox>
            <FormControl>
              <FormButton
                isLoading={form.processing}
                title="Submit Application"
              />
            </FormControl>
          </VStack>
        </Box>
      </Container>
    </Box>
  );
}
