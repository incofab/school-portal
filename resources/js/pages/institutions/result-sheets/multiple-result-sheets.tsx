import { BoxProps } from '@chakra-ui/react';
import React, { PropsWithChildren } from 'react';
import { Div } from '@/components/semantic';
import '@/../../public/style/result-sheet.css';
import '@/style/template-5.css';
import { ResultProps } from '@/util/result-util';
import Template1 from './template-1';
import Template2 from './template-2';
import Template3 from './template-3';
import Template4 from './template-4';
import Template5 from './template-5';
import Template6 from './template-6';
import Template7 from './template-7';

interface Props {
  results: ResultProps[];
  resultTemplete: string;
}
export default function MultipleResultSheets({
  results,
  resultTemplete,
}: Props) {
  const template = resultTemplete.replaceAll('-', '').toLowerCase();

  function A4Page({ children, ...props }: PropsWithChildren & BoxProps) {
    return (
      <Div className="a4-page" {...props}>
        <Div>{children}</Div>
      </Div>
    );
  }

  return (
    <Div>
      {results.map((result, index) => (
        <A4Page key={index}>
          <DynamicComponent
            name={template as keyof typeof components}
            props={result}
          />
        </A4Page>
      ))}
    </Div>
  );
}

// Mapping of components
const components = {
  template1: Template1,
  template2: Template2,
  template3: Template3,
  template4: Template4,
  template5: Template5,
  template6: Template6,
  template7: Template7,
};

type ComponentName = keyof typeof components;

interface DynamicComponentProps {
  name: string; //T;
  props: ResultProps;
}

function DynamicComponent<T extends ComponentName>({
  name,
  props,
}: DynamicComponentProps) {
  const Component = components[name as keyof typeof components];
  return <Component {...props} />; // {...props} />;
}
