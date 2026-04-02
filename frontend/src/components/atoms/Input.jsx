import { forwardRef } from 'react';
import { cn } from '@/utils';
import { AlertCircle, CheckCircle2, XCircle } from 'lucide-react';

/**
 * Input component with label, error, and helper text
 * @param {Object} props - Component props
 * @param {string} props.label - Input label
 * @param {string} props.error - Error message
 * @param {string} props.helperText - Helper text
 * @param {'text' | 'email' | 'password' | 'number' | 'tel' | 'url' | 'search'} props.type - Input type
 * @param {boolean} props.disabled - Disabled state
 * @param {React.ReactNode} props.leftIcon - Icon on the left
 * @param {React.ReactNode} props.rightIcon - Icon on the right
 * @param {string} props.className - Additional CSS classes
 */
const Input = forwardRef(
  (
    {
      label,
      error,
      helperText,
      type = 'text',
      disabled = false,
      leftIcon,
      rightIcon,
      className,
      id,
      ...props
    },
    ref
  ) => {
    const inputId = id || label?.toLowerCase().replace(/\s+/g, '-');

    const baseStyles = cn(
      'w-full px-4 py-2.5',
      'border rounded-lg',
      'bg-white dark:bg-secondary-800',
      'text-secondary-900 dark:text-secondary-100',
      'placeholder-secondary-400 dark:placeholder-secondary-500',
      'focus:outline-none focus:ring-2 focus:border-transparent',
      'transition-all duration-200',
      'disabled:opacity-50 disabled:cursor-not-allowed',
      {
        'border-secondary-300 dark:border-secondary-600 focus:ring-primary-500':
          !error,
        'border-danger-500 focus:ring-danger-500': error,
        'pr-10': rightIcon,
        'pl-10': leftIcon,
      },
      className
    );

    return (
      <div className="w-full">
        {label && (
          <label
            htmlFor={inputId}
            className="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-1.5"
          >
            {label}
          </label>
        )}
        <div className="relative">
          {leftIcon && (
            <div className="absolute left-3 top-1/2 -translate-y-1/2 text-secondary-400">
              {leftIcon}
            </div>
          )}
          <input
            ref={ref}
            id={inputId}
            type={type}
            className={baseStyles}
            disabled={disabled}
            aria-invalid={!!error}
            aria-describedby={error ? `${inputId}-error` : undefined}
            {...props}
          />
          {rightIcon && (
            <div className="absolute right-3 top-1/2 -translate-y-1/2 text-secondary-400">
              {rightIcon}
            </div>
          )}
        </div>
        {error && (
          <p
            id={`${inputId}-error`}
            className="mt-1.5 text-sm text-danger-600 dark:text-danger-400 flex items-center gap-1"
            role="alert"
          >
            <XCircle className="w-4 h-4" />
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

Input.displayName = 'Input';

export default Input;
