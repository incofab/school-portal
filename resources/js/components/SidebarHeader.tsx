import { BoxProps } from '@chakra-ui/react';
import styled from '@emotion/styled';
import React from 'react';
import { Div } from './semantic';

// const StyledSidebarHeader = styled.div`
//   margin-top: 5px !important;
//   height: 64px;
//   min-height: 64px;
//   display: flex;
//   align-items: center;
//   padding: 0 20px;

//   > div {
//     width: 100%;
//     overflow: hidden;
//   }
// `;

const StyledLogo = styled.div`
  width: 35px;
  min-width: 35px;
  height: 35px;
  min-height: 35px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  color: white;
  font-size: 24px;
  font-weight: 700;
  background-color: #009fdb;
  background: linear-gradient(45deg, rgb(21 87 205) 0%, rgb(90 225 255) 100%);
`;

export const SidebarHeader = ({ ...props }: BoxProps) => {
  return (
    <Div {...props} p={1}>
      <Div style={{ display: 'flex', alignItems: 'center' }}>
        <StyledLogo>T</StyledLogo>
        <h1 className="sidebarTitle">TriniCol</h1>
      </Div>
    </Div>
  );
};
