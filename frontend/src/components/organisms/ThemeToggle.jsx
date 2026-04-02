import { useState } from 'react';
import { cn } from '@/utils';
import { Button } from '@/components/atoms';
import { Sun, Moon, Monitor } from 'lucide-react';
import { useTheme } from '@/contexts/ThemeContext';

/**
 * Theme toggle component for switching between light, dark, and system themes
 */
export function ThemeToggle({ className, ...props }) {
  const { theme, setTheme } = useTheme();
  const [isOpen, setIsOpen] = useState(false);

  const themes = [
    { value: 'light', label: 'Light', icon: Sun },
    { value: 'dark', label: 'Dark', icon: Moon },
    { value: 'system', label: 'System', icon: Monitor },
  ];

  const currentTheme = themes.find((t) => t.value === theme) || themes[0];
  const CurrentIcon = currentTheme.icon;

  return (
    <div className={cn('relative', className)} {...props}>
      <button
        onClick={() => setIsOpen(!isOpen)}
        className="p-2 rounded-lg hover:bg-secondary-100 dark:hover:bg-secondary-800 transition-colors"
        aria-label="Toggle theme"
      >
        <CurrentIcon className="w-5 h-5 text-secondary-600 dark:text-secondary-400" />
      </button>

      {isOpen && (
        <>
          <div className="fixed inset-0 z-10" onClick={() => setIsOpen(false)} />
          <div className="absolute right-0 mt-2 w-40 py-2 bg-white dark:bg-secondary-800 rounded-lg shadow-lg border border-secondary-200 dark:border-secondary-700 z-20">
            {themes.map((t) => (
              <button
                key={t.value}
                onClick={() => {
                  setTheme(t.value);
                  setIsOpen(false);
                }}
                className={cn(
                  'w-full flex items-center gap-3 px-4 py-2 text-sm',
                  'text-secondary-700 dark:text-secondary-300',
                  'hover:bg-secondary-100 dark:hover:bg-secondary-700',
                  'transition-colors',
                  theme === t.value && 'bg-secondary-100 dark:bg-secondary-700'
                )}
              >
                <t.icon className="w-4 h-4" />
                {t.label}
              </button>
            ))}
          </div>
        </>
      )}
    </div>
  );
}

export default ThemeToggle;
