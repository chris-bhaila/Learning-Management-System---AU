import Alpine from 'alpinejs';
import Iodine from '@caneara/iodine';

const iodine = new Iodine();
iodine.setErrorMessage('required',  'This field is required.');
iodine.setErrorMessage('maxLength', 'Must be [PARAM] characters or fewer.');

window.Alpine = Alpine;
window.Iodine = iodine;
Alpine.start();
