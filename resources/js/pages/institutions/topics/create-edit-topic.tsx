import React, { useState } from 'react';
import { Checkbox, FormControl, Text, VStack } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import useWebForm from '@/hooks/use-web-form';
import { preventNativeSubmit } from '@/util/util';
import { Inertia } from '@inertiajs/inertia';
import { User, Topic } from '@/types/models';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import CenteredBox from '@/components/centered-box';
import { FormButton } from '@/components/buttons';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import FormControlBox from '@/components/forms/form-control-box';
import { Input } from '@chakra-ui/react';
import ClassificationGroupSelect from '@/components/selectors/classification-group-select';
import CourseSelect from '@/components/selectors/course-select';
import TopicSelect from '@/components/selectors/topic-select';
import { InstitutionUserType, TermType } from '@/types/types';
import useSharedProps from '@/hooks/use-shared-props';
import EnumSelect from '@/components/dropdown-select/enum-select';
import StaffSelect from '@/components/selectors/staff-select';
import useIsAdmin from '@/hooks/use-is-admin';
import TinyMceEditor from '@/components/tinymce-editor';
import FileDropper from '@/components/file-dropper';
import FileObject from '@/components/file-dropper/file-object';
import { FileDropperType } from '@/components/file-dropper/common';

interface Props {
  user?: User;
  topic?: Topic;
  parentTopics: Topic[];
}

export default function CreateOrUpdateTopic({
  user,
  topic,
  parentTopics,
}: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const isAdmin = useIsAdmin();
  const { currentTerm } = useSharedProps();
  const [shouldBeDisabled, setShouldBeDisabled] = useState(
    topic?.parent_topic_id ? true : false
  );
  const [lessonPlanFiles, setLessonPlanFiles] = useState<FileObject[]>([]);

  const webForm = useWebForm({
    term: topic ? topic.scheme_of_works?.[0]?.term : currentTerm,
    week_number: topic ? topic.scheme_of_works?.[0]?.week_number : '',
    title: topic ? topic.title : '',
    description: topic ? topic.description : '',
    classification_group_id: topic ? topic.classification_group_id : '',
    course_id: topic ? topic.course_id : '',
    user_id: user ? { label: user.full_name, value: user.id } : null,
    parent_topic_id: topic ? topic.parent_topic_id : '',
    is_used_by_institution_group: topic
      ? topic.institution_group_id !== null
        ? true
        : false
      : false,
  });

  const updateForm = (parentTopicId?: number | string | null) => {
    if (parentTopicId) {
      const selectedParentTopic = parentTopics.find(
        (topic) => topic.id === Number(parentTopicId)
      );

      webForm.setValue(
        'classification_group_id',
        String(selectedParentTopic?.classification_group_id)
      );
      webForm.setValue('course_id', String(selectedParentTopic?.course_id));
      setShouldBeDisabled(true);
    } else {
      webForm.setValue('classification_group_id', '');
      webForm.setValue('course_id', '');
      setShouldBeDisabled(false);
    }

    webForm.setValue(
      'parent_topic_id',
      parentTopicId ? String(parentTopicId) : ''
    );
    return parentTopicId ? String(parentTopicId) : '';
  };

  const submit = async () => {
    const res = await webForm.submit((data, web) => {
      const payload = { ...data, user_id: data.user_id?.value };

      if (lessonPlanFiles.length === 0) {
        return web.post(
          instRoute('inst-topics.store-or-update', topic ? [topic] : undefined),
          payload
        );
      }

      const formData = new FormData();

      Object.entries(payload).forEach(([key, value]) => {
        if (value === undefined || value === null || value === '') {
          return;
        }

        formData.append(
          key,
          typeof value === 'boolean' ? (value ? '1' : '0') : String(value)
        );
      });

      lessonPlanFiles.forEach((file) => {
        formData.append(
          'lesson_plan_files[]',
          file.file,
          file.getNameWithExtension()
        );
      });

      return web.post(
        instRoute('inst-topics.store-or-update', topic ? [topic] : undefined),
        formData,
        { headers: { 'Content-Type': 'multipart/form-data' } }
      );
    });

    if (!handleResponseToast(res)) {
      return;
    }
    Inertia.visit(instRoute('inst-topics.index'));
  };

  return (
    <DashboardLayout>
      <CenteredBox>
        <Slab>
          <SlabHeading title={`${topic ? 'Update' : 'Create'} Topic`} />
          <SlabBody>
            <VStack
              spacing={4}
              as={'form'}
              onSubmit={preventNativeSubmit(submit)}
            >
              {!topic && (
                <>
                  <FormControlBox
                    form={webForm as any}
                    title="Term"
                    formKey="term"
                    isRequired
                  >
                    <EnumSelect
                      selectValue={webForm.data.term}
                      enumData={TermType}
                      onChange={(e: any) => webForm.setValue('term', e?.value)}
                    />
                  </FormControlBox>

                  <FormControlBox
                    form={webForm as any}
                    title="Week Number"
                    formKey="week_number"
                    isRequired
                  >
                    <Input
                      type="number"
                      onChange={(e) =>
                        webForm.setValue('week_number', e.currentTarget.value)
                      }
                      value={webForm.data.week_number}
                      required
                    />
                  </FormControlBox>
                </>
              )}

              <FormControlBox
                form={webForm as any}
                formKey={'classification_group_id'}
                title="Class Group"
                isRequired
              >
                <ClassificationGroupSelect
                  selectValue={webForm.data.classification_group_id}
                  isMulti={false}
                  isClearable={true}
                  onChange={(e: any) =>
                    webForm.setValue('classification_group_id', e?.value)
                  }
                  required
                  isDisabled={shouldBeDisabled}
                />
              </FormControlBox>

              <FormControlBox
                form={webForm as any}
                title="Subject"
                formKey="course_id"
                isRequired
              >
                <CourseSelect
                  onChange={(e: any) => webForm.setValue('course_id', e?.value)}
                  selectValue={webForm.data.course_id}
                  isMulti={false}
                  isClearable={true}
                  required
                  isDisabled={shouldBeDisabled}
                />
              </FormControlBox>

              {isAdmin && !topic && (
                <>
                  <FormControlBox
                    title="Teacher"
                    form={webForm as any}
                    formKey="user_id"
                  >
                    <StaffSelect
                      value={webForm.data.user_id}
                      isClearable={true}
                      rolesIn={[
                        InstitutionUserType.Teacher,
                        InstitutionUserType.Admin,
                      ]}
                      onChange={(e) => webForm.setValue('user_id', e)}
                      isMulti={false}
                      required
                    />
                  </FormControlBox>
                </>
              )}

              <FormControlBox
                form={webForm as any}
                title="Parent Topic [Optional]"
                formKey="parent_topic_id"
              >
                <TopicSelect
                  topics={parentTopics}
                  onChange={(e: any) => {
                    updateForm(e?.value);
                  }}
                  selectValue={webForm.data.parent_topic_id}
                  isMulti={false}
                  isClearable={true}
                />
              </FormControlBox>

              <FormControlBox
                form={webForm as any}
                title="Title"
                formKey="title"
                isRequired
              >
                <Input
                  type="text"
                  onChange={(e) =>
                    webForm.setValue('title', e.currentTarget.value)
                  }
                  value={webForm.data.title}
                />
              </FormControlBox>

              <FormControlBox
                title="Description"
                form={webForm as any}
                formKey="description"
              >
                <TinyMceEditor
                  initialValue={topic?.description}
                  value={webForm.data.description}
                  onEditorChange={(description: string) =>
                    webForm.setValue('description', description)
                  }
                />
              </FormControlBox>

              <FormControl>
                <Checkbox
                  isChecked={webForm.data.is_used_by_institution_group}
                  onChange={(e) =>
                    webForm.setData({
                      ...webForm.data,
                      is_used_by_institution_group: e.currentTarget.checked,
                    })
                  }
                  size={'md'}
                  colorScheme="brand"
                >
                  Applies to entire Institution Group.
                </Checkbox>
              </FormControl>

              <FormControlBox
                title="Lesson Plan Files"
                form={webForm as any}
                formKey="lesson_plan_files"
              >
                <VStack align={'stretch'} spacing={2}>
                  <FileDropper
                    files={lessonPlanFiles}
                    onChange={setLessonPlanFiles}
                    accept={[FileDropperType.Media]}
                    multiple
                    canRename
                    maxSize={10 * 1024 * 1024}
                    isLoading={webForm.processing}
                  />
                  <Text fontSize={'sm'} color={'blackAlpha.700'}>
                    Files are attached to the lesson plan created for this
                    topic. On edit, files are attached to the first available
                    lesson plan for the topic.
                  </Text>
                </VStack>
              </FormControlBox>

              <FormControl>
                <FormButton isLoading={webForm.processing} />
              </FormControl>
            </VStack>
          </SlabBody>
        </Slab>
      </CenteredBox>
    </DashboardLayout>
  );
}
