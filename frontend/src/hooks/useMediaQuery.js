import { useState, useEffect } from 'react';

/**
 * Custom hook to track media query matches
 * @param {string} query - Media query string (e.g., '(min-width: 768px)')
 * @returns {boolean} Whether the media query matches
 */
export function useMediaQuery(query) {
  const [matches, setMatches] = useState(() => {
    if (typeof window === 'undefined') return false;
    return window.matchMedia(query).matches;
  });

  useEffect(() => {
    const mediaQuery = window.matchMedia(query);

    // Update state with current value
    setMatches(mediaQuery.matches);

    // Handler for changes
    const handleChange = (event) => {
      setMatches(event.matches);
    };

    // Add listener (modern browsers)
    mediaQuery.addEventListener('change', handleChange);

    return () => {
      mediaQuery.removeEventListener('change', handleChange);
    };
  }, [query]);

  return matches;
}

/**
 * Predefined breakpoint hooks
 */
export const useIsMobile = () => useMediaQuery('(max-width: 639px)');
export const useIsTablet = () => useMediaQuery('(min-width: 640px) and (max-width: 1023px)');
export const useIsDesktop = () => useMediaQuery('(min-width: 1024px)');
export const useIsSm = () => useMediaQuery('(min-width: 640px)');
export const useIsMd = () => useMediaQuery('(min-width: 768px)');
export const useIsLg = () => useMediaQuery('(min-width: 1024px)');
export const useIsXl = () => useMediaQuery('(min-width: 1280px)');
export const useIs2xl = () => useMediaQuery('(min-width: 1536px)');

export default useMediaQuery;
