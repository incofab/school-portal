import React, { useState } from 'react';
import { preventNativeSubmit } from '@/util/util';
import {
  Button,
  FormControl,
  HStack,
  Icon,
  Input,
  Select,
} from '@chakra-ui/react';
import { Div } from '@/components/semantic';
import { MagnifyingGlassIcon } from '@heroicons/react/24/outline';
import useQueryString from '@/hooks/use-query-string';
import { Inertia } from '@inertiajs/inertia';

export interface SearchField {
  label: string;
  value: string;
}

interface Props {
  fields?: SearchField[];
  onChange: (search: string) => void;
}

export default function FieldSearchForm({ fields, onChange }: Props) {
  const { params } = useQueryString();
  const [searchQuery, setSearchQuery] = useState(params.searchQuery || '');
  const [searchField, setSearchField] = useState(
    params.searchField || (fields ? fields[0].value : '')
  );
  function onSearch() {
    const url = new URL(window.location.href);

    if (!searchQuery) {
      url.searchParams.delete('search');
      // url.searchParams.delete('searchField');
    }

    if (searchQuery) {
      url.searchParams.set('search', searchQuery);
    }
    // if (searchField) {
    //   url.searchParams.set('searchField', searchField);
    // }

    Inertia.visit(url.toString());
  }

  return (
    <HStack as={'form'} onSubmit={preventNativeSubmit(onSearch)} align={'end'}>
      <HStack align={'end'} spacing={0}>
        {fields && fields.length > 0 && (
          <FormControl>
            <Select
              size={'sm'}
              roundedRight={0}
              borderRight={0}
              value={searchField}
              onChange={(e) => setSearchField(e.currentTarget.value)}
            >
              {fields.map((filter) => (
                <option key={filter.value} value={filter.value}>
                  {filter.label}
                </option>
              ))}
            </Select>
          </FormControl>
        )}
        <FormControl>
          <Input
            size={'sm'}
            roundedLeft={0}
            placeholder={`Search records...`}
            value={searchQuery}
            onChange={(e) => {
              const query = e.currentTarget.value;
              setSearchQuery(query);
              // onChange(query);
            }}
          />
        </FormControl>
      </HStack>
      <Div>
        <Button
          colorScheme={'brand'}
          leftIcon={<Icon as={MagnifyingGlassIcon} w={4} h={4} />}
          size={'sm'}
          type={'submit'}
        >
          Search
        </Button>
      </Div>
    </HStack>
  );
}
