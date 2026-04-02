import { useState, useRef, useEffect } from 'react';
import { cn } from '@/utils';
import { ChevronDown } from 'lucide-react';

/**
 * Dropdown component with trigger and menu
 * @param {Object} props - Component props
 * @param {React.ReactNode} props.trigger - Trigger element
 * @param {Array} props.items - Menu items
 * @param {string} props.align - Menu alignment
 * @param {string} props.className - Additional CSS classes
 */
function Dropdown({ trigger, items, align = 'right', className, children }) {
  const [isOpen, setIsOpen] = useState(false);
  const dropdownRef = useRef(null);

  useEffect(() => {
    const handleClickOutside = (event) => {
      if (dropdownRef.current && !dropdownRef.current.contains(event.target)) {
        setIsOpen(false);
      }
    };

    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  const alignStyles = {
    left: 'left-0',
    right: 'right-0',
    center: 'left-1/2 -translate-x-1/2',
  };

  return (
    <div ref={dropdownRef} className={cn('relative inline-block', className)}>
      {/* Trigger */}
      <div onClick={() => setIsOpen(!isOpen)} className="cursor-pointer">
        {trigger || (
          <button className="inline-flex items-center gap-1 px-3 py-2 text-sm font-medium text-secondary-700 dark:text-secondary-300 hover:bg-secondary-100 dark:hover:bg-secondary-800 rounded-lg transition-colors">
            Options
            <ChevronDown className={cn('w-4 h-4 transition-transform', isOpen && 'rotate-180')} />
          </button>
        )}
      </div>

      {/* Menu */}
      {isOpen && (
        <>
          <div className="fixed inset-0 z-10" onClick={() => setIsOpen(false)} />
          <div
            className={cn(
              'absolute z-20 mt-2 w-48 py-1',
              'bg-white dark:bg-secondary-800',
              'rounded-lg shadow-lg border border-secondary-200 dark:border-secondary-700',
              'animate-fade-in',
              alignStyles[align]
            )}
          >
            {children ||
              items?.map((item, index) => (
                <button
                  key={index}
                  onClick={() => {
                    item.onClick?.();
                    setIsOpen(false);
                  }}
                  className={cn(
                    'w-full px-4 py-2 text-left text-sm',
                    'text-secondary-700 dark:text-secondary-300',
                    'hover:bg-secondary-100 dark:hover:bg-secondary-700',
                    'transition-colors',
                    item.divider && 'border-t border-secondary-200 dark:border-secondary-700 pt-2 mt-1',
                    item.disabled && 'opacity-50 cursor-not-allowed'
                  )}
                  disabled={item.disabled}
                >
                  {item.icon && (
                    <span className="inline-flex items-center mr-2">{item.icon}</span>
                  )}
                  {item.label}
                </button>
              ))}
          </div>
        </>
      )}
    </div>
  );
}

export default Dropdown;
