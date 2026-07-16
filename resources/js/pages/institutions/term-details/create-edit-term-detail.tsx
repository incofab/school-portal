import { LinkButton } from '@/components/buttons';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import useInstitutionRoute from '@/hooks/use-institution-route';
import DashboardLayout from '@/layout/dashboard-layout';
import { TermDetail } from '@/types/models';
import TermDetailForm from './term-detail-form';

interface Props {
  termDetail: TermDetail;
  mode: 'create' | 'edit';
}

export default function CreateEditTermDetail({ termDetail, mode }: Props) {
  const { instRoute } = useInstitutionRoute();

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading
          title={
            mode === 'create' ? 'Create Term Detail' : 'Update Term Detail'
          }
          rightElement={
            <LinkButton
              title="Back to List"
              href={instRoute('term-details.index')}
              variant="outline"
            />
          }
        />
        <SlabBody>
          <TermDetailForm termDetail={termDetail} />
        </SlabBody>
      </Slab>
    </DashboardLayout>
  );
}
