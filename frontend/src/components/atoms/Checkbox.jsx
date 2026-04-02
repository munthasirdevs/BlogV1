import { forwardRef } from 'react';
import { cn } from '@/utils';

/**
 * Checkbox component with label
 * @param {Object} props - Component props
 * @param {string} props.label - Checkbox label
 * @param {string} props.error - Error message
 * @param {boolean} props.disabled - Disabled state
 * @param {string} props.className - Additional CSS classes
 */
const Checkbox = forwardRef(
  ({ label, error, disabled = false, className, id, ...props }, ref) => {
    const checkboxId = id || label?.toLowerCase().replace(/\s+/g, '-');

    const baseStyles = cn(
      'w-4 h-4',
      'rounded',
      'border-secondary-300 dark:border-secondary-600',
      'text-primary-600',
      'focus:ring-primary-500 focus:ring-2',
      'dark:bg-secondary-800',
      'dark:checked:bg-primary-600',
      'transition-colors',
      'disabled:opacity-50 disabled:cursor-not-allowed',
      {
        'border-danger-500 focus:ring-danger-500': error,
      },
      className
    );

    return (
      <div className="flex items-start">
        <input
          ref={ref}
          id={checkboxId}
          type="checkbox"
          className={baseStyles}
          disabled={disabled}
          aria-invalid={!!error}
          {...props}
        />
        {label && (
          <label
            htmlFor={checkboxId}
            className="ml-2 text-sm text-secondary-700 dark:text-secondary-300 cursor-pointer"
          >
            {label}
          </label>
        )}
      </div>
    );
  }
);

Checkbox.displayName = 'Checkbox';

export default Checkbox;
