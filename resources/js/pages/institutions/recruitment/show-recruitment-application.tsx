import React from 'react';
import {
  Badge,
  FormControl,
  HStack,
  Link,
  Textarea,
  VStack,
} from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import Dt from '@/components/dt';
import { BrandButton, FormButton } from '@/components/buttons';
import FormControlBox from '@/components/forms/form-control-box';
import EnumSelect from '@/components/dropdown-select/enum-select';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { RecruitmentApplication } from '@/types/models';
import { RecruitmentApplicationStatus, SelectOptionType } from '@/types/types';
import { preventNativeSubmit } from '@/util/util';
import { Inertia } from '@inertiajs/inertia';

interface Props {
  recruitmentApplication: RecruitmentApplication;
}

export default function ShowRecruitmentApplication({
  recruitmentApplication,
}: Props) {
  const { instRoute } = useInstitutionRoute();
  const { handleResponseToast } = useMyToast();
  const form = useWebForm({
    status: recruitmentApplication.status,
    review_note: recruitmentApplication.review_note ?? '',
  });

  async function submit(status?: string) {
    const res = await form.submit((data, web) =>
      web.post(
        instRoute('recruitment-applications.update-status', [
          recruitmentApplication.id,
        ]),
        { ...data, status: status ?? data.status }
      )
    );

    if (!handleResponseToast(res)) return;
    Inertia.reload({ only: ['recruitmentApplication'] });
  }

  const profileData: SelectOptionType<React.ReactNode>[] = [
    { label: 'Vacancy', value: recruitmentApplication.vacancy_post?.title },
    { label: 'Application No', value: recruitmentApplication.application_no },
    { label: 'Name', value: recruitmentApplication.name },
    { label: 'Email', value: recruitmentApplication.email },
    { label: 'Phone', value: recruitmentApplication.phone },
    { label: 'Current Role', value: recruitmentApplication.current_role },
    {
      label: 'Years of Experience',
      value: recruitmentApplication.years_of_experience,
    },
    {
      label: 'Highest Qualification',
      value: recruitmentApplication.highest_qualification,
    },
    recruitmentApplication.cv_url
      ? {
          label: 'CV',
          value: (
            <Link
              href={recruitmentApplication.cv_url}
              color="brand.500"
              isExternal
            >
              Open CV Link
            </Link>
          ),
        }
      : { label: 'CV', value: '' },
    {
      label: 'Cover Letter Link',
      value: recruitmentApplication.cover_letter_url ? (
        <Link
          href={recruitmentApplication.cover_letter_url}
          color="brand.500"
          isExternal
        >
          Open Cover Letter
        </Link>
      ) : (
        ''
      ),
    },
    {
      label: 'Portfolio',
      value: recruitmentApplication.portfolio_url ? (
        <Link
          href={recruitmentApplication.portfolio_url}
          color="brand.500"
          isExternal
        >
          Open Portfolio
        </Link>
      ) : (
        ''
      ),
    },
    { label: 'Available From', value: recruitmentApplication.available_from },
    { label: 'Reference', value: recruitmentApplication.reference },
  ];

  return (
    <DashboardLayout>
      <VStack align="stretch" spacing={4}>
        <HStack align="stretch">
          <Badge alignSelf="center" colorScheme="blue">
            {recruitmentApplication.status}
          </Badge>
          <BrandButton
            title="Shortlist"
            type="button"
            onClick={() => submit(RecruitmentApplicationStatus.Shortlisted)}
          />
          <BrandButton
            title="Mark Hired"
            type="button"
            onClick={() => submit(RecruitmentApplicationStatus.Hired)}
          />
          <BrandButton
            title="Decline"
            type="button"
            colorScheme="red"
            onClick={() => submit(RecruitmentApplicationStatus.Declined)}
          />
        </HStack>
        <Slab>
          <SlabHeading title={`${recruitmentApplication.name}'s Application`} />
          <SlabBody>
            <Dt contentData={profileData} spacing={4} labelWidth="170px" />
            {recruitmentApplication.cover_letter && (
              <FormControlBox
                form={form as any}
                title="Cover Letter"
                formKey="cover_letter"
              >
                <Textarea
                  value={recruitmentApplication.cover_letter}
                  readOnly
                  minH="180px"
                />
              </FormControlBox>
            )}
          </SlabBody>
        </Slab>
        <Slab>
          <SlabHeading title="Review" />
          <SlabBody>
            <VStack
              as="form"
              align="stretch"
              onSubmit={preventNativeSubmit(() => submit())}
            >
              <FormControlBox
                form={form as any}
                title="Status"
                formKey="status"
              >
                <EnumSelect
                  enumData={RecruitmentApplicationStatus}
                  selectValue={form.data.status}
                  onChange={(e: any) => form.setValue('status', e.value)}
                  required
                />
              </FormControlBox>
              <FormControlBox
                form={form as any}
                title="Review Note"
                formKey="review_note"
              >
                <Textarea
                  value={form.data.review_note}
                  onChange={(e) =>
                    form.setValue('review_note', e.currentTarget.value)
                  }
                />
              </FormControlBox>
              <FormControl>
                <FormButton isLoading={form.processing} />
              </FormControl>
            </VStack>
          </SlabBody>
        </Slab>
      </VStack>
    </DashboardLayout>
  );
}
