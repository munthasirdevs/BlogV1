import { forwardRef } from 'react';
import { cn } from '@/utils';
import { X } from 'lucide-react';

/**
 * Modal component with overlay and close functionality
 * @param {Object} props - Component props
 * @param {boolean} props.isOpen - Modal open state
 * @param {Function} props.onClose - Close handler
 * @param {string} props.title - Modal title
 * @param {React.ReactNode} props.children - Modal content
 * @param {boolean} props.closeOnOverlay - Close when clicking overlay
 * @param {string} props.size - Modal size
 */
const Modal = forwardRef(
  (
    {
      isOpen,
      onClose,
      title,
      children,
      closeOnOverlay = true,
      size = 'md',
      className,
      ...props
    },
    ref
  ) => {
    const sizeStyles = {
      sm: 'max-w-md',
      md: 'max-w-lg',
      lg: 'max-w-2xl',
      xl: 'max-w-4xl',
      full: 'max-w-full mx-4',
    };

    if (!isOpen) return null;

    const handleOverlayClick = (e) => {
      if (closeOnOverlay && e.target === e.currentTarget) {
        onClose();
      }
    };

    return (
      <div
        ref={ref}
        className="fixed inset-0 z-50 flex items-center justify-center p-4"
        onClick={handleOverlayClick}
        role="dialog"
        aria-modal="true"
        aria-labelledby={title ? 'modal-title' : undefined}
        {...props}
      >
        {/* Overlay */}
        <div className="absolute inset-0 bg-black/50 backdrop-blur-sm animate-fade-in" />

        {/* Modal Content */}
        <div
          className={cn(
            'relative w-full bg-white dark:bg-secondary-800 rounded-xl shadow-2xl',
            'animate-slide-in',
            sizeStyles[size],
            className
          )}
        >
          {/* Header */}
          {(title || onClose) && (
            <div className="flex items-center justify-between px-6 py-4 border-b border-secondary-200 dark:border-secondary-700">
              {title && (
                <h2
                  id="modal-title"
                  className="text-lg font-semibold text-secondary-900 dark:text-secondary-100"
                >
                  {title}
                </h2>
              )}
              {onClose && (
                <button
                  onClick={onClose}
                  className="p-1 rounded-lg hover:bg-secondary-100 dark:hover:bg-secondary-700 transition-colors"
                  aria-label="Close modal"
                >
                  <X className="w-5 h-5 text-secondary-500" />
                </button>
              )}
            </div>
          )}

          {/* Body */}
          <div className="px-6 py-4">{children}</div>
        </div>
      </div>
    );
  }
);

Modal.displayName = 'Modal';

export default Modal;
