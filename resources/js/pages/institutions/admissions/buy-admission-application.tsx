import { Div } from '@/components/semantic';
import { Button, Icon, Text } from '@chakra-ui/react';
import React from 'react';
import { ArrowDownIcon } from '@heroicons/react/24/solid';
import { CurrencyDollarIcon } from '@heroicons/react/24/outline';
import { AdmissionApplication } from '@/types/models';
import { LabelText } from '@/components/result-helper-components';
import { formatAsCurrency } from '@/util/util';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';

interface Props {
  admissionApplication: AdmissionApplication;
}

export default function BuyAdmissionApplication({
  admissionApplication,
}: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const webForm = useWebForm({});

  async function submit() {
    const res = await webForm.submit((data, web) =>
      web.post(
        instRoute('admission-forms.buy', [
          admissionApplication.admission_form_id!,
          admissionApplication.id,
        ]),
        data
      )
    );

    if (!handleResponseToast(res)) return;

    window.location.href = res.data.authorization_url;
  }

  return (
    <Div background={'brand.50'} height={'100vh'}>
      <Div
        rounded={'md'}
        border={'1px solid'}
        borderColor={'green.600'}
        bg={'green.50'}
        textAlign={'center'}
        p={8}
      >
        <Text fontSize={'2xl'} color={'green.600'}>
          Complete your Registration
        </Text>
        <Div>
          <LabelText
            label="Name"
            text={`${admissionApplication.first_name} ${admissionApplication.last_name}`}
          />
          <LabelText
            label="Application Number"
            text={admissionApplication.appplication_no}
          />
        </Div>
        <Icon as={ArrowDownIcon} w={10} h={10} mt={6} />
        <Text mb={2} fontWeight={'semibold'}>
          {admissionApplication.admissionForm!.title}
        </Text>
        <Text mb={4} fontWeight={'bold'} color={'green.500'}>
          {formatAsCurrency(admissionApplication.admissionForm!.price)}
        </Text>
        <Button
          variant={'outline'}
          colorScheme="brand"
          leftIcon={<Icon as={CurrencyDollarIcon} />}
          mt={4}
          size={'sm'}
          onClick={(e) => window.close()}
        >
          Pay Now
        </Button>
      </Div>
    </Div>
  );
}
