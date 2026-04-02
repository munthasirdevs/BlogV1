import { forwardRef } from 'react';
import { cn } from '@/utils';

/**
 * Textarea component with label, error, and helper text
 * @param {Object} props - Component props
 */
const Textarea = forwardRef(
  ({ label, error, helperText, disabled, className, id, rows = 4, ...props }, ref) => {
    const textareaId = id || label?.toLowerCase().replace(/\s+/g, '-');

    const baseStyles = cn(
      'w-full px-4 py-2.5',
      'border rounded-lg',
      'bg-white dark:bg-secondary-800',
      'text-secondary-900 dark:text-secondary-100',
      'placeholder-secondary-400 dark:placeholder-secondary-500',
      'focus:outline-none focus:ring-2 focus:border-transparent',
      'transition-all duration-200',
      'disabled:opacity-50 disabled:cursor-not-allowed',
      'resize-y',
      {
        'border-secondary-300 dark:border-secondary-600 focus:ring-primary-500':
          !error,
        'border-danger-500 focus:ring-danger-500': error,
      },
      className
    );

    return (
      <div className="w-full">
        {label && (
          <label
            htmlFor={textareaId}
            className="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-1.5"
          >
            {label}
          </label>
        )}
        <textarea
          ref={ref}
          id={textareaId}
          rows={rows}
          className={baseStyles}
          disabled={disabled}
          aria-invalid={!!error}
          {...props}
        />
        {error && (
          <p className="mt-1.5 text-sm text-danger-600 dark:text-danger-400">
            {error}
          </p>
        )}
        {helperText && !error && (
          <p className="mt-1.5 text-sm text-secondary-500 dark:text-secondary-400">
            {helperText}
          </p>
        )}
      </div>
    );
  }
);

Textarea.displayName = 'Textarea';

export default Textarea;
