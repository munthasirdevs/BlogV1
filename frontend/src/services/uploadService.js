import api from './api';
import { API_ENDPOINTS } from '@/constants';

/**
 * Upload service
 */
export const uploadService = {
  /**
   * Upload a file
   * @param {File} file - File to upload
   * @param {string} type - Upload type (image, document, etc.)
   */
  upload: async (file, type = 'image') => {
    const formData = new FormData();
    formData.append('file', file);
    formData.append('type', type);

    const response = await api.post(API_ENDPOINTS.UPLOAD, formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });
    return response.data;
  },

  /**
   * Upload multiple files
   * @param {File[]} files - Files to upload
   * @param {string} type - Upload type
   */
  uploadMultiple: async (files, type = 'image') => {
    const formData = new FormData();
    files.forEach((file) => {
      formData.append('files[]', file);
    });
    formData.append('type', type);

    const response = await api.post(`${API_ENDPOINTS.UPLOAD}/multiple`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });
    return response.data;
  },
};

export default uploadService;
