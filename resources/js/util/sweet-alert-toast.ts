import Swal, { SweetAlertOptions } from 'sweetalert2';
import withReactContent from 'sweetalert2-react-content';

const MySwal = withReactContent(Swal);

export default function sweetAlertToast(options: SweetAlertOptions) {
  MySwal.fire({ ...options });
  // MySwal.fire({
  //   title: 'Failed.',
  //   html: '<b>This Course Could Not Be Registered.</b><br/>Kindly verify that the Course Code does not exist already.',
  //   icon: 'error',
  // });
}
