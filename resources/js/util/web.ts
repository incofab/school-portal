import axios from 'axios';

const web = axios.create({
  headers: {
    common: {
      'X-Requested-With': 'XMLHttpRequest',
    },
  },
  baseURL: '/',
});

export default web;
