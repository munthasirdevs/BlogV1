import { useState, useEffect } from 'react';
import { cn } from '@/utils';
import { List, X } from 'lucide-react';

/**
 * TableOfContents component - Generates clickable TOC from headings
 * @param {Object} props - Component props
 * @param {string} props.contentSelector - CSS selector for content container
 * @param {string} props.className - Additional CSS classes
 * @param {boolean} props.collapsible - Allow collapsing (default: true)
 * @param {string} props.title - TOC title (default: 'Table of Contents')
 */
function TableOfContents({ contentSelector = '.prose', className, collapsible = true, title = 'Table of Contents' }) {
  const [headings, setHeadings] = useState([]);
  const [activeId, setActiveId] = useState('');
  const [isCollapsed, setIsCollapsed] = useState(false);

  useEffect(() => {
    // Wait for content to render
    const timer = setTimeout(() => {
      const contentElement = document.querySelector(contentSelector);
      if (!contentElement) return;

      // Find all h2 and h3 headings
      const headingElements = contentElement.querySelectorAll('h2, h3');
      const headingData = Array.from(headingElements).map((heading) => {
        // Generate ID if not present
        if (!heading.id) {
          heading.id = heading.textContent
            .toLowerCase()
            .replace(/[^\w\s-]/g, '')
            .replace(/\s+/g, '-');
        }

        return {
          id: heading.id,
          text: heading.textContent,
          level: heading.tagName.toLowerCase(),
        };
      });

      setHeadings(headingData);
    }, 100);

    return () => clearTimeout(timer);
  }, [contentSelector]);

  useEffect(() => {
    if (headings.length === 0) return;

    // Intersection Observer to highlight active heading
    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            setActiveId(entry.target.id);
          }
        });
      },
      {
        rootMargin: '-100px 0px -60% 0px',
        threshold: 0,
      }
    );

    headings.forEach(({ id }) => {
      const element = document.getElementById(id);
      if (element) observer.observe(element);
    });

    return () => observer.disconnect();
  }, [headings]);

  if (headings.length === 0) return null;

  const handleClick = (id) => {
    const element = document.getElementById(id);
    if (element) {
      element.scrollIntoView({ behavior: 'smooth' });
      setActiveId(id);
    }
  };

  return (
    <nav
      className={cn(
        'bg-secondary-50 dark:bg-secondary-800/50 rounded-xl p-4 mb-8',
        'border border-secondary-200 dark:border-secondary-700',
        className
      )}
      aria-label={title}
    >
      {/* Header */}
      <div className="flex items-center justify-between mb-3">
        <div className="flex items-center gap-2">
          <List className="w-4 h-4 text-secondary-500" />
          <h3 className="text-sm font-semibold text-secondary-900 dark:text-secondary-100">
            {title}
          </h3>
        </div>
        {collapsible && (
          <button
            onClick={() => setIsCollapsed(!isCollapsed)}
            className="p-1 rounded hover:bg-secondary-200 dark:hover:bg-secondary-700 transition-colors"
            aria-label={isCollapsed ? 'Expand table of contents' : 'Collapse table of contents'}
          >
            <X className="w-4 h-4 text-secondary-500" />
          </button>
        )}
      </div>

      {/* Headings list */}
      {!isCollapsed && (
        <ul className="space-y-1">
          {headings.map(({ id, text, level }) => (
            <li key={id}>
              <button
                onClick={() => handleClick(id)}
                className={cn(
                  'block w-full text-left text-sm py-1.5 px-2 rounded-lg transition-all',
                  level === 'h3' && 'ml-4',
                  activeId === id
                    ? 'bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400 font-medium'
                    : 'text-secondary-600 dark:text-secondary-400 hover:bg-secondary-100 dark:hover:bg-secondary-700'
                )}
              >
                {text}
              </button>
            </li>
          ))}
        </ul>
      )}
    </nav>
  );
}

export default TableOfContents;
