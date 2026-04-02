import { useState, useEffect } from 'react';

/**
 * Custom hook to track scroll position and direction
 * @param {Object} options - Hook options
 * @param {number} options.scrollThreshold - Threshold to trigger show/hide (default: 300)
 * @returns {Object} Scroll state and helpers
 */
export function useScroll({ scrollThreshold = 300 } = {}) {
  const [scrollY, setScrollY] = useState(0);
  const [scrollDirection, setScrollDirection] = useState('up');
  const [isScrolled, setIsScrolled] = useState(false);
  const [isAtTop, setIsAtTop] = useState(true);
  const [isAtBottom, setIsAtBottom] = useState(false);

  useEffect(() => {
    let lastScrollY = window.scrollY;
    let ticking = false;

    const handleScroll = () => {
      if (!ticking) {
        window.requestAnimationFrame(() => {
          const currentScrollY = window.scrollY;
          const docHeight = document.documentElement.scrollHeight;
          const winHeight = window.innerHeight;

          setScrollY(currentScrollY);
          setIsScrolled(currentScrollY > scrollThreshold);
          setIsAtTop(currentScrollY <= 10);
          setIsAtBottom(currentScrollY + winHeight >= docHeight - 10);

          if (currentScrollY > lastScrollY) {
            setScrollDirection('down');
          } else if (currentScrollY < lastScrollY) {
            setScrollDirection('up');
          }

          lastScrollY = currentScrollY;
          ticking = false;
        });

        ticking = true;
      }
    };

    // Initial check
    handleScroll();

    window.addEventListener('scroll', handleScroll, { passive: true });

    return () => {
      window.removeEventListener('scroll', handleScroll);
    };
  }, [scrollThreshold]);

  const scrollToTop = () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  const scrollToBottom = () => {
    window.scrollTo({ top: document.documentElement.scrollHeight, behavior: 'smooth' });
  };

  const scrollTo = (y) => {
    window.scrollTo({ top: y, behavior: 'smooth' });
  };

  return {
    scrollY,
    scrollDirection,
    isScrolled,
    isAtTop,
    isAtBottom,
    scrollToTop,
    scrollToBottom,
    scrollTo,
  };
}

export default useScroll;
