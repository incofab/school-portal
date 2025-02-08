import React from 'react';

import { useState } from 'react';
import {
  Table,
  Button,
  Divider,
  HStack,
  Icon,
  IconButton,
  Spacer,
  Tbody,
  Td,
  Text,
  Th,
  Thead,
  Tr,
  Box,
  Modal,
  ModalOverlay,
  ModalContent,
  ModalHeader,
  ModalBody,
  ModalFooter,
  Input,
} from '@chakra-ui/react';
import FormControlBox from '@/components/forms/form-control-box';
import { dateTimeFormat, preventNativeSubmit } from '@/util/util';
import format from 'date-fns/format';
import InputForm from '@/components/forms/input-form';
import DashboardLayout from '@/layout/dashboard-layout';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { BrandButton } from '@/components/buttons';
import { CloudArrowDownIcon } from '@heroicons/react/24/solid';
import { PlusIcon } from '@heroicons/react/24/solid';

export default function ListTimetables() {
  // Define state to hold column headers and data
  const [columns, setColumns] = useState([
    { label: 'Day', accessor: 'day' },
    { label: '1st Period', accessor: 'p1' },
    { label: '2nd Period', accessor: 'p2' },
    { label: '3rd Period', accessor: 'p3' },
    { label: '4th Period', accessor: 'p4' },
    { label: '5th Period', accessor: 'p5' },
    // { label: 'Product', accessor: 'product' },
    // { label: 'Category', accessor: 'category' },
    // { label: 'Price', accessor: 'price' },
  ]);

  const [data, setData] = useState([
    { day: 'Monday', p1: '', p2: '', p3: '', p4: '', p5: '' },
    { day: 'Tuesday', p1: '', p2: '', p3: '', p4: '', p5: '' },
    { day: 'Wednesday', p1: '', p2: '', p3: '', p4: '', p5: '' },
    { day: 'Thursday', p1: '', p2: '', p3: '', p4: '', p5: '' },
    { day: 'Friday', p1: '', p2: '', p3: '', p4: '', p5: '' },
    { day: 'Saturday', p1: '', p2: '', p3: '', p4: '', p5: '' },
    { day: 'Sunday', p1: '', p2: '', p3: '', p4: '', p5: '' },

    // { product: 'Laptop', category: 'Electronics', price: 999.99 },
    // { product: 'Coffee Maker', category: 'Home Appliances', price: 49.99 },
    // { product: 'Desk Chair', category: 'Furniture', price: 150.99 },
    // { product: 'Smartphone', category: 'Electronics', price: 799.99 },
  ]);

  // Modal state
  /*
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [editedValue, setEditedValue] = useState('');
  const [currentCell, setCurrentCell] = useState({
    column: null,
    rowIndex: null,
  });*/

  const [isModalOpen, setIsModalOpen] = useState(false);
  const [inputValues, setInputValues] = useState({
    starts: '',
    ends: '',
    activity: '',
  });
  const [currentCell, setCurrentCell] = useState({
    column: null,
    rowIndex: null,
  });

  // Function to get the ordinal value
  const getOrdinal = (n: number) => {
    const j = n % 10;
    const k = n % 100;
    if (j === 1 && k !== 11) {
      return `${n}st`;
    }
    if (j === 2 && k !== 12) {
      return `${n}nd`;
    }
    if (j === 3 && k !== 13) {
      return `${n}rd`;
    }
    return `${n}th`;
  };

  // Function to handle adding a new column
  const addColumn = () => {
    const newColumn = {
      label: `${getOrdinal(columns.length)} Period`,
      accessor: `p${columns.length}`,
    };
    setColumns([...columns, newColumn]);

    // Add new data to all rows for this new column
    const newData = data.map((row) => ({
      ...row,
      [newColumn.accessor]: '', // N/A - Default value
    }));
    setData(newData);
  };

  // Function to handle deleting a column
  const deleteColumn = (accessor: any) => {
    // Filter out the column from the columns state
    const updatedColumns = columns.filter((col) => col.accessor !== accessor);
    setColumns(updatedColumns);

    // Remove the corresponding field from each row's data
    const updatedData = data.map((row) => {
      const { [accessor]: _, ...rest } = row; // Destructure to remove the column
      return rest;
    });
    setData(updatedData);
  };

  /*
  // Function to handle cell click and open modal for editing
  const handleCellClick = (column, rowIndex) => {
    setCurrentCell({ column, rowIndex });
    setEditedValue(data[rowIndex][column.accessor]);
    setIsModalOpen(true);
  };

  // Function to handle value change in the input field
  const handleValueChange = (event) => {
    setEditedValue(event.target.value);
  };

  // Function to handle modal save
  const handleSave = () => {
    const updatedData = [...data];
    updatedData[currentCell.rowIndex][currentCell.column.accessor] =
      editedValue;
    setData(updatedData);
    setIsModalOpen(false);
  };

  // Function to handle modal close without saving
  const handleClose = () => {
    setIsModalOpen(false);
  }; 
  */

  // Function to handle cell click and open modal for editing
  const handleCellClick = (column, rowIndex) => {
    setCurrentCell({ column, rowIndex });
    const cellContent = data[rowIndex][column.accessor];
    // Assuming the data is a string or can be split into parts for 3 inputs
    const [starts, ends, activity] = cellContent.split('\n');
    setInputValues({
      starts: starts || '',
      ends: ends || '',
      activity: activity || '',
    });
    setIsModalOpen(true);
  };

  // Function to handle value change in the input fields
  const handleValueChange = (event) => {
    // alert(format(new Date(event.target.value), dateTimeFormat));
    setInputValues({
      ...inputValues,
      [event.target.name]: event.target.value,
    });
  };

  // Function to handle modal save
  const handleSave = () => {
    const updatedData = [...data];
    const updatedValue = `${inputValues.starts}\n${inputValues.ends}\n${inputValues.activity}`;
    updatedData[currentCell.rowIndex][currentCell.column.accessor] =
      updatedValue;
    setData(updatedData);
    setIsModalOpen(false);
  };

  // Function to handle modal close without saving
  const handleClose = () => {
    setIsModalOpen(false);
  };

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading
          title="Deposits"
          rightElement={
            // <LinkButton
            //   href={instRoute('fundings.create')}
            //   title={'Add New Column'}
            // />

            <BrandButton
              onClick={addColumn}
              size="sm"
              mt={3}
              leftIcon={<Icon as={PlusIcon} />}
              variant={'solid'}
              colorScheme={'brand'}
              title="Add Column"
            />
          }
        />

        {/* Table */}
        <SlabBody>
          <Table size="sm">
            <Thead>
              <Tr>
                {columns.map((column, index) => (
                  <Th key={index}>
                    {column.label}
                    {/* Delete Button for each column */}
                    {index > 5 && (
                      <Button
                        size="xs"
                        colorScheme="red"
                        ml={2}
                        onClick={() => deleteColumn(column.accessor)}
                      >
                        DELETE {index}
                      </Button>
                    )}
                  </Th>
                ))}
              </Tr>
            </Thead>

            <Tbody>
              {data.map((row, rowIndex) => (
                <Tr key={rowIndex}>
                  {columns.map((column, colIndex) => (
                    <Td key={colIndex}>
                      <div style={{ whiteSpace: 'pre-wrap' }}>
                        {row[column.accessor]}
                      </div>{' '}
                      {colIndex > 0 && (
                        <Button
                          size="xs"
                          colorScheme="teal"
                          onClick={() => handleCellClick(column, rowIndex)}
                        >
                          Echo '{column.label}-{rowIndex}'
                        </Button>
                      )}
                    </Td>
                  ))}
                </Tr>
              ))}
            </Tbody>
          </Table>
        </SlabBody>
      </Slab>

      {/* Modal to edit cell value */}
      <Modal isOpen={isModalOpen} onClose={handleClose}>
        <ModalOverlay />
        <ModalContent>
          <ModalHeader>Edit Cell Value</ModalHeader>
          <ModalBody>
            <label htmlFor="starts">Starts At:</label>
            <Input
              name="starts"
              type="time"
              value={inputValues.starts}
              onChange={handleValueChange}
              mb={2}
            />

            <label htmlFor="ends">Ends At:</label>
            <Input
              name="ends"
              type="time"
              value={inputValues.ends}
              onChange={handleValueChange}
              mb={2}
              title="Ends At:"
              placeholder="08:00"
            />

            <label htmlFor="activity">Activity / Subject:</label>
            <Input
              name="activity"
              value={inputValues.activity}
              onChange={handleValueChange}
              placeholder="activity"
            />
          </ModalBody>
          <ModalFooter>
            <Button variant="ghost" onClick={handleClose}>
              Cancel
            </Button>
            <Button colorScheme="blue" onClick={handleSave}>
              Save
            </Button>
          </ModalFooter>
        </ModalContent>
      </Modal>
    </DashboardLayout>
  );
}
