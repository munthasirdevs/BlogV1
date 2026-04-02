import { createContext, useContext, useState, useCallback } from 'react';
import { Toast } from '@/components/molecules';

/**
 * Toast context for managing global toast notifications
 */
const ToastContext = createContext(undefined);

/**
 * Toast provider component
 */
export function ToastProvider({ children }) {
  const [toasts, setToasts] = useState([]);

  /**
   * Add a new toast notification
   */
  const addToast = useCallback((toast) => {
    const id = Date.now();
    const newToast = {
      id,
      type: toast.type || 'info',
      title: toast.title,
      message: toast.message,
      duration: toast.duration || 5000,
    };

    setToasts((prev) => [...prev, newToast]);

    // Auto-remove toast after duration
    if (newToast.duration) {
      setTimeout(() => {
        removeToast(id);
      }, newToast.duration);
    }

    return id;
  }, []);

  /**
   * Remove a toast notification by ID
   */
  const removeToast = useCallback((id) => {
    setToasts((prev) => prev.filter((toast) => toast.id !== id));
  }, []);

  /**
   * Show info toast
   */
  const info = useCallback(
    (title, message, options = {}) => {
      return addToast({ type: 'info', title, message, ...options });
    },
    [addToast]
  );

  /**
   * Show success toast
   */
  const success = useCallback(
    (title, message, options = {}) => {
      return addToast({ type: 'success', title, message, ...options });
    },
    [addToast]
  );

  /**
   * Show warning toast
   */
  const warning = useCallback(
    (title, message, options = {}) => {
      return addToast({ type: 'warning', title, message, ...options });
    },
    [addToast]
  );

  /**
   * Show error toast
   */
  const error = useCallback(
    (title, message, options = {}) => {
      return addToast({ type: 'danger', title, message, ...options });
    },
    [addToast]
  );

  const value = {
    addToast,
    removeToast,
    info,
    success,
    warning,
    error,
  };

  return (
    <ToastContext.Provider value={value}>
      {children}
      {/* Render all toasts */}
      <div className="fixed bottom-4 right-4 z-50 space-y-2">
        {toasts.map((toast) => (
          <Toast
            key={toast.id}
            type={toast.type}
            title={toast.title}
            message={toast.message}
            show={true}
            onClose={() => removeToast(toast.id)}
          />
        ))}
      </div>
    </ToastContext.Provider>
  );
}

/**
 * Hook to use toast context
 */
export function useToast() {
  const context = useContext(ToastContext);
  if (context === undefined) {
    throw new Error('useToast must be used within a ToastProvider');
  }
  return context;
}

export default ToastContext;
