import { forwardRef } from 'react';
import { cn } from '@/utils';

/**
 * Switch/Toggle component
 * @param {Object} props - Component props
 * @param {boolean} props.checked - Checked state
 * @param {Function} props.onChange - Change handler
 * @param {boolean} props.disabled - Disabled state
 * @param {string} props.size - Switch size
 */
const Switch = forwardRef(
  ({ checked = false, onChange, disabled = false, size = 'md', className, ...props }, ref) => {
    const sizeStyles = {
      sm: 'w-8 h-4',
      md: 'w-11 h-6',
      lg: 'w-14 h-7',
    };

    const thumbSizes = {
      sm: 'w-3 h-3',
      md: 'w-5 h-5',
      lg: 'w-6 h-6',
    };

    const translateStyles = {
      sm: checked ? 'translate-x-4' : 'translate-x-0',
      md: checked ? 'translate-x-5' : 'translate-x-0',
      lg: checked ? 'translate-x-7' : 'translate-x-0',
    };

    return (
      <button
        ref={ref}
        type="button"
        role="switch"
        aria-checked={checked}
        disabled={disabled}
        className={cn(
          'relative inline-flex flex-shrink-0 rounded-full transition-colors duration-200',
          'focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2',
          disabled && 'opacity-50 cursor-not-allowed',
          checked ? 'bg-primary-600' : 'bg-secondary-300 dark:bg-secondary-600',
          sizeStyles[size],
          className
        )}
        onClick={() => !disabled && onChange?.(!checked)}
        {...props}
      >
        <span
          className={cn(
            'inline-block bg-white rounded-full shadow transform transition-transform duration-200',
            thumbSizes[size],
            translateStyles[size],
            size === 'sm' && checked ? 'translate-x-4' : '',
            size === 'md' && checked ? 'translate-x-5' : '',
            size === 'lg' && checked ? 'translate-x-7' : ''
          )}
        />
      </button>
    );
  }
);

Switch.displayName = 'Switch';

export default Switch;
