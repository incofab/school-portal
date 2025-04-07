import React from 'react';
import { Assignment, Todo } from '@/types/models';
import { FormControl, Icon, VStack } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import useInstitutionRoute from '@/hooks/use-institution-route';
import useIsStudent from '@/hooks/use-is-student';
import DOMPurify from 'dompurify';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import { Inertia } from '@inertiajs/inertia';
import { preventNativeSubmit } from '@/util/util';
import FormControlBox from '@/components/forms/form-control-box';
import { Editor } from '@tinymce/tinymce-react';
import { FormButton, LinkButton } from '@/components/buttons';
import {
  Table,
  Thead,
  Tbody,
  Tfoot,
  Tr,
  Th,
  Td,
  TableCaption,
  TableContainer,
} from '@chakra-ui/react';
import CenteredBox from '@/components/centered-box';
import { CheckBadgeIcon } from '@heroicons/react/24/solid';
import { ExclamationCircleIcon } from '@heroicons/react/24/outline';

interface Props {
  todos: Todo[];
}

export default function ListTodoList({ todos }: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();

  return (
    <DashboardLayout>
      <CenteredBox>
        <Slab>
          <SlabHeading title={'Setup Checklist'} />

          <SlabBody>
            <TableContainer>
              <Table>
                <Tbody>
                  {todos.map((element) => (
                    <Tr key={element.route}>
                      <Td>
                        {element.count > 0 ? (
                          <Icon as={CheckBadgeIcon} color={'brand.500'} />
                        ) : (
                          <Icon as={ExclamationCircleIcon} color={'red'} />
                        )}
                      </Td>
                      <Td>{element.label}</Td>
                      <Td>
                        {element.count > 0 ? (
                          'Done'
                        ) : (
                          <LinkButton
                            variant={'link'}
                            href={instRoute(element.route)}
                            title=" Add Now "
                          />
                        )}
                      </Td>
                    </Tr>
                  ))}
                </Tbody>
              </Table>
            </TableContainer>
          </SlabBody>
        </Slab>
      </CenteredBox>
    </DashboardLayout>
  );
}
