import { useEffect } from 'react';

/**
 * Custom hook to detect clicks outside a referenced element
 * @param {Object} options - Hook options
 * @param {React.RefObject} options.ref - Ref to the element to watch
 * @param {Function} options.handler - Callback when click outside occurs
 * @param {boolean} options.enabled - Whether the hook is enabled (default: true)
 * @param {string[]} options.excludedSelectors - CSS selectors to exclude from triggering
 */
export function useClickOutside({ ref, handler, enabled = true, excludedSelectors = [] }) {
  useEffect(() => {
    if (!enabled || !ref?.current) return;

    const handleClickOutside = (event) => {
      // Check if clicked element matches any excluded selectors
      const isExcluded = excludedSelectors.some((selector) => {
        const excludedElement = document.querySelector(selector);
        return excludedElement && (
          event.target === excludedElement ||
          excludedElement.contains(event.target)
        );
      });

      if (isExcluded) return;

      // Check if click is inside the referenced element
      if (ref.current && !ref.current.contains(event.target)) {
        handler(event);
      }
    };

    // Use mousedown to catch clicks before they propagate
    document.addEventListener('mousedown', handleClickOutside);

    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
    };
  }, [ref, handler, enabled, excludedSelectors]);
}

export default useClickOutside;
