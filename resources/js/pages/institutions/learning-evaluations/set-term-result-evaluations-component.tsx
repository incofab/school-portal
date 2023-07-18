import React, { useState } from 'react';
import {
  Checkbox,
  Divider,
  FormControl,
  Input,
  Text,
  VStack,
} from '@chakra-ui/react';
import useWebForm from '@/hooks/use-web-form';
import { preventNativeSubmit, range } from '@/util/util';
import { LearningEvaluation, TermResult } from '@/types/models';
import { FormButton } from '@/components/buttons';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import FormControlBox from '@/components/forms/form-control-box';
import { LearningEvaluationDomainType } from '@/types/types';
import MySelect from '@/components/dropdown-select/my-select';
import { Div } from '@/components/semantic';

interface Props {
  termResult: TermResult;
  learningEvaluations?: LearningEvaluation[];
}

export default function SetTermResultEvaluation({
  termResult,
  learningEvaluations,
}: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const [evaluation, setEvaluation] = useState(
    termResult.learning_evaluation ?? {}
  );

  const webForm = useWebForm({});

  const submit = async () => {
    const res = await webForm.submit((data, web) =>
      web.post(instRoute('set-term-result-learning-evaluation', [termResult]), {
        evaluations: Object.entries(evaluation).map(([key, val]) => ({
          learning_evaluation_id: key,
          value: val,
        })),
      })
    );

    if (!handleResponseToast(res)) return;

    // Inertia.visit(instRoute('learning-evaluations.index'));
  };

  if (!learningEvaluations) {
    return null;
  }

  return (
    <Div>
      <Text fontSize={'md'} fontWeight={'bold'}>
        Learning Evaluation
      </Text>
      <Divider mb={5} mt={1} />
      <VStack
        align={'stretch'}
        spacing={4}
        as={'form'}
        onSubmit={preventNativeSubmit(submit)}
      >
        {learningEvaluations.map((item) => {
          if (
            item.learning_evaluation_domain?.type ===
            LearningEvaluationDomainType.YesOrNo
          ) {
            return (
              <Checkbox
                key={'display-evalauation' + item.id}
                isChecked={Boolean(evaluation[item.id])}
                onChange={(e) =>
                  setEvaluation({
                    ...evaluation,
                    [item.id]: e.currentTarget.checked,
                  })
                }
              >
                {item.title}
              </Checkbox>
            );
          }
          if (
            item.learning_evaluation_domain?.type ===
            LearningEvaluationDomainType.Number
          ) {
            return (
              <FormControlBox
                form={webForm as any}
                formKey=""
                title={item.title}
                key={'display-evalauation' + item.id}
              >
                <MySelect
                  isMulti={false}
                  selectValue={evaluation[item.id]}
                  getOptions={() =>
                    range(1, item.learning_evaluation_domain?.max).map(
                      (num) => {
                        return {
                          label: String(num),
                          value: String(num),
                        };
                      }
                    )
                  }
                  onChange={(e: any) =>
                    setEvaluation({
                      ...evaluation,
                      [item.id]: e.value,
                    })
                  }
                />
              </FormControlBox>
            );
          }
          return (
            <Input
              key={'display-evalauation' + item.id}
              value={evaluation[item.id]}
              onChange={(e) =>
                setEvaluation({
                  ...evaluation,
                  [item.id]: e.currentTarget.value,
                })
              }
            />
          );
        })}
        <FormControl>
          <FormButton isLoading={webForm.processing} />
        </FormControl>
      </VStack>
    </Div>
  );
}
