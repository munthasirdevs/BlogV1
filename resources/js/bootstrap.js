import axios from 'axios';
import Alpine from 'alpinejs';
import { Theme } from './utils/theme';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

window.Alpine = Alpine;
window.Theme = Theme;

document.addEventListener('DOMContentLoaded', () => Theme.init());

Alpine.start();
