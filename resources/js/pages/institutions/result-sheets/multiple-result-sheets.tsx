import { BoxProps } from '@chakra-ui/react';
import React, { PropsWithChildren } from 'react';
import { Div } from '@/components/semantic';
import { ResultProps } from '@/util/result-util';

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
        <A4Page key={result.termResult.id}>
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
  template1: React.lazy(() => import('./template-1')),
  template2: React.lazy(() => import('./template-2')),
  template3: React.lazy(() => import('./template-3')),
  template4: React.lazy(() => import('./template-4')),
  template5: React.lazy(() => import('./template-5')),
  template6: React.lazy(() => import('./template-6')),
  template7: React.lazy(() => import('./template-7')),
  template8: React.lazy(() => import('./template-8')),
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
