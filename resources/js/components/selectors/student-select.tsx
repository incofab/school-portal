import React from 'react';
import { Student } from '@/types/models';
import { AsyncProps } from 'react-select/async';
import { GroupBase } from 'react-select/dist/declarations/src/types';
import MyAsyncSelect from './my-async-select';
import useInstitutionRoute from '@/hooks/use-institution-route';

export default function StudentSelect<
  Option,
  IsMulti extends boolean,
  Group extends GroupBase<Option>
>({ ...props }: AsyncProps<Option, IsMulti, Group>) {
  const { instRoute } = useInstitutionRoute();
  return (
    <MyAsyncSelect
      searchUrl={instRoute('students.search')}
      label={(item: Student) =>
        item.user!.full_name + ' - ' + item.classification!.title
      }
      {...props}
    />
  );
}

// export default function StudentSelect<
//   Option,
//   IsMulti extends boolean,
//   Group extends GroupBase<Option>
// >({ ...props }: AsyncProps<Option, IsMulti, Group>) {
//   const debouncedSearch = useMemo(() => {
//     return debounce(async function (inputValue: string, callback: any) {
//       const url = new URL(instRoute('students.search'));
//       if (inputValue) {
//         url.searchParams.set('search', inputValue);
//       }
//       const res = await web.get(url.toString());
//       const result = res.data.users.data.map((item: Student) => ({
//         label: item.user!.full_name + ' - ' + item.classification!.title,
//         value: item.id,
//       }));
//       callback(result);
//     }, 250);
//   }, []);

//   return (
//     <AsyncSelect
//       loadOptions={(inputValue, callback) => {
//         /**
//          * Using promises with the debounce doesn't seem to work nicely
//          * Intentionally not returning the result of this function
//          * @see https://github.com/JedWatson/react-select/issues/3075#issuecomment-506647171
//          */
//         debouncedSearch(inputValue, callback);
//       }}
//       defaultOptions={true}
//       {...props}
//     />
//   );
// }
