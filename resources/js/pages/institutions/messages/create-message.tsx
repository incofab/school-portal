import React from 'react';
import { Classification, ClassificationGroup } from '@/types/models';
import DashboardLayout from '@/layout/dashboard-layout';
import { MessageRecipientType, NotificationChannelsType } from '@/types/types';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { FormButton, LinkButton } from '@/components/buttons';
import useInstitutionRoute from '@/hooks/use-institution-route';
import {
  Checkbox,
  FormControl,
  SimpleGrid,
  Textarea,
  VStack,
} from '@chakra-ui/react';
import FormControlBox from '@/components/forms/form-control-box';
import useWebForm from '@/hooks/use-web-form';
import { generateRandomString, preventNativeSubmit } from '@/util/util';
import ClassificationSelect from '@/components/selectors/classification-select';
import AssociationSelect from '@/components/selectors/association-select';
import ClassificationGroupSelect from '@/components/selectors/classification-group-select';
import EnumSelect from '@/components/dropdown-select/enum-select';
import InputForm from '@/components/forms/input-form';
import useMyToast from '@/hooks/use-my-toast';
import { Inertia } from '@inertiajs/inertia';
import { Div } from '@/components/semantic';

interface Props {
  classifications: Classification[];
  classificationGroups: ClassificationGroup[];
  associations: Classification[];
}

export default function CreateMessage({
  classifications,
  classificationGroups,
  associations,
}: Props) {
  const { instRoute } = useInstitutionRoute();
  const { handleResponseToast } = useMyToast();
  const webForm = useWebForm({
    messageable_type: '',
    messageable_id: '',
    to_guardians: true,
    receivers: '',
    channel: NotificationChannelsType.Email,
    reference: '',
    subject: '',
    message: '',
  });

  const submit = async () => {
    const res = await webForm.submit((data, web) =>
      web.post(instRoute('messages.store'), {
        ...data,
        reference: generateRandomString(16),
      })
    );

    if (!handleResponseToast(res)) return;

    Inertia.visit(instRoute('messages.index'));
  };

  function toCustomContact() {
    return webForm.data.messageable_type == '';
  }

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading
          title="Send Messages"
          rightElement={
            <LinkButton title="new" href={instRoute('messages.create')} />
          }
        />
        <SlabBody>
          <VStack
            spacing={4}
            as={'form'}
            onSubmit={preventNativeSubmit(submit)}
            align={'stretch'}
          >
            <Div
              border={'1px solid'}
              borderColor={'gray.300'}
              p={3}
              borderRadius={'md'}
            >
              <SimpleGrid columns={{ base: 1, md: 2 }} spacing={4} mb={3}>
                {[
                  {
                    label: 'Everyone',
                    value: MessageRecipientType.Institution,
                  },
                  {
                    label: 'Class',
                    value: MessageRecipientType.Classification,
                  },
                  {
                    label: 'Divisions',
                    value: MessageRecipientType.Association,
                  },
                  { label: 'Enter Email/Phone No', value: '' },
                  // {
                  //   label: 'Class Group',
                  //   value: MessageRecipientType.ClassificationGroup,
                  // },
                ].map((item) => (
                  <Checkbox
                    key={item.label}
                    isChecked={webForm.data.messageable_type === item.value}
                    onChange={(e) => {
                      webForm.setData({
                        ...webForm.data,
                        messageable_type: e.currentTarget.checked
                          ? item.value
                          : '',
                        messageable_id: '',
                        receivers: '',
                      });
                    }}
                    size={'md'}
                    colorScheme="brand"
                  >
                    {item.label}
                  </Checkbox>
                ))}
              </SimpleGrid>

              {webForm.data.messageable_type ===
                MessageRecipientType.Classification && (
                <FormControlBox
                  title="Class"
                  form={webForm as any}
                  formKey="messageable_id"
                  isRequired
                >
                  <ClassificationSelect
                    classifications={classifications}
                    onChange={(e: any) =>
                      webForm.setValue('messageable_id', e.value)
                    }
                  />
                </FormControlBox>
              )}
              {webForm.data.messageable_type ===
                MessageRecipientType.Association && (
                <FormControlBox
                  title="Divisions"
                  form={webForm as any}
                  formKey="messageable_id"
                  isRequired
                >
                  <AssociationSelect
                    associations={associations}
                    onChange={(e: any) =>
                      webForm.setValue('messageable_id', e.value)
                    }
                  />
                </FormControlBox>
              )}
              {webForm.data.messageable_type ===
                MessageRecipientType.ClassificationGroup && (
                <FormControlBox
                  title="Class Group"
                  form={webForm as any}
                  formKey="messageable_id"
                  isRequired
                >
                  <ClassificationGroupSelect
                    classificationGroups={classificationGroups}
                    onChange={(e: any) =>
                      webForm.setValue('messageable_id', e.value)
                    }
                  />
                </FormControlBox>
              )}

              {toCustomContact() && (
                <FormControlBox
                  form={webForm as any}
                  title="Receivers contacts"
                  formKey="receivers"
                  isRequired
                >
                  <Textarea
                    onChange={(e) =>
                      webForm.setValue('receivers', e.currentTarget.value)
                    }
                    noOfLines={3}
                    placeholder="Phone/Email of receivers separated by comma"
                  >
                    {webForm.data.receivers}
                  </Textarea>
                </FormControlBox>
              )}
            </Div>

            <FormControlBox
              isRequired
              form={webForm as any}
              title="Notification Channel"
              formKey="channel"
            >
              <EnumSelect
                enumData={NotificationChannelsType}
                onChange={(e: any) => webForm.setValue('channel', e.value)}
                selectValue={webForm.data.channel}
                required
              />
            </FormControlBox>

            {webForm.data.channel === NotificationChannelsType.Email && (
              <InputForm
                form={webForm as any}
                formKey="subject"
                title="Subject"
              />
            )}

            <FormControlBox
              form={webForm as any}
              title="Message"
              formKey="message"
              isRequired
            >
              <Textarea
                onChange={(e) =>
                  webForm.setValue('message', e.currentTarget.value)
                }
                noOfLines={3}
              >
                {webForm.data.message}
              </Textarea>
            </FormControlBox>

            {!toCustomContact() && (
              <Checkbox
                isChecked={webForm.data.to_guardians}
                onChange={(e) => {
                  webForm.setValue('to_guardians', e.currentTarget.checked);
                }}
                size={'md'}
                colorScheme="brand"
              >
                Send to Guardians
              </Checkbox>
            )}

            <FormControl>
              <FormButton
                isLoading={webForm.processing}
                isDisabled={webForm.processing}
              />
            </FormControl>
          </VStack>
        </SlabBody>
      </Slab>
    </DashboardLayout>
  );
}
