import React from 'react';
import route from '@/util/route';
import { AcademicSession } from '@/types/models';
import { Props } from 'react-select';
import SingleQuerySelect from '../dropdown-select/single-query-select';

interface MyProps {
  selectValue?: number | string;
  academicSessions?: AcademicSession[];
}

export default function AcademicSessionSelect({
  selectValue,
  academicSessions,
  ...props
}: MyProps & Props) {
  return (
    <SingleQuerySelect
      {...props}
      selectValue={selectValue}
      dataList={academicSessions}
      searchUrl={route('academic-sessions.search')}
      label={'title'}
    />
  );
}
/*
export default function AcademicSessionSelect({
  selectValue,
  academicSessions,
  ...props
}: MyProps & Props) {
  const [data, setData] = useState<AcademicSession[]>(academicSessions ?? []);
  const [refreshKey, setRefreshKey] = useState<string>('');
  const webForm = useWebForm({});

  useEffect(() => {
    if (academicSessions) {
      return;
    }
    webForm
      .submit((data, web) => {
        return web.get(route('academic-sessions.search'));
      })
      .then(({ ok, data }) => {
        if (!ok) {
          return;
        }
        setData(data.academicSessions);
        setRefreshKey(Math.random() + '');
      });
  }, []);

  return (
    <ReactSelect
      {...props}
      selectValue={selectValue}
      data={{ main: data, label: 'title', value: 'id' }}
      isLoading={webForm.processing}
      refreshKey={refreshKey}
    />
  );
}
*/
