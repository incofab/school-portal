import React from 'react';
import { Button, Icon } from '@chakra-ui/react';
import route from '@/util/route';
import { Student } from '@/types/models';
import { CloudArrowDownIcon } from '@heroicons/react/24/solid';

interface Props {
  path: string;
  student: Student;
}

export default function ShowPdfResult({ path, student }: Props) {
  return (
    <object data={path} type="application/pdf" width="100%" height="800px">
      <p style={{ textAlign: 'center' }}>
        <br />
        <br />
        Hi, click here to{' '}
        <Button
          as={'a'}
          href={route('show-pdf-result', { student, download: true })}
          colorScheme={'brand'}
          variant={'outline'}
          size={'sm'}
          leftIcon={<Icon as={CloudArrowDownIcon} />}
          ml={2}
        >
          download your result
        </Button>
      </p>
    </object>
  );
}
