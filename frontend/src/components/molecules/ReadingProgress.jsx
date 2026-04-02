import { useEffect, useState } from 'react';
import { cn } from '@/utils';
import { useScroll } from '@/hooks';

/**
 * ReadingProgress component - Shows scroll progress as a bar at the top
 * @param {Object} props - Component props
 * @param {string} props.color - Progress bar color (default: primary)
 * @param {string} props.height - Progress bar height (default: 3px)
 * @param {boolean} props.showPercentage - Show percentage text (default: false)
 */
function ReadingProgress({ color = 'primary', height = '3px', showPercentage = false }) {
  const [progress, setProgress] = useState(0);
  const { scrollY, scrollHeight } = useScroll();

  useEffect(() => {
    // Calculate scroll progress percentage
    const scrollTop = window.scrollY;
    const docHeight = document.documentElement.scrollHeight - window.innerHeight;
    const scrollPercent = docHeight > 0 ? (scrollTop / docHeight) * 100 : 0;
    setProgress(Math.min(scrollPercent, 100));
  }, [scrollY, scrollHeight]);

  const colorClasses = {
    primary: 'bg-primary-600 dark:bg-primary-400',
    secondary: 'bg-secondary-600 dark:bg-secondary-400',
    accent: 'bg-accent-600 dark:bg-accent-400',
    green: 'bg-green-600 dark:bg-green-400',
    blue: 'bg-blue-600 dark:bg-blue-400',
  };

  return (
    <div className="fixed top-0 left-0 w-full z-50" role="progressbar" aria-valuenow={Math.round(progress)} aria-valuemin={0} aria-valuemax={100}>
      {/* Progress bar */}
      <div
        className={cn('transition-all duration-150 ease-out', colorClasses[color])}
        style={{
          height,
          width: `${progress}%`,
        }}
      />
      
      {/* Percentage text (optional) */}
      {showPercentage && (
        <div className="absolute right-4 top-1 text-xs font-medium text-secondary-600 dark:text-secondary-400">
          {Math.round(progress)}%
        </div>
      )}
    </div>
  );
}

export default ReadingProgress;
