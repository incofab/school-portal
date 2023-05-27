import filter from 'lodash/filter';
import keys from 'lodash/keys';
import pull from 'lodash/pull';
import includes from 'lodash/includes';
import lowerCase from 'lodash/lowerCase';
import find from 'lodash/find';

export default function searchContent(data: any, search: string) {
  if (typeof search === 'undefined' || search.length === 0) {
    return data;
  }

  const result = filter(data, function (c) {
    const cProperties = keys(c);
    pull(cProperties, 'id');

    return find(cProperties, function (property) {
      if (c[property]) {
        return includes(lowerCase(c[property]), lowerCase(search));
      }
    });
  });

  return result;
}
