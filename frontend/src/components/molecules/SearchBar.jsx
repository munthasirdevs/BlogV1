import { useState, useRef, useEffect, useCallback } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import { cn } from '@/utils';
import { Search, X, Loader2 } from 'lucide-react';
import { useClickOutside } from '@/hooks';
import { debounce } from '@/utils';
import { searchService } from '@/services';
import { ROUTES } from '@/constants';

/**
 * SearchBar component with debounce and search suggestions
 */
function SearchBar({ className, placeholder = 'Search posts, categories, tags...', variant = 'default' }) {
  const [query, setQuery] = useState('');
  const [isFocused, setIsFocused] = useState(false);
  const [suggestions, setSuggestions] = useState([]);
  const [isLoading, setIsLoading] = useState(false);
  const [isOpen, setIsOpen] = useState(false);
  const [selectedIndex, setSelectedIndex] = useState(-1);
  const [recentSearches, setRecentSearches] = useState([]);

  const searchRef = useRef(null);
  const inputRef = useRef(null);
  const navigate = useNavigate();
  const location = useLocation();

  // Load recent searches from localStorage
  useEffect(() => {
    const stored = localStorage.getItem('recent_searches');
    if (stored) {
      try {
        setRecentSearches(JSON.parse(stored));
      } catch (e) {
        console.error('Failed to parse recent searches:', e);
      }
    }
  }, []);

  // Debounced search function
  const performSearch = useCallback(
    debounce(async (searchQuery) => {
      if (!searchQuery.trim()) {
        setSuggestions([]);
        setIsLoading(false);
        return;
      }

      try {
        setIsLoading(true);
        const results = await searchService.suggestions(searchQuery);
        setSuggestions(results.suggestions || []);
      } catch (error) {
        console.error('Search failed:', error);
        setSuggestions([]);
      } finally {
        setIsLoading(false);
      }
    }, 300),
    []
  );

  // Handle query change
  const handleQueryChange = (e) => {
    const newQuery = e.target.value;
    setQuery(newQuery);
    performSearch(newQuery);
    setSelectedIndex(-1);
  };

  // Handle search submission
  const handleSearch = useCallback(
    (searchQuery = query) => {
      if (!searchQuery.trim()) return;

      // Add to recent searches
      const updated = [searchQuery, ...recentSearches.filter((s) => s !== searchQuery)].slice(0, 5);
      setRecentSearches(updated);
      localStorage.setItem('recent_searches', JSON.stringify(updated));

      // Navigate to search results
      navigate(`${ROUTES.POSTS}?q=${encodeURIComponent(searchQuery)}`);
      setIsOpen(false);
      setQuery('');
      setSuggestions([]);

      // Close on mobile
      if (inputRef.current) {
        inputRef.current.blur();
      }
    },
    [query, recentSearches, navigate]
  );

  // Handle key press
  const handleKeyDown = (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      if (selectedIndex >= 0 && suggestions[selectedIndex]) {
        handleSearch(suggestions[selectedIndex]);
      } else {
        handleSearch();
      }
    } else if (e.key === 'ArrowDown') {
      e.preventDefault();
      setSelectedIndex((prev) => (prev < suggestions.length - 1 ? prev + 1 : prev));
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      setSelectedIndex((prev) => (prev > 0 ? prev - 1 : -1));
    } else if (e.key === 'Escape') {
      setIsOpen(false);
      inputRef.current?.blur();
    }
  };

  // Clear search
  const handleClear = () => {
    setQuery('');
    setSuggestions([]);
    setSelectedIndex(-1);
    inputRef.current?.focus();
  };

  // Close on outside click
  useClickOutside({
    ref: searchRef,
    handler: () => {
      setIsOpen(false);
    },
  });

  // Clear recent search
  const clearRecentSearches = () => {
    setRecentSearches([]);
    localStorage.removeItem('recent_searches');
  };

  const isExpanded = isFocused || isOpen || query.length > 0;

  return (
    <div
      ref={searchRef}
      className={cn('relative', className)}
      onFocus={() => setIsFocused(true)}
      onBlur={() => setIsFocused(false)}
    >
      {/* Search Input Container */}
      <div
        className={cn(
          'flex items-center gap-2 rounded-lg transition-all duration-200',
          variant === 'default' &&
            cn(
              'bg-secondary-100 dark:bg-secondary-800',
              isExpanded && 'bg-white dark:bg-secondary-900 ring-2 ring-primary-500/20'
            ),
          variant === 'ghost' && 'bg-transparent hover:bg-secondary-100 dark:hover:bg-secondary-800'
        )}
      >
        <Search
          className={cn(
            'w-4 h-4 flex-shrink-0',
            variant === 'default' ? 'ml-3 text-secondary-500' : 'mx-2 text-secondary-600 dark:text-secondary-400'
          )}
        />
        <input
          ref={inputRef}
          type="text"
          value={query}
          onChange={handleQueryChange}
          onKeyDown={handleKeyDown}
          onFocus={() => setIsOpen(true)}
          placeholder={placeholder}
          className={cn(
            'flex-1 bg-transparent text-sm text-secondary-900 dark:text-secondary-100 placeholder-secondary-500',
            'focus:outline-none',
            variant === 'default' ? 'py-2 pr-3' : 'py-2',
            variant === 'mobile-expanded' && 'w-full'
          )}
          aria-label="Search"
          aria-expanded={isOpen}
          role="searchbox"
        />
        {isLoading && <Loader2 className="w-4 h-4 animate-spin text-secondary-400" />}
        {query && !isLoading && (
          <button
            onClick={handleClear}
            className="p-1 rounded-full hover:bg-secondary-200 dark:hover:bg-secondary-700 transition-colors"
            aria-label="Clear search"
          >
            <X className="w-3.5 h-3.5 text-secondary-500" />
          </button>
        )}
      </div>

      {/* Search Dropdown */}
      {(isOpen || isFocused) && (query || recentSearches.length > 0) && (
        <div
          className={cn(
            'absolute left-0 right-0 mt-2 py-2 bg-white dark:bg-secondary-800 rounded-lg shadow-lg',
            'border border-secondary-200 dark:border-secondary-700 z-50',
            'max-h-96 overflow-y-auto'
          )}
          role="listbox"
        >
          {/* Loading State */}
          {isLoading && (
            <div className="px-4 py-3 text-sm text-secondary-500 dark:text-secondary-400 flex items-center gap-2">
              <Loader2 className="w-4 h-4 animate-spin" />
              Searching...
            </div>
          )}

          {/* Suggestions */}
          {!isLoading && suggestions.length > 0 && (
            <div>
              <div className="px-4 py-2 text-xs font-semibold text-secondary-500 uppercase tracking-wider">
                Suggestions
              </div>
              {suggestions.map((suggestion, index) => (
                <button
                  key={suggestion}
                  onClick={() => handleSearch(suggestion)}
                  className={cn(
                    'w-full flex items-center gap-3 px-4 py-2.5 text-sm text-left',
                    'hover:bg-secondary-100 dark:hover:bg-secondary-700 transition-colors',
                    index === selectedIndex && 'bg-secondary-100 dark:bg-secondary-700'
                  )}
                  role="option"
                  aria-selected={index === selectedIndex}
                >
                  <Search className="w-4 h-4 text-secondary-400" />
                  <span className="text-secondary-700 dark:text-secondary-300">{suggestion}</span>
                </button>
              ))}
            </div>
          )}

          {/* Recent Searches */}
          {!isLoading && !query && recentSearches.length > 0 && (
            <div>
              <div className="px-4 py-2 flex items-center justify-between">
                <span className="text-xs font-semibold text-secondary-500 uppercase tracking-wider">
                  Recent Searches
                </span>
                <button
                  onClick={clearRecentSearches}
                  className="text-xs text-primary-600 hover:text-primary-700 dark:text-primary-400"
                >
                  Clear all
                </button>
              </div>
              {recentSearches.map((search, index) => (
                <button
                  key={search}
                  onClick={() => handleSearch(search)}
                  className={cn(
                    'w-full flex items-center gap-3 px-4 py-2.5 text-sm text-left',
                    'hover:bg-secondary-100 dark:hover:bg-secondary-700 transition-colors',
                    index === selectedIndex && 'bg-secondary-100 dark:bg-secondary-700'
                  )}
                  role="option"
                >
                  <Search className="w-4 h-4 text-secondary-400" />
                  <span className="text-secondary-700 dark:text-secondary-300">{search}</span>
                </button>
              ))}
            </div>
          )}

          {/* No Results */}
          {!isLoading && query && suggestions.length === 0 && (
            <div className="px-4 py-3 text-sm text-secondary-500 dark:text-secondary-400">
              No suggestions found. Press Enter to search for "{query}"
            </div>
          )}
        </div>
      )}
    </div>
  );
}

export default SearchBar;
